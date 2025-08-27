<?php
session_start();
require_once('../config/database_pdo.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Необходимо заполнить все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Указан некорректный email';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать не менее 6 символов';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        $conn = getConnection();
        
        // Проверка уникальности имени пользователя и email
        $check_query = "SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?";
        $params = array($username, $email);
        $stmt = executeQuery($conn, $check_query, $params);
        
        if ($stmt === false) {
            $error = 'Ошибка запроса: Не удалось выполнить запрос';
        } else {
            $row = fetchArray($stmt);
            if ($row['count'] > 0) {
                $error = 'Пользователь с таким именем или email уже существует';
            } else {
                // Регистрация нового пользователя
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_query = "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
                $insert_params = array($username, $email, $hashed_password);
                $insert_stmt = executeQuery($conn, $insert_query, $insert_params);
                
                if ($insert_stmt === false) {
                    $error = 'Ошибка при регистрации: Не удалось выполнить запрос';
                } else {
                    // Получаем ID нового пользователя
                    $user_id = $conn->lastInsertId();
                    
                    // Автоматически авторизуем пользователя
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    
                    // Перенаправляем на главную страницу
                    header("Location: /pages/main.php");
                    exit();
                }
                
                freeStatement($insert_stmt);
            }
            
            freeStatement($stmt);
        }
        
        closeConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="register-page">
    <div class="register-container">
        <div class="register-form-container animate__animated animate__fadeIn">
            <div class="logo-container text-center mb-4">
                <h1 class="company-logo">ООО "Аплана.ИТ"</h1>
                <p class="tagline">Регистрация в системе учёта клиентов</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success animate__animated animate__bounceIn"><?php echo $success; ?></div>
            <div class="text-center mt-3 mb-4">
                <a href="/pages/login.php" class="btn btn-primary">Перейти на страницу входа</a>
            </div>
            <?php else: ?>
            
            <form method="POST" action="" class="register-form needs-validation" novalidate>
                <div class="form-group mb-3">
                    <label for="username" class="form-label">Имя пользователя</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength mt-1"></div>
                </div>
                
                <div class="form-group mb-4">
                    <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg register-btn">Зарегистрироваться</button>
                </div>
                
                <div class="text-center mt-3">
                    <p>Уже есть аккаунт? <a href="/pages/login.php" class="login-link">Войти</a></p>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/register.js"></script>
</body>
</html> 