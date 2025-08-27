<?php
session_start();
require_once('../config/database_pdo.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Маркировка всех уведомлений пользователя как прочитанных
if (isset($_POST['mark_all_read'])) {
    $conn = getConnection();
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $params = array($_SESSION['user_id']);
    executeQuery($conn, $query, $params);
    closeConnection($conn);
    
    // Перенаправление для избежания повторной отправки формы
    header("Location: /pages/notifications.php");
    exit();
}

// Получение списка уведомлений
$notifications = [];
$conn = getConnection();

try {
    // Проверка существования таблицы notifications
    $checkTableQuery = "SELECT COUNT(*) as count FROM information_schema.tables 
                       WHERE table_schema = DATABASE() 
                       AND table_name = 'notifications'";
    $checkTableStmt = executeQuery($conn, $checkTableQuery);
    $tableExists = (fetchArray($checkTableStmt)['count'] > 0);
    
    if (!$tableExists) {
        // Создание таблицы уведомлений, если не существует
        $createTableQuery = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            link VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        executeQuery($conn, $createTableQuery);
        
        // Добавление тестовых уведомлений
        $insertQuery = "INSERT INTO notifications (user_id, message, link) VALUES 
                      (?, 'Добро пожаловать в систему учета клиентов!', '/pages/main.php'),
                      (?, 'У вас есть доступ к новому отчету по клиентам', '/pages/stats.php'),
                      (?, 'Обновлен профиль клиента ООО Рога и Копыта', '/pages/clients.php')";
        executeQuery($conn, $insertQuery, array($_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']));
    }
    
    // Получение уведомлений пользователя
    $query = "SELECT id, message, is_read, link, created_at FROM notifications 
             WHERE user_id = ? ORDER BY created_at DESC";
    $params = array($_SESSION['user_id']);
    $stmt = executeQuery($conn, $query, $params);
    
    while ($row = fetchArray($stmt)) {
        $notifications[] = $row;
    }
    
    // Получение количества непрочитанных уведомлений
    $unreadQuery = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $unreadStmt = executeQuery($conn, $unreadQuery, $params);
    $unreadCount = fetchArray($unreadStmt)['count'];
    
} catch (Exception $e) {
    $error = "Ошибка при получении уведомлений: " . $e->getMessage();
} finally {
    closeConnection($conn);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Уведомления | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notification-item {
            padding: 15px;
            border-left: 3px solid #eee;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-unread {
            border-left-color: #4e73df;
            background-color: #f0f7ff;
        }
        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .notification-actions {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include_once('../templates/header.php'); ?>
        
        <main class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Уведомления</h1>
                    
                    <div>
                        <form method="POST" action="" class="d-inline">
                            <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
                                <i class="fas fa-check-double"></i> Отметить все как прочитанные
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow mb-4 animate__animated animate__fadeIn">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Список уведомлений 
                            <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                            <span class="badge rounded-pill bg-danger"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php elseif (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                            <p class="lead">У вас нет уведомлений</p>
                        </div>
                        <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo ($notification['is_read'] == 0) ? 'notification-unread' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <?php if ($notification['is_read'] == 0): ?>
                                        <span class="badge rounded-pill bg-primary me-2">Новое</span>
                                        <?php endif; ?>
                                        <span class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></span>
                                    </div>
                                    <span class="notification-time"><?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?></span>
                                </div>
                                
                                <div class="notification-actions">
                                    <?php if (!empty($notification['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt"></i> Перейти
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
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
            // Автоматическое обновление количества непрочитанных уведомлений
            const updateNotificationCount = () => {
                fetch('/pages/get_unread_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.querySelector('.notification-bell .badge');
                        if (badge) {
                            badge.textContent = data.count;
                            if (data.count == 0) {
                                badge.style.display = 'none';
                            } else {
                                badge.style.display = 'inline-block';
                            }
                        }
                    })
                    .catch(error => console.error('Ошибка:', error));
            };
            
            // Обновление каждые 60 секунд
            setInterval(updateNotificationCount, 60000);
        });
    </script>
</body>
</html> 