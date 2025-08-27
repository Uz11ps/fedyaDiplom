<?php
// Проверка подключения к базе данных
if (!function_exists('getConnection')) {
    require_once(__DIR__ . '/../config/database_pdo.php');
}

// Получение количества непрочитанных уведомлений
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $conn = getConnection();
        
        // Проверка существования таблицы notifications
        $check_table_query = "SELECT COUNT(*) as count FROM information_schema.tables 
                             WHERE table_schema = DATABASE() 
                             AND table_name = 'notifications'";
        $check_table_stmt = executeQuery($conn, $check_table_query);
        $table_exists = fetchArray($check_table_stmt)['count'] > 0;
        
        if ($table_exists) {
            $unread_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
            $unread_stmt = executeQuery($conn, $unread_query, array($_SESSION['user_id']));
            $unread_count = fetchArray($unread_stmt)['count'];
        }
        
        closeConnection($conn);
    } catch (Exception $e) {
        // В случае ошибки оставляем значение по умолчанию
    }
}
?>
<!DOCTYPE html>
<script>
// Установка темы до загрузки контента для избежания мигания
(function() {
    // Получение темы из localStorage или cookie
    const localTheme = localStorage.getItem('theme');
    const cookieTheme = ("; " + document.cookie).split("; theme=").pop().split(";").shift();
    const theme = localTheme || cookieTheme || 'light';
    
    // Применение темы
    document.documentElement.setAttribute('data-bs-theme', theme);
})();
</script>
<header class="main-header">
    <?php
    // Получение аватара пользователя, если он не передан
    if (!isset($user) || !isset($user['avatar'])) {
        $conn = getConnection();
        
        // Проверка существования столбца avatar
        $check_avatar_column = "SELECT COUNT(*) as count FROM information_schema.columns 
                                WHERE table_schema = DATABASE() 
                                AND table_name = 'users' 
                                AND column_name = 'avatar'";
        $avatar_check_stmt = executeQuery($conn, $check_avatar_column);
        $avatar_column_exists = fetchArray($avatar_check_stmt)['count'] > 0;
        
        // Получение данных пользователя с учетом наличия столбца avatar
        if ($avatar_column_exists) {
            $user_query = "SELECT avatar FROM users WHERE id = ?";
            $user_params = array($_SESSION['user_id']);
            $user_stmt = executeQuery($conn, $user_query, $user_params);
            $user_data = fetchArray($user_stmt);
            $avatar_path = $user_data['avatar'] ? $user_data['avatar'] : '/assets/images/default-avatar.png';
        } else {
            $avatar_path = '/assets/images/default-avatar.png';
        }
        
        closeConnection($conn);
    } else {
        $avatar_path = $user['avatar'] ? $user['avatar'] : '/assets/images/default-avatar.png';
    }
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand animate__animated animate__pulse animate__infinite animate__slower" href="/pages/main.php">
                <i class="fas fa-building"></i> ООО "Аплана.ИТ"
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'main.php') ? 'active' : ''; ?>" href="/pages/main.php">
                            <i class="fas fa-home"></i> Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'clients.php') ? 'active' : ''; ?>" href="/pages/clients.php">
                            <i class="fas fa-users"></i> Клиенты
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>" href="/pages/orders.php">
                            <i class="fas fa-shopping-cart"></i> Заказы
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-chart-line"></i> Аналитика
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item" href="/pages/stats.php">
                                    <i class="fas fa-chart-bar"></i> Статистика
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/pages/reports.php">
                                    <i class="fas fa-file-alt"></i> Отчёты
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/pages/export.php">
                                    <i class="fas fa-file-export"></i> Экспорт в Excel
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/pages/analytics.php">
                                    <i class="fas fa-analytics"></i> Общая аналитика
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>" href="/pages/settings.php">
                            <i class="fas fa-cog"></i> Настройки
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <div class="notification-bell me-3 position-relative">
                        <a href="/pages/notifications.php" class="text-light notification-link">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_count; ?>
                                <span class="visually-hidden">непрочитанных уведомлений</span>
                            </span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="user-profile dropdown">
                        <a class="nav-link dropdown-toggle text-light" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $avatar_path; ?>" alt="Аватар" class="avatar-img">
                            <span class="ms-2"><?php echo $_SESSION['username']; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="/pages/profile.php">
                                    <i class="fas fa-user-circle"></i> Профиль
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/pages/settings.php">
                                    <i class="fas fa-cog"></i> Настройки
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/pages/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Выйти
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="breadcrumb-container">
        <div class="container-fluid">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/pages/main.php">Главная</a></li>
                    <?php
                    $current_page = basename($_SERVER['PHP_SELF'], '.php');
                    if ($current_page != 'main') {
                        $page_titles = [
                            'clients' => 'Клиенты',
                            'stats' => 'Статистика',
                            'reports' => 'Отчёты',
                            'analytics' => 'Аналитика',
                            'settings' => 'Настройки',
                            'profile' => 'Профиль'
                        ];
                        
                        if (isset($page_titles[$current_page])) {
                            echo '<li class="breadcrumb-item active" aria-current="page">' . $page_titles[$current_page] . '</li>';
                        }
                    }
                    ?>
                </ol>
            </nav>
        </div>
    </div>
</header>

<style>
    /* Стили для ссылки колокольчика */
    .notification-bell {
        font-size: 1.2rem;
        position: relative;
    }
    
    .notification-link {
        display: block;
        padding: 10px; /* Увеличиваем область клика */
        cursor: pointer;
        text-decoration: none !important; /* Предотвращаем подчеркивание */
        position: relative;
        z-index: 1000; /* Высокий z-index */
    }
    
    .notification-link:hover,
    .notification-link:focus,
    .notification-link:active {
        color: #fff !important;
        text-decoration: none !important;
    }
    
    /* Контейнер для значка */
    .badge-container {
        position: absolute;
        top: 0;
        right: 0;
        transform: translate(40%, -40%);
        z-index: 999;
    }
    
    /* Стиль значка */
    .notification-badge {
        z-index: 999;
        pointer-events: none; /* Предотвращаем перехват кликов значком */
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Немедленно заменяем структуру колокольчика для лучшей работы
        const notificationBell = document.querySelector('.notification-bell');
        if (notificationBell) {
            const bellLink = notificationBell.querySelector('a');
            const badgeElement = notificationBell.querySelector('.badge');
            
            // Упрощаем структуру для более надежной работы
            if (bellLink && badgeElement) {
                // Текст значка
                const badgeText = badgeElement.textContent.trim();
                // Сохраняем скрытый текст
                const hiddenText = badgeElement.querySelector('.visually-hidden') 
                    ? badgeElement.querySelector('.visually-hidden').outerHTML 
                    : '';
                
                // Обновляем структуру
                bellLink.innerHTML = `
                    <i class="fas fa-bell"></i>
                    ${badgeText > 0 ? `
                    <span class="badge-container">
                        <span class="badge rounded-pill bg-danger notification-badge">
                            ${badgeText}
                            ${hiddenText}
                        </span>
                    </span>
                    ` : ''}
                `;
                
                // Добавляем прямой обработчик клика
                bellLink.onclick = function(e) {
                    window.location.href = '/pages/notifications.php';
                    return true;
                };
            }
        }
    });
</script>

<!-- Подключаем скрипт для обработки уведомлений -->
<script src="/assets/js/notifications.js"></script> 