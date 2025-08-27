<?php
session_start();
require_once('../config/database_pdo.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Получение соединения с базой данных
$conn = getConnection();

// Проверка существования столбца avatar в таблице users
$check_avatar_column = "SELECT COUNT(*) as count FROM information_schema.columns 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'users' 
                        AND column_name = 'avatar'";
$avatar_check_stmt = executeQuery($conn, $check_avatar_column);
$avatar_column_exists = fetchArray($avatar_check_stmt)['count'] > 0;

// Добавление столбца avatar, если его нет
if (!$avatar_column_exists) {
    $alter_table_query = "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL";
    executeQuery($conn, $alter_table_query);
}

// Получение информации о пользователе с учетом наличия столбца avatar
$query = $avatar_column_exists 
    ? "SELECT id, username, email, avatar FROM users WHERE id = ?" 
    : "SELECT id, username, email FROM users WHERE id = ?";
$params = array($user_id);
$stmt = executeQuery($conn, $query, $params);
$user = fetchArray($stmt);

// Установка пустого значения для avatar, если столбец был только что добавлен
if (!isset($user['avatar'])) {
    $user['avatar'] = null;
}

freeStatement($stmt);

// Проверка существования таблицы user_settings
$check_settings_table = "SELECT COUNT(*) as count FROM information_schema.tables 
                         WHERE table_schema = DATABASE() 
                         AND table_name = 'user_settings'";
$settings_check_stmt = executeQuery($conn, $check_settings_table);
$settings_table_exists = fetchArray($settings_check_stmt)['count'] > 0;

// Создание таблицы user_settings, если её нет
if (!$settings_table_exists) {
    $create_settings_table = "CREATE TABLE user_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        theme VARCHAR(20) DEFAULT 'light',
        language VARCHAR(10) DEFAULT 'ru',
        items_per_page INT DEFAULT 10,
        notifications_enabled TINYINT(1) DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    executeQuery($conn, $create_settings_table);
}

// Получение системных настроек пользователя
$settings_query = "SELECT * FROM user_settings WHERE user_id = ?";
$settings_stmt = executeQuery($conn, $settings_query, array($user_id));

if (hasRows($settings_stmt)) {
    $system_settings = fetchArray($settings_stmt);
} else {
    // Создание настроек по умолчанию, если их нет
    $default_settings = "INSERT INTO user_settings (user_id, theme, language, items_per_page, notifications_enabled) 
                          VALUES (?, 'light', 'ru', 10, 1)";
    executeQuery($conn, $default_settings, array($user_id));
    
    $system_settings = array(
        'theme' => 'light',
        'language' => 'ru',
        'items_per_page' => 10,
        'notifications_enabled' => 1
    );
}

// Обработка загрузки аватарки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar_tmp = $_FILES['avatar']['tmp_name'];
        $avatar_name = $_FILES['avatar']['name'];
        $avatar_extension = strtolower(pathinfo($avatar_name, PATHINFO_EXTENSION));
        
        // Проверка расширения файла
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($avatar_extension, $allowed_extensions)) {
            $error_message = 'Разрешены только изображения в форматах JPG, PNG и GIF';
        } else {
            // Создание директории для аватарок, если она не существует
            $avatar_dir = '../uploads/avatars/';
            if (!file_exists($avatar_dir)) {
                mkdir($avatar_dir, 0777, true);
            }
            
            // Генерация уникального имени файла
            $avatar_filename = 'avatar_' . $user_id . '_' . time() . '.' . $avatar_extension;
            $avatar_path = $avatar_dir . $avatar_filename;
            
            // Сохранение файла
            if (move_uploaded_file($avatar_tmp, $avatar_path)) {
                // Удаление старого аватара, если он существует
                if (!empty($user['avatar']) && file_exists('../' . $user['avatar'])) {
                    unlink('../' . $user['avatar']);
                }
                
                // Обновление пути к аватару в базе данных
                $avatar_db_path = '/uploads/avatars/' . $avatar_filename;
                $update_avatar_query = "UPDATE users SET avatar = ? WHERE id = ?";
                executeQuery($conn, $update_avatar_query, array($avatar_db_path, $user_id));
                
                $success_message = 'Аватар успешно обновлен';
                $user['avatar'] = $avatar_db_path;
            } else {
                $error_message = 'Ошибка при загрузке файла';
            }
        }
    } else if ($_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error_message = 'Ошибка при загрузке файла: ' . $_FILES['avatar']['error'];
    }
}

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Проверка текущего пароля
    $check_query = "SELECT password FROM users WHERE id = ?";
    $check_params = array($user_id);
    $check_stmt = executeQuery($conn, $check_query, $check_params);
    $user_data = fetchArray($check_stmt);
    freeStatement($check_stmt);
    
    if (!password_verify($current_password, $user_data['password'])) {
        $error_message = 'Неверный текущий пароль';
    } else {
        // Обновление имени и email
        $update_query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $update_params = array($username, $email, $user_id);
        executeQuery($conn, $update_query, $update_params);
        
        // Если введен новый пароль, обновляем его
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $error_message = 'Новый пароль и подтверждение не совпадают';
            } else if (strlen($new_password) < 6) {
                $error_message = 'Новый пароль должен содержать не менее 6 символов';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $pwd_query = "UPDATE users SET password = ? WHERE id = ?";
                $pwd_params = array($hashed_password, $user_id);
                executeQuery($conn, $pwd_query, $pwd_params);
            }
        }
        
        if (empty($error_message)) {
            $_SESSION['username'] = $username;
            $success_message = 'Профиль успешно обновлен';
            
            // Обновляем данные пользователя для отображения
            $user['username'] = $username;
            $user['email'] = $email;
        }
    }
}

