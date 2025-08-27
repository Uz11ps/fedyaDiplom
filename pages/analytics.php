<?php
session_start();
require_once('../config/database_pdo.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$conn = getConnection();

// Общая статистика
$totalClientsQuery = "SELECT COUNT(*) as count FROM clients";
$totalClientsStmt = executeQuery($conn, $totalClientsQuery);
$totalClients = fetchArray($totalClientsStmt)['count'];
freeStatement($totalClientsStmt);

$totalOrdersQuery = "SELECT COUNT(*) as count, SUM(amount) as total FROM orders";
$totalOrdersStmt = executeQuery($conn, $totalOrdersQuery);
$totalOrders = fetchArray($totalOrdersStmt)['count'];
$totalAmount = fetchArray($totalOrdersStmt)['total'] ?? 0;
freeStatement($totalOrdersStmt);

$avgOrderAmount = $totalOrders > 0 ? $totalAmount / $totalOrders : 0;

// Тренды и прогнозы
$trendsQuery = "SELECT 
                DATE_FORMAT(order_date, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(amount) as amount
               FROM orders
               GROUP BY DATE_FORMAT(order_date, '%Y-%m')
               ORDER BY month";
$trendsStmt = executeQuery($conn, $trendsQuery);
$trends = [];
while ($row = fetchArray($trendsStmt)) {
    $trends[] = $row;
}
freeStatement($trendsStmt);

// Ключевые показатели эффективности
$kpiQuery = "SELECT 
             COUNT(DISTINCT client_id) as active_clients,
             COUNT(*) as orders_count,
             SUM(amount) as total_amount,
             AVG(amount) as avg_amount
             FROM orders
             WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$kpiStmt = executeQuery($conn, $kpiQuery);
$kpi = fetchArray($kpiStmt);
freeStatement($kpiStmt);

// Расчет роста
$previousMonthQuery = "SELECT 
                      COUNT(*) as count,
                      SUM(amount) as amount
                      FROM orders
                      WHERE order_date BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)";
$previousMonthStmt = executeQuery($conn, $previousMonthQuery);
$previousMonth = fetchArray($previousMonthStmt);
freeStatement($previousMonthStmt);

$orderGrowth = ($previousMonth['count'] > 0) ? 
               (($kpi['orders_count'] - $previousMonth['count']) / $previousMonth['count'] * 100) : 
               0;

$revenueGrowth = ($previousMonth['amount'] > 0) ? 
                (($kpi['total_amount'] - $previousMonth['amount']) / $previousMonth['amount'] * 100) : 
                0;

closeConnection($conn);

// Подготовка данных для графиков
$trendLabels = [];
$trendValues = [];
$trendCounts = [];

foreach ($trends as $trend) {
    $date = DateTime::createFromFormat('Y-m', $trend['month']);
    $trendLabels[] = $date ? $date->format('M Y') : $trend['month'];
    $trendValues[] = $trend['amount'];
    $trendCounts[] = $trend['count'];
}

$trendLabelsJson = json_encode($trendLabels);
$trendValuesJson = json_encode($trendValues);
$trendCountsJson = json_encode($trendCounts);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Общая аналитика | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .kpi-card {
            transition: all 0.3s ease;
            height: 100%;
        }
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }
        .kpi-icon {
            font-size: 2.5rem;
            opacity: 0.7;
        }
        .growth-positive {
            color: #1cc88a;
        }
        .growth-negative {
            color: #e74a3b;
        }
        .chart-container {
            position: relative;
            height: 350px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include_once('../templates/header.php'); ?>
        
        <main class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Общая аналитика</h1>
                    <div>
                        <button class="btn btn-outline-primary me-2" id="shareDashboard">
                            <i class="fas fa-share-alt"></i> Поделиться
                        </button>
                        <button class="btn btn-outline-success" id="exportAnalytics">
                            <i class="fas fa-file-export"></i> Экспорт
                        </button>
                    </div>
                </div>
                
                <!-- KPI дашборд -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 kpi-card animate__animated animate__fadeIn">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Всего клиентов</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalClients); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users kpi-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2 kpi-card animate__animated animate__fadeIn animate__delay-1s">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Общая выручка</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalAmount, 2, '.', ' '); ?> ₽</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-ruble-sign kpi-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2 kpi-card animate__animated animate__fadeIn animate__delay-2s">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Выполнено заказов</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalOrders); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list kpi-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2 kpi-card animate__animated animate__fadeIn animate__delay-3s">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Средний чек</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($avgOrderAmount, 2, '.', ' '); ?> ₽</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-coins kpi-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Рост и тренды -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4 animate__animated animate__fadeIn">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Динамика продаж и заказов</h6>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary active" data-period="all">Все время</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-period="year">Год</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-period="quarter">Квартал</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="trendsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card shadow mb-4 animate__animated animate__fadeIn animate__delay-1s">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Показатели роста (30 дней)</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <p class="mb-1 font-weight-bold">Рост количества заказов</p>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar <?php echo $orderGrowth >= 0 ? 'bg-success' : 'bg-danger'; ?>" role="progressbar" 
                                                    style="width: <?php echo min(abs($orderGrowth), 100); ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <span class="<?php echo $orderGrowth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                                <?php echo $orderGrowth >= 0 ? '+' : ''; ?><?php echo number_format($orderGrowth, 1); ?>%
                                                <i class="fas fa-<?php echo $orderGrowth >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="mb-1 font-weight-bold">Рост выручки</p>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar <?php echo $revenueGrowth >= 0 ? 'bg-success' : 'bg-danger'; ?>" role="progressbar" 
                                                    style="width: <?php echo min(abs($revenueGrowth), 100); ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <span class="<?php echo $revenueGrowth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                                <?php echo $revenueGrowth >= 0 ? '+' : ''; ?><?php echo number_format($revenueGrowth, 1); ?>%
                                                <i class="fas fa-<?php echo $revenueGrowth >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <div class="mb-2">Активные клиенты за 30 дней</div>
                                    <div class="h3 mb-0 font-weight-bold"><?php echo $kpi['active_clients']; ?></div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <div class="mb-2">Средний чек за 30 дней</div>
                                    <div class="h3 mb-0 font-weight-bold"><?php echo number_format($kpi['avg_amount'] ?? 0, 2, '.', ' '); ?> ₽</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Прогнозы -->
                <div class="card shadow mb-4 animate__animated animate__fadeIn animate__delay-2s">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Прогноз на следующий квартал</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-4 mb-md-0">
                                <div class="text-center">
                                    <div class="h4 font-weight-bold mb-3">Прогноз выручки</div>
                                    <div class="display-4 mb-2 text-primary"><?php echo number_format($kpi['total_amount'] * 3, 0, '.', ' '); ?> ₽</div>
                                    <div class="text-muted">Основан на данных за 30 дней</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4 mb-md-0">
                                <div class="text-center">
                                    <div class="h4 font-weight-bold mb-3">Прогноз заказов</div>
                                    <div class="display-4 mb-2 text-success"><?php echo number_format($kpi['orders_count'] * 3, 0); ?></div>
                                    <div class="text-muted">Ожидаемое количество в следующем квартале</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 font-weight-bold mb-3">Рекомендации</div>
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Увеличить средний чек
                                            <span class="badge bg-primary rounded-pill">1</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Активировать неактивных клиентов
                                            <span class="badge bg-primary rounded-pill">2</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Расширить базу VIP-клиентов
                                            <span class="badge bg-primary rounded-pill">3</span>
                                        </li>
                                    </ul>
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
        document.addEventListener('DOMContentLoaded', function() {
            // График трендов
            const trendsCtx = document.getElementById('trendsChart').getContext('2d');
            const trendsChart = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: <?php echo $trendLabelsJson; ?>,
                    datasets: [
                        {
                            label: 'Выручка',
                            data: <?php echo $trendValuesJson; ?>,
                            borderColor: 'rgba(78, 115, 223, 1)',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Количество заказов',
                            data: <?php echo $trendCountsJson; ?>,
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
                                text: 'Выручка (₽)'
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
            
            // Переключение периодов на графике
            document.querySelectorAll('[data-period]').forEach(button => {
                button.addEventListener('click', function() {
                    // Активация кнопки
                    document.querySelectorAll('[data-period]').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Фильтрация данных в зависимости от периода
                    const period = this.dataset.period;
                    let labels = <?php echo $trendLabelsJson; ?>;
                    let values = <?php echo $trendValuesJson; ?>;
                    let counts = <?php echo $trendCountsJson; ?>;
                    
                    if (period === 'quarter') {
                        // Последние 3 месяца
                        labels = labels.slice(-3);
                        values = values.slice(-3);
                        counts = counts.slice(-3);
                    } else if (period === 'year') {
                        // Последние 12 месяцев
                        labels = labels.slice(-12);
                        values = values.slice(-12);
                        counts = counts.slice(-12);
                    }
                    
                    // Обновление данных графика
                    trendsChart.data.labels = labels;
                    trendsChart.data.datasets[0].data = values;
                    trendsChart.data.datasets[1].data = counts;
                    trendsChart.update();
                });
            });
            
            // Обработчики кнопок
            document.getElementById('shareDashboard').addEventListener('click', function() {
                $('#shareModal').modal('show');
            });
            
            document.getElementById('exportAnalytics').addEventListener('click', function() {
                window.location.href = '/pages/export.php';
            });
            
            // Обработка форм шаринга
            $('#shareForm').submit(function(e) {
                e.preventDefault();
                
                const shareMethod = $('#shareMethod').val();
                const email = $('#shareEmail').val();
                const period = $('[data-period].active').data('period') || '30days';
                
                $.ajax({
                    url: '/pages/share_analytics.php',
                    type: 'POST',
                    data: {
                        share_method: shareMethod,
                        email: email,
                        period: period
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (response.redirect && response.share_url) {
                                window.open(response.share_url, '_blank');
                                $('#shareModal').modal('hide');
                            } else if (shareMethod === 'link' && response.share_url) {
                                $('#copyLinkInput').val(response.share_url);
                                $('#shareResultModal .modal-title').text('Ссылка создана');
                                $('#shareResultModal .modal-body').html(`
                                    <p>Используйте эту ссылку для шаринга аналитики:</p>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="copyLinkInput" value="${response.share_url}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" id="copyLinkBtn">
                                            <i class="fas fa-copy"></i> Копировать
                                        </button>
                                    </div>
                                `);
                                $('#shareModal').modal('hide');
                                $('#shareResultModal').modal('show');
                                
                                // Обработчик копирования ссылки
                                $('#copyLinkBtn').click(function() {
                                    const copyText = document.getElementById("copyLinkInput");
                                    copyText.select();
                                    copyText.setSelectionRange(0, 99999);
                                    document.execCommand("copy");
                                    $(this).html('<i class="fas fa-check"></i> Скопировано');
                                });
                            } else {
                                $('#shareResultModal .modal-title').text('Успех');
                                $('#shareResultModal .modal-body').html(`<p>${response.message}</p>`);
                                $('#shareModal').modal('hide');
                                $('#shareResultModal').modal('show');
                            }
                        } else {
                            $('#shareError').text(response.message).show();
                        }
                    },
                    error: function() {
                        $('#shareError').text('Произошла ошибка при отправке запроса').show();
                    }
                });
            });
            
            // Отображение/скрытие поля email при выборе метода шаринга
            $('#shareMethod').change(function() {
                if ($(this).val() === 'email') {
                    $('#emailGroup').show();
                    $('#shareEmail').prop('required', true);
                } else {
                    $('#emailGroup').hide();
                    $('#shareEmail').prop('required', false);
                }
            });
        });
    </script>
    
    <!-- Модальное окно для выбора метода шаринга -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Поделиться аналитикой</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="shareForm">
                        <div class="alert alert-danger" id="shareError" style="display: none;"></div>
                        
                        <div class="mb-3">
                            <label for="shareMethod" class="form-label">Способ шаринга</label>
                            <select class="form-select" id="shareMethod" name="shareMethod" required>
                                <option value="">Выберите способ</option>
                                <option value="link">Получить ссылку</option>
                                <option value="email">Отправить по email</option>
                                <option value="telegram">Telegram</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="viber">Viber</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="emailGroup" style="display: none;">
                            <label for="shareEmail" class="form-label">Email получателя</label>
                            <input type="email" class="form-control" id="shareEmail" name="shareEmail">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-share-alt"></i> Поделиться
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно с результатом шаринга -->
    <div class="modal fade" id="shareResultModal" tabindex="-1" aria-labelledby="shareResultModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareResultModalLabel">Результат</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Содержимое будет добавлено динамически -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 