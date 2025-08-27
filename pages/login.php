<?php
session_start();
require_once('../config/database_pdo.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = isset($_POST['login']) ? $_POST['login'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($login) || empty($password)) {
        $error = 'Необходимо заполнить все поля';
    } else {
        $conn = getConnection();
        $query = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";
        $params = array($login, $login);
        $stmt = executeQuery($conn, $query, $params);
        
        if ($stmt === false) {
            $error = 'Ошибка запроса: Не удалось выполнить запрос';
        } else {
            if (hasRows($stmt)) {
                $row = fetchArray($stmt);
                
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    
                    header("Location: /pages/main.php");
                    exit();
                } else {
                    $error = 'Неверный логин или пароль';
                }
            } else {
                $error = 'Неверный логин или пароль';
            }
        }
        
        freeStatement($stmt);
        closeConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-form-container animate__animated animate__fadeIn">
            <div class="logo-container text-center mb-4">
                <h1 class="company-logo">ООО "Аплана.ИТ"</h1>
                <p class="tagline">Система учёта клиентов</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form needs-validation" novalidate>
                <div class="form-group mb-3">
                    <label for="login" class="form-label">Логин или Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="login" name="login" required>
                    </div>
                </div>
                
                <div class="form-group mb-4">
                    <label for="password" class="form-label">Пароль</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg login-btn">Войти</button>
                </div>
                
                <div class="text-center mt-3">
                    <a href="/pages/register.php" class="register-link">Зарегистрироваться</a> | 
                    <a href="/pages/forgot-password.php" class="forgot-link">Забыли пароль?</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/login.js"></script>
</body>
</html> 