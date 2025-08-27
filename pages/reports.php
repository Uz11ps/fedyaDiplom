<?php
session_start();
require_once('../config/database_pdo.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Получение данных для отчетов
$conn = getConnection();

// Пример отчета: Общая статистика по заказам за последние 6 месяцев
$sixMonthsAgoQuery = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    COUNT(*) as count,
                    SUM(amount) as amount,
                    AVG(amount) as avg_amount,
                    MIN(amount) as min_amount,
                    MAX(amount) as max_amount
                FROM orders
                WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY month DESC";
$sixMonthsAgoStmt = executeQuery($conn, $sixMonthsAgoQuery);
$monthlyStats = [];
while ($row = fetchArray($sixMonthsAgoStmt)) {
    $monthlyStats[] = $row;
}
freeStatement($sixMonthsAgoStmt);

// Статистика по категориям клиентов
$categoriesQuery = "SELECT c.category, 
                    COUNT(DISTINCT c.id) as client_count, 
                    COUNT(o.id) as order_count, 
                    SUM(o.amount) as total_amount
                 FROM clients c
                 LEFT JOIN orders o ON c.id = o.client_id
                 GROUP BY c.category";
$categoriesStmt = executeQuery($conn, $categoriesQuery);
$categoryStats = [];
while ($row = fetchArray($categoriesStmt)) {
    $categoryStats[] = $row;
}
freeStatement($categoriesStmt);

// Конвертируем месяцы для отображения в отчетах
$monthLabels = [];
$monthValues = [];
$monthOrders = [];

foreach ($monthlyStats as $stat) {
    // Преобразуем формат даты для отображения
    $date = DateTime::createFromFormat('Y-m', $stat['month']);
    $monthLabels[] = $date ? $date->format('M Y') : $stat['month'];
    $monthValues[] = $stat['amount'];
    $monthOrders[] = $stat['count'];
}

closeConnection($conn);

// Подготовка данных для графиков
$monthLabelsJson = json_encode($monthLabels);
$monthValuesJson = json_encode($monthValues);
$monthOrdersJson = json_encode($monthOrders);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include_once('../templates/header.php'); ?>
        
        <main class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Отчеты</h1>
                    <div>
                        <button class="btn btn-outline-primary me-2" id="printReport">
                            <i class="fas fa-print"></i> Печать
                        </button>
                        <button class="btn btn-outline-success" id="exportReport">
                            <i class="fas fa-file-excel"></i> Экспорт в Excel
                        </button>
                    </div>
                </div>
                
                <!-- Фильтры для отчетов -->
                <div class="card shadow mb-4 animate__animated animate__fadeIn">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Параметры отчета</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" id="reportFilterForm">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="date_from" class="form-label">Дата с</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="date_to" class="form-label">Дата по</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="category" class="form-label">Категория клиентов</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">Все категории</option>
                                        <option value="Розничный">Розничный</option>
                                        <option value="Корпоративный">Корпоративный</option>
                                        <option value="Партнер">Партнер</option>
                                        <option value="VIP">VIP</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row justify-content-end">
                                <div class="col-md-auto">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Применить фильтры
                                    </button>
                                    <button type="reset" class="btn btn-secondary ms-2">
                                        <i class="fas fa-redo"></i> Сбросить
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Графики отчетов -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow mb-4 animate__animated animate__fadeIn animate__delay-1s">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Динамика продаж за последние 6 месяцев</h6>
                            </div>
                            <div class="card-body">
                                <div style="height: 300px;">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow mb-4 animate__animated animate__fadeIn animate__delay-1s">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Распределение по категориям</h6>
                            </div>
                            <div class="card-body">
                                <div style="height: 300px;">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Таблица с данными -->
                <div class="card shadow mb-4 animate__animated animate__fadeIn animate__delay-2s">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Статистика по категориям клиентов</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Категория</th>
                                        <th>Количество клиентов</th>
                                        <th>Количество заказов</th>
                                        <th>Общая сумма</th>
                                        <th>Средний чек</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categoryStats)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Данные отсутствуют</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($categoryStats as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['category']); ?></td>
                                        <td><?php echo $stat['client_count']; ?></td>
                                        <td><?php echo $stat['order_count']; ?></td>
                                        <td><?php echo number_format($stat['total_amount'] ?? 0, 2, '.', ' '); ?> ₽</td>
                                        <td>
                                            <?php 
                                            if ($stat['order_count'] > 0) {
                                                echo number_format(($stat['total_amount'] / $stat['order_count']), 2, '.', ' ') . ' ₽';
                                            } else {
                                                echo '0.00 ₽';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
        document.addEventListener('DOMContentLoaded', function() {
            // График динамики продаж
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?php echo $monthLabelsJson; ?>,
                    datasets: [
                        {
                            label: 'Сумма продаж',
                            data: <?php echo $monthValuesJson; ?>,
                            borderColor: 'rgba(78, 115, 223, 1)',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Количество заказов',
                            data: <?php echo $monthOrdersJson; ?>,
                            borderColor: 'rgba(28, 200, 138, 1)',
                            backgroundColor: 'rgba(28, 200, 138, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Сумма продаж (₽)'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Количество заказов'
                            }
                        }
                    }
                }
            });
            
            // График категорий
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_column($categoryStats, 'category')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($categoryStats, 'total_amount')); ?>,
                        backgroundColor: [
                            'rgba(78, 115, 223, 0.8)',
                            'rgba(28, 200, 138, 0.8)',
                            'rgba(246, 194, 62, 0.8)',
                            'rgba(231, 74, 59, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Обработчики кнопок
            document.getElementById('printReport').addEventListener('click', function() {
                window.print();
            });
            
            // Обработчик клика на кнопку экспорта
            $('#exportReport').click(function() {
                // Перенаправление на страницу экспорта
                window.location.href = '/pages/export.php';
            });
        });
    </script>
</body>
</html> 