<?php
session_start();
require_once('../config/database_pdo.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Получение данных для статистики
$conn = getConnection();

// Общее количество клиентов
$totalClientsQuery = "SELECT COUNT(*) as count FROM clients";
$totalClientsStmt = executeQuery($conn, $totalClientsQuery);
$totalClients = fetchArray($totalClientsStmt)['count'];
freeStatement($totalClientsStmt);

// Распределение клиентов по категориям
$categoriesQuery = "SELECT category, COUNT(*) as count FROM clients GROUP BY category";
$categoriesStmt = executeQuery($conn, $categoriesQuery);
$categories = [];
while ($row = fetchArray($categoriesStmt)) {
    $categories[$row['category']] = $row['count'];
}
freeStatement($categoriesStmt);

// Количество заказов
$ordersQuery = "SELECT COUNT(*) as count, SUM(amount) as total FROM orders";
$ordersStmt = executeQuery($conn, $ordersQuery);
$ordersStats = fetchArray($ordersStmt);
$totalOrders = $ordersStats['count'];
$totalAmount = $ordersStats['total'] ?? 0;
freeStatement($ordersStmt);

// Статистика по статусам заказов
$orderStatusQuery = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$orderStatusStmt = executeQuery($conn, $orderStatusQuery);
$orderStatuses = [];
while ($row = fetchArray($orderStatusStmt)) {
    $orderStatuses[$row['status']] = $row['count'];
}
freeStatement($orderStatusStmt);

// Топ-5 клиентов по сумме заказов
$topClientsQuery = "SELECT c.id, c.name, COUNT(o.id) as orders_count, SUM(o.amount) as total_amount 
                    FROM clients c 
                    LEFT JOIN orders o ON c.id = o.client_id 
                    GROUP BY c.id, c.name 
                    ORDER BY total_amount DESC 
                    LIMIT 5";
$topClientsStmt = executeQuery($conn, $topClientsQuery);
$topClients = [];
while ($row = fetchArray($topClientsStmt)) {
    $topClients[] = $row;
}
freeStatement($topClientsStmt);

// Заказы по месяцам (за последний год)
$monthlyOrdersQuery = "SELECT 
                        DATE_FORMAT(order_date, '%Y-%m') as month,
                        COUNT(*) as count,
                        SUM(amount) as amount
                       FROM orders
                       WHERE order_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                       GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                       ORDER BY month";
$monthlyOrdersStmt = executeQuery($conn, $monthlyOrdersQuery);
$monthlyOrders = [];
while ($row = fetchArray($monthlyOrdersStmt)) {
    $monthlyOrders[] = $row;
}
freeStatement($monthlyOrdersStmt);

closeConnection($conn);

// Подготовка данных для графиков в формате JSON
$categoriesData = json_encode(array_values($categories));
$categoriesLabels = json_encode(array_keys($categories));
$monthlyOrdersData = json_encode(array_column($monthlyOrders, 'amount'));
$monthlyOrdersLabels = json_encode(array_column($monthlyOrders, 'month'));
$statusData = json_encode(array_values($orderStatuses));
$statusLabels = json_encode(array_keys($orderStatuses));
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stat-card {
            transition: transform 0.3s ease-in-out;
            margin-bottom: 20px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            height: 300px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include_once('../templates/header.php'); ?>
        
        <main class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Статистика и аналитика</h1>
                    <div>
                        <button class="btn btn-outline-primary me-2" id="printStats">
                            <i class="fas fa-print"></i> Печать
                        </button>
                        <button class="btn btn-outline-success" id="exportStats">
                            <i class="fas fa-file-excel"></i> Экспорт
                        </button>
                    </div>
                </div>
                
                <!-- Основные показатели -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white stat-card animate__animated animate__fadeIn">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white">Всего клиентов</h6>
                                        <h2 class="mb-0"><?php echo $totalClients; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-success text-white stat-card animate__animated animate__fadeIn animate__delay-1s">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white">Всего заказов</h6>
                                        <h2 class="mb-0"><?php echo $totalOrders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-info text-white stat-card animate__animated animate__fadeIn animate__delay-2s">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white">Средний чек</h6>
                                        <h2 class="mb-0"><?php echo $totalOrders > 0 ? number_format($totalAmount / $totalOrders, 0, '.', ' ') : 0; ?> ₽</h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-ruble-sign fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-warning text-white stat-card animate__animated animate__fadeIn animate__delay-3s">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white">Общая сумма</h6>
                                        <h2 class="mb-0"><?php echo number_format($totalAmount, 0, '.', ' '); ?> ₽</h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Графики -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Клиенты по категориям</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="categoriesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Статусы заказов</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Динамика заказов по месяцам</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="monthlyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Топ клиентов -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Топ-5 клиентов</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Название</th>
                                                <th>Количество заказов</th>
                                                <th>Общая сумма</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($topClients)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Данные отсутствуют</td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($topClients as $client): ?>
                                            <tr>
                                                <td><?php echo $client['id']; ?></td>
                                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                                <td><?php echo $client['orders_count']; ?></td>
                                                <td><?php echo number_format($client['total_amount'] ?? 0, 2, '.', ' '); ?> ₽</td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Инициализация графиков
        document.addEventListener('DOMContentLoaded', function() {
            // График категорий клиентов
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            new Chart(categoriesCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo $categoriesLabels; ?>,
                    datasets: [{
                        data: <?php echo $categoriesData; ?>,
                        backgroundColor: [
                            '#4e73df', '#36b9cc', '#1cc88a', '#f6c23e', '#e74a3b', '#858796'
                        ],
                        hoverBackgroundColor: [
                            '#2e59d9', '#2c9faf', '#17a673', '#dda20a', '#be2617', '#60616f'
                        ],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // График статусов заказов
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo $statusLabels; ?>,
                    datasets: [{
                        data: <?php echo $statusData; ?>,
                        backgroundColor: [
                            '#1cc88a', '#f6c23e', '#e74a3b', '#4e73df', '#858796'
                        ],
                        hoverBackgroundColor: [
                            '#17a673', '#dda20a', '#be2617', '#2e59d9', '#60616f'
                        ],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // График заказов по месяцам
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?php echo $monthlyOrdersLabels; ?>,
                    datasets: [{
                        label: "Сумма заказов",
                        lineTension: 0.3,
                        backgroundColor: "rgba(78, 115, 223, 0.05)",
                        borderColor: "rgba(78, 115, 223, 1)",
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointBorderColor: "rgba(78, 115, 223, 1)",
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        data: <?php echo $monthlyOrdersData; ?>,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Обработчики кнопок
            document.getElementById('printStats').addEventListener('click', function() {
                window.print();
            });
            
            // Обработчик клика на кнопку экспорта
            $('#exportStats').click(function() {
                // Получаем текущую дату и первый день месяца как значения по умолчанию
                const currentDate = new Date();
                const firstDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                
                // Форматируем даты в формате YYYY-MM-DD
                const startDate = firstDayOfMonth.toISOString().split('T')[0];
                const endDate = currentDate.toISOString().split('T')[0];
                
                // Перенаправление на страницу экспорта статистики
                window.location.href = '/pages/export_statistics.php?start_date=' + startDate + '&end_date=' + endDate;
            });
        });
    </script>
</body>
</html> 