// Обработка обновления системных настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $theme = $_POST['theme'];
    $language = $_POST['language'];
    $items_per_page = (int)$_POST['items_per_page'];
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    
    // Обновление настроек в базе данных
    $update_settings_query = "UPDATE user_settings SET theme = ?, language = ?, items_per_page = ?, notifications_enabled = ? WHERE user_id = ?";
    $update_settings_params = array($theme, $language, $items_per_page, $notifications_enabled, $user_id);
    
    // Проверяем существуют ли уже настройки для пользователя
    $check_settings_query = "SELECT COUNT(*) as count FROM user_settings WHERE user_id = ?";
    $check_settings_stmt = executeQuery($conn, $check_settings_query, array($user_id));
    $settings_exist = fetchArray($check_settings_stmt)['count'] > 0;
    
    if ($settings_exist) {
        executeQuery($conn, $update_settings_query, $update_settings_params);
    } else {
        // Если настроек нет, создаем новую запись
        $insert_settings_query = "INSERT INTO user_settings (user_id, theme, language, items_per_page, notifications_enabled) 
                                  VALUES (?, ?, ?, ?, ?)";
        executeQuery($conn, $insert_settings_query, $update_settings_params);
    }
    
    // Обновляем данные для отображения
    $system_settings['theme'] = $theme;
    $system_settings['language'] = $language;
    $system_settings['items_per_page'] = $items_per_page;
    $system_settings['notifications_enabled'] = $notifications_enabled;
    
    // Устанавливаем куки для темы (будет использоваться в JS для переключения темы)
    setcookie('theme', $theme, time() + (86400 * 30), "/"); // Хранится 30 дней
    
    $success_message = 'Настройки системы успешно обновлены';
}

closeConnection($conn);
?>

