<?php
session_start();
require_once('../config/database_pdo.php');

$error_message = '';
$success_message = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$valid_token = false;
$user_id = null;

if (empty($token)) {
    $error_message = 'Недействительный токен сброса пароля.';
} else {
    $conn = getConnection();
    
    // Проверка существования таблицы password_resets
    $check_table_query = "SELECT COUNT(*) as count FROM information_schema.tables 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'password_resets'";
    $check_table_stmt = executeQuery($conn, $check_table_query);
    $table_exists = fetchArray($check_table_stmt)['count'] > 0;
    
    if ($table_exists) {
        // Проверка токена
        $token_query = "SELECT user_id, expires_at FROM password_resets WHERE token = ?";
        $token_stmt = executeQuery($conn, $token_query, array($token));
        
        if (hasRows($token_stmt)) {
            $token_data = fetchArray($token_stmt);
            $current_time = date('Y-m-d H:i:s');
            
            if ($token_data['expires_at'] > $current_time) {
                $valid_token = true;
                $user_id = $token_data['user_id'];
            } else {
                $error_message = 'Срок действия токена истек. Пожалуйста, запросите новую ссылку для сброса пароля.';
            }
        } else {
            $error_message = 'Недействительный токен сброса пароля.';
        }
    } else {
        $error_message = 'Произошла ошибка. Пожалуйста, запросите новую ссылку для сброса пароля.';
    }
    
    // Обработка формы сброса пароля
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($new_password) || empty($confirm_password)) {
            $error_message = 'Пожалуйста, заполните все поля.';
        } else if ($new_password !== $confirm_password) {
            $error_message = 'Пароли не совпадают.';
        } else if (strlen($new_password) < 6) {
            $error_message = 'Пароль должен содержать не менее 6 символов.';
        } else {
            // Обновление пароля пользователя
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            executeQuery($conn, $update_query, array($hashed_password, $user_id));
            
            // Удаление использованного токена
            $delete_query = "DELETE FROM password_resets WHERE token = ?";
            executeQuery($conn, $delete_query, array($token));
            
            $success_message = 'Ваш пароль был успешно сброшен. Теперь вы можете <a href="/pages/login.php">войти</a> с новым паролем.';
            $valid_token = false; // Скрываем форму
        }
    }
    
    closeConnection($conn);
}
?>

<!DOCTYPE html>
<html lang="ru" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля | ООО Аплана.ИТ</title>
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
        .reset-password-container {
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
        .password-feedback {
            margin-top: 0.25rem;
            font-size: 0.875em;
        }
    </style>
</head>
<body>
    <div class="reset-password-container animate__animated animate__fadeIn">
        <div class="logo-wrapper">
            <a href="/pages/main.php" class="logo-text">
                <i class="fas fa-building"></i> ООО "Аплана.ИТ"
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4 class="m-0"><i class="fas fa-key me-2"></i>Сброс пароля</h4>
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
                <?php elseif ($valid_token): ?>
                <p class="mb-4">Введите новый пароль для вашей учетной записи.</p>
                
                <form method="POST" action="" id="resetPasswordForm">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Новый пароль</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" required 
                                   placeholder="Введите новый пароль" minlength="6">
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-feedback" id="passwordFeedback"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                   placeholder="Подтвердите новый пароль" minlength="6">
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-feedback" id="confirmFeedback"></div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить новый пароль
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="text-center py-3">
                    <p>Если вам нужно сбросить пароль, перейдите на страницу <a href="/pages/forgot-password.php">восстановления пароля</a>.</p>
                </div>
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
            
            // Проверка совпадения паролей
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordFeedback = document.getElementById('passwordFeedback');
            const confirmFeedback = document.getElementById('confirmFeedback');
            
            if (newPasswordInput && confirmPasswordInput) {
                newPasswordInput.addEventListener('input', validatePassword);
                confirmPasswordInput.addEventListener('input', validatePasswordMatch);
                
                function validatePassword() {
                    const password = newPasswordInput.value;
                    
                    if (password.length < 6) {
                        passwordFeedback.textContent = 'Пароль должен содержать не менее 6 символов';
                        passwordFeedback.classList.add('text-danger');
                    } else {
                        passwordFeedback.textContent = 'Пароль подходит';
                        passwordFeedback.classList.remove('text-danger');
                        passwordFeedback.classList.add('text-success');
                    }
                    
                    if (confirmPasswordInput.value) {
                        validatePasswordMatch();
                    }
                }
                
                function validatePasswordMatch() {
                    const password = newPasswordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    if (password === confirmPassword) {
                        confirmFeedback.textContent = 'Пароли совпадают';
                        confirmFeedback.classList.remove('text-danger');
                        confirmFeedback.classList.add('text-success');
                    } else {
                        confirmFeedback.textContent = 'Пароли не совпадают';
                        confirmFeedback.classList.add('text-danger');
                        confirmFeedback.classList.remove('text-success');
                    }
                }
            }
        });
    </script>
</body>
</html> 