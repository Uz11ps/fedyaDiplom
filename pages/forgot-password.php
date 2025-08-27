<?php
session_start();
require_once('../config/database_pdo.php');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error_message = 'Пожалуйста, введите ваш email';
    } else {
        $conn = getConnection();
        
        // Проверка существования пользователя с таким email
        $query = "SELECT id, username FROM users WHERE email = ?";
        $params = array($email);
        $stmt = executeQuery($conn, $query, $params);
        
        if (hasRows($stmt)) {
            $user = fetchArray($stmt);
            $user_id = $user['id'];
            $username = $user['username'];
            
            // Генерация токена сброса пароля
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Проверка существования таблицы password_resets
            $check_table_query = "SELECT COUNT(*) as count FROM information_schema.tables 
                                WHERE table_schema = DATABASE() 
                                AND table_name = 'password_resets'";
            $check_table_stmt = executeQuery($conn, $check_table_query);
            $table_exists = fetchArray($check_table_stmt)['count'] > 0;
            
            if (!$table_exists) {
                // Создание таблицы для токенов сброса пароля
                $create_table_query = "CREATE TABLE password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                executeQuery($conn, $create_table_query);
            }
            
            // Удаление предыдущих токенов для этого пользователя
            $delete_tokens_query = "DELETE FROM password_resets WHERE user_id = ?";
            executeQuery($conn, $delete_tokens_query, array($user_id));
            
            // Сохранение нового токена
            $insert_token_query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
            executeQuery($conn, $insert_token_query, array($user_id, $token, $expires));
            
            // Формирование ссылки для сброса пароля
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/pages/reset-password.php?token=" . $token;
            
            // В реальном приложении здесь должна быть отправка email
            // Но для демонстрации просто выводим ссылку
            $success_message = 'Инструкции по сбросу пароля были отправлены на ваш email.<br>
                               Демо-ссылка: <a href="' . $reset_link . '">' . $reset_link . '</a>';
        } else {
            // Не сообщаем, что email не найден, чтобы избежать утечки информации
            $success_message = 'Если указанный email зарегистрирован в системе, 
                              инструкции по сбросу пароля будут отправлены на него.';
        }
        
        closeConnection($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="ru" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .forgot-password-container {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
        }
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card-header {
            background-color: #4e73df;
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-bottom: none;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        .logo-wrapper {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo-text {
            font-size: 1.8rem;
            font-weight: bold;
            color: #4e73df;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container animate__animated animate__fadeIn">
        <div class="logo-wrapper">
            <a href="/pages/main.php" class="logo-text">
                <i class="fas fa-building"></i> ООО "Аплана.ИТ"
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4 class="m-0"><i class="fas fa-key me-2"></i>Восстановление пароля</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger animate__animated animate__shakeX" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success animate__animated animate__fadeIn" role="alert">
                    <?php echo $success_message; ?>
                </div>
                <?php else: ?>
                <p class="mb-4">Введите ваш email, и мы отправим вам инструкции по сбросу пароля.</p>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="Введите ваш email">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Отправить инструкции
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="/pages/login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Вернуться на страницу входа
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 