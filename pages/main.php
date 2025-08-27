<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ООО Аплана.ИТ - Система учёта клиентов</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <?php include_once('../templates/header.php'); ?>
        
        <main class="main-content">
            <div class="welcome-section">
                <h1 class="animate__animated animate__fadeInDown">Система учёта клиентов ООО "Аплана.ИТ"</h1>
                <p class="lead animate__animated animate__fadeIn animate__delay-1s">Добро пожаловать, <?php echo $_SESSION['username']; ?>!</p>
            </div>
            
            <div class="dashboard">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card dashboard-card animate__animated animate__fadeInLeft">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-users"></i> Клиенты</h5>
                                <p class="card-text">Управление базой клиентов</p>
                                <a href="/pages/clients.php" class="btn btn-primary">Перейти</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card dashboard-card animate__animated animate__fadeInUp">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-chart-line"></i> Статистика</h5>
                                <p class="card-text">Просмотр аналитики и отчётов</p>
                                <a href="/pages/stats.php" class="btn btn-primary">Перейти</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card dashboard-card animate__animated animate__fadeInRight">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-cog"></i> Настройки</h5>
                                <p class="card-text">Управление настройками системы</p>
                                <a href="/pages/settings.php" class="btn btn-primary">Перейти</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include_once('../templates/footer.php'); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html> 