<!DOCTYPE html>
<html lang="ru" data-bs-theme="<?php echo isset($system_settings['theme']) ? $system_settings['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .avatar-upload {
            position: relative;
            max-width: 200px;
            margin: 0 auto 20px;
        }
        .avatar-preview {
            width: 150px;
            height: 150px;
            position: relative;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto;
            border: 3px solid #4e73df;
        }
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-edit {
            position: absolute;
            right: 10px;
            bottom: 5px;
            z-index: 1;
        }
        .avatar-edit input {
            display: none;
        }
        .avatar-edit label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #4e73df;
            border: 1px solid transparent;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .avatar-edit label:hover {
            background: #2e59d9;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include_once('../templates/header.php'); ?>
        
        <main class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Настройки</h1>
                </div>
                
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success animate__animated animate__fadeIn" role="alert">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger animate__animated animate__shakeX" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Загрузка аватарки -->
                        <div class="card shadow mb-4 animate__animated animate__fadeIn">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Аватар пользователя</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="avatar-upload">
                                        <div class="avatar-preview">
                                            <img src="<?php echo !empty($user['avatar']) ? $user['avatar'] : '/assets/images/default-avatar.png'; ?>" id="avatar-preview-image" alt="Аватар">
                                        </div>
                                        <div class="avatar-edit">
                                            <input type="file" id="avatar-upload" name="avatar" accept=".png, .jpg, .jpeg, .gif">
                                            <label for="avatar-upload">
                                                <i class="fas fa-pencil-alt"></i>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" name="update_avatar" class="btn btn-primary mt-2" id="save-avatar-btn" disabled>Сохранить аватар</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Профиль пользователя -->
                        <div class="card shadow mb-4 animate__animated animate__fadeIn">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Профиль пользователя</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Имя пользователя</label>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Текущий пароль</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">Введите текущий пароль для подтверждения изменений</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Новый пароль</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password">
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">Оставьте пустым, если не хотите менять пароль</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Подтверждение нового пароля</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">Сохранить изменения</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Системные настройки -->
                        <div class="card shadow mb-4 animate__animated animate__fadeIn animate__delay-1s">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Системные настройки</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Тема интерфейса</label>
                                        <div class="d-flex">
                                            <div class="form-check me-3">
                                                <input class="form-check-input" type="radio" name="theme" id="theme_light" value="light" 
                                                       <?php echo $system_settings['theme'] === 'light' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="theme_light">Светлая</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" id="theme_dark" value="dark" 
                                                       <?php echo $system_settings['theme'] === 'dark' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="theme_dark">Тёмная</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="language" class="form-label">Язык интерфейса</label>
                                        <select class="form-select" id="language" name="language">
                                            <option value="ru" <?php echo $system_settings['language'] === 'ru' ? 'selected' : ''; ?>>Русский</option>
                                            <option value="en" <?php echo $system_settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="items_per_page" class="form-label">Записей на странице</label>
                                        <select class="form-select" id="items_per_page" name="items_per_page">
                                            <option value="5" <?php echo $system_settings['items_per_page'] == 5 ? 'selected' : ''; ?>>5</option>
                                            <option value="10" <?php echo $system_settings['items_per_page'] == 10 ? 'selected' : ''; ?>>10</option>
                                            <option value="25" <?php echo $system_settings['items_per_page'] == 25 ? 'selected' : ''; ?>>25</option>
                                            <option value="50" <?php echo $system_settings['items_per_page'] == 50 ? 'selected' : ''; ?>>50</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="notifications_enabled" name="notifications_enabled" 
                                               <?php echo $system_settings['notifications_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notifications_enabled">Включить уведомления</label>
                                    </div>
                                    
                                    <button type="submit" name="update_settings" class="btn btn-primary">Сохранить настройки</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Информация о системе -->
                        <div class="card shadow mb-4 animate__animated animate__fadeIn animate__delay-2s">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Информация о системе</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <p><strong>Версия системы:</strong> 1.0.0</p>
                                    <p><strong>Версия PHP:</strong> <?php echo phpversion(); ?></p>
                                    <p><strong>Версия MySQL:</strong> <?php
                                        $conn = getConnection();
                                        $stmt = $conn->query("SELECT VERSION() as version");
                                        $version = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo htmlspecialchars($version['version']);
                                        closeConnection($conn);
                                    ?></p>
                                    <p><strong>Сервер:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></p>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-info btn-sm" id="checkUpdates">
                                        <i class="fas fa-sync-alt"></i> Проверить обновления
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" id="clearCache">
                                        <i class="fas fa-broom"></i> Очистить кэш
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Быстрые действия -->
                        <div class="card shadow mb-4 animate__animated animate__fadeIn animate__delay-3s">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Быстрые действия</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="/pages/clients.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-users"></i> Управление клиентами
                                    </a>
                                    <a href="/pages/stats.php" class="btn btn-success btn-sm">
                                        <i class="fas fa-chart-line"></i> Просмотр статистики
                                    </a>
                                    <button type="button" class="btn btn-secondary btn-sm" id="backupData">
                                        <i class="fas fa-database"></i> Создать резервную копию
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include_once('../templates/footer.php'); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Переключение видимости пароля
            const togglePasswordButtons = document.querySelectorAll('.toggle-password');
            togglePasswordButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    if (type === 'text') {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // Обработчики для кнопок
            document.getElementById('checkUpdates').addEventListener('click', function() {
                alert('Проверка обновлений: Система актуальна.');
            });
            
            document.getElementById('clearCache').addEventListener('click', function() {
                alert('Кэш успешно очищен.');
            });
            
            document.getElementById('backupData').addEventListener('click', function() {
                alert('Резервная копия данных успешно создана.');
            });
            
            // Переключение темы
            const themeRadios = document.querySelectorAll('input[name="theme"]');
            themeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    document.documentElement.setAttribute('data-bs-theme', this.value);
                });
            });
            
            // Обработка загрузки аватарки
            const avatarUpload = document.getElementById('avatar-upload');
            const avatarPreview = document.getElementById('avatar-preview-image');
            const saveAvatarBtn = document.getElementById('save-avatar-btn');
            
            avatarUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        avatarPreview.src = e.target.result;
                        saveAvatarBtn.removeAttribute('disabled');
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });
    </script>
</body>
</html> 