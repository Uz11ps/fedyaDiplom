<?php
session_start();
require_once('../config/database_pdo.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Получение списка заказов из базы данных
$conn = getConnection();

try {
    // Проверяем наличие необходимых столбцов в таблице orders
    $checkColumnsQuery = "SHOW COLUMNS FROM orders LIKE 'product_name'";
    $checkStmt = $conn->query($checkColumnsQuery);
    $hasProductName = $checkStmt->rowCount() > 0;
    
    $checkColumnsQuery = "SHOW COLUMNS FROM orders LIKE 'order_date'";
    $checkStmt = $conn->query($checkColumnsQuery);
    $hasOrderDate = $checkStmt->rowCount() > 0;
    
    $checkColumnsQuery = "SHOW COLUMNS FROM orders LIKE 'status'";
    $checkStmt = $conn->query($checkColumnsQuery);
    $hasStatus = $checkStmt->rowCount() > 0;
    
    // Создаем столбцы если они отсутствуют
    if (!$hasProductName) {
        $conn->exec("ALTER TABLE orders ADD COLUMN product_name VARCHAR(255) NOT NULL DEFAULT 'Не указано'");
        $hasProductName = true;
    }
    
    if (!$hasOrderDate) {
        $conn->exec("ALTER TABLE orders ADD COLUMN order_date DATE");
        $conn->exec("UPDATE orders SET order_date = CURDATE() WHERE order_date IS NULL");
        $hasOrderDate = true;
    }
    
    if (!$hasStatus) {
        $conn->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'Новый'");
        $hasStatus = true;
    }

    // Инициализируем статистику нулевыми значениями на случай ошибки
    $stats = [
        'total_orders' => 0,
        'total_amount' => 0,
        'unique_clients' => 0,
        'average_amount' => 0
    ];

    // Получение фильтров
    $clientFilter = isset($_GET['client_id']) ? (int)$_GET['client_id'] : '';
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
    $dateFromFilter = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $dateToFilter = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    
    // Формируем базовый запрос
    $query = "SELECT o.id, o.client_id, c.name as client_name, 
                     o.product_name, o.amount, o.order_date, o.status, o.created_at
              FROM orders o
              JOIN clients c ON o.client_id = c.id
              WHERE 1=1";
    
    $params = [];
    
    // Добавляем условия фильтрации
    if (!empty($clientFilter)) {
        $query .= " AND o.client_id = ?";
        $params[] = $clientFilter;
    }
    
    if (!empty($statusFilter)) {
        $query .= " AND o.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($dateFromFilter)) {
        $query .= " AND o.order_date >= ?";
        $params[] = $dateFromFilter;
    }
    
    if (!empty($dateToFilter)) {
        $query .= " AND o.order_date <= ?";
        $params[] = $dateToFilter;
    }
    
    // Сортировка и лимиты
    $query .= " ORDER BY o.order_date DESC";
    
    $stmt = executeQuery($conn, $query, $params);
    
    // Получение результатов
    $orders = [];
    while ($row = fetchArray($stmt)) {
        $orders[] = $row;
    }
    freeStatement($stmt);
    
    // Получаем список клиентов для фильтра
    $clientsQuery = "SELECT id, name FROM clients ORDER BY name ASC";
    $clientsStmt = executeQuery($conn, $clientsQuery);
    $clients = [];
    while ($row = fetchArray($clientsStmt)) {
        $clients[] = $row;
    }
    freeStatement($clientsStmt);
    
    // Получаем уникальные статусы заказов для фильтра
    $statusesQuery = "SELECT DISTINCT status FROM orders WHERE status IS NOT NULL ORDER BY status ASC";
    $statusesStmt = executeQuery($conn, $statusesQuery);
    $statuses = [];
    while ($row = fetchArray($statusesStmt)) {
        $statuses[] = $row['status'];
    }
    freeStatement($statusesStmt);
    
    // Добавляем стандартные статусы если их нет в базе
    $defaultStatuses = ['Новый', 'В обработке', 'Выполнен', 'Отменен'];
    foreach ($defaultStatuses as $status) {
        if (!in_array($status, $statuses)) {
            $statuses[] = $status;
        }
    }
    sort($statuses);
    
    // Получаем суммарную статистику по заказам
    $statsQuery = "SELECT 
                     COUNT(*) as total_orders,
                     IFNULL(SUM(amount), 0) as total_amount,
                     COUNT(DISTINCT client_id) as unique_clients,
                     IFNULL(AVG(amount), 0) as average_amount
                   FROM orders";
    $statsStmt = executeQuery($conn, $statsQuery);
    $stats = fetchArray($statsStmt);
    freeStatement($statsStmt);
    
} catch (Exception $e) {
    $error = "Ошибка при загрузке заказов: " . $e->getMessage();
    
    // Инициализируем статистику нулевыми значениями в случае ошибки
    $stats = [
        'total_orders' => 0,
        'total_amount' => 0,
        'unique_clients' => 0,
        'average_amount' => 0
    ];
} finally {
    closeConnection($conn);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <div class="app-container">
        <?php include_once('../templates/header.php'); ?>
        
        <main class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Управление заказами</h1>
                    <button type="button" class="btn btn-primary" id="newOrderBtn">
                        <i class="fas fa-plus"></i> Новый заказ
                    </button>
                </div>
                
                <!-- Краткая статистика -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white">Всего заказов</h6>
                                        <h2 class="mb-0"><?php echo isset($stats['total_orders']) ? number_format($stats['total_orders']) : 0; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-success text-white shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white">Общая сумма</h6>
                                        <h2 class="mb-0"><?php echo isset($stats['total_amount']) ? number_format($stats['total_amount'], 2, '.', ' ') : "0.00"; ?> ₽</h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-ruble-sign fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-info text-white shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white">Клиентов с заказами</h6>
                                        <h2 class="mb-0"><?php echo isset($stats['unique_clients']) ? number_format($stats['unique_clients']) : 0; ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-users fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-warning text-white shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white">Средний чек</h6>
                                        <h2 class="mb-0"><?php echo isset($stats['average_amount']) ? number_format($stats['average_amount'], 2, '.', ' ') : "0.00"; ?> ₽</h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Фильтры -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Фильтры</h6>
                    </div>
                    <div class="card-body">
                        <form id="filterForm" method="get" class="row g-3">
                            <div class="col-md-3">
                                <label for="client_id" class="form-label">Клиент</label>
                                <select class="form-select" id="client_id" name="client_id">
                                    <option value="">Все клиенты</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>" <?php echo ($clientFilter == $client['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($client['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="status" class="form-label">Статус</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Все статусы</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo ($statusFilter == $status) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Дата с</label>
                                <input type="text" class="form-control datepicker" id="date_from" name="date_from" value="<?php echo $dateFromFilter; ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Дата по</label>
                                <input type="text" class="form-control datepicker" id="date_to" name="date_to" value="<?php echo $dateToFilter; ?>">
                            </div>
                            
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary me-2">Применить фильтры</button>
                                <a href="/pages/orders.php" class="btn btn-secondary">Сбросить</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Таблица заказов -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Список заказов</h6>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download"></i> Экспорт
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="/pages/export_orders.php">Экспорт в Excel</a></li>
                                <li><a class="dropdown-item" href="#" id="printOrders">Печать списка</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php else: ?>
                            <?php if (empty($orders)): ?>
                                <div class="alert alert-info">Заказы не найдены</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="orders-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Клиент</th>
                                                <th>Товар/Услуга</th>
                                                <th>Сумма</th>
                                                <th>Дата заказа</th>
                                                <th>Статус</th>
                                                <th>Дата создания</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td><?php echo $order['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                    <td class="text-end"><?php echo number_format($order['amount'], 2, '.', ' '); ?> ₽</td>
                                                    <td><?php echo date('d.m.Y', strtotime($order['order_date'])); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                                            <?php echo htmlspecialchars($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-primary edit-order" 
                                                                    data-id="<?php echo $order['id']; ?>"
                                                                    data-client="<?php echo $order['client_id']; ?>"
                                                                    data-product="<?php echo htmlspecialchars($order['product_name']); ?>"
                                                                    data-amount="<?php echo $order['amount']; ?>"
                                                                    data-order-date="<?php echo date('Y-m-d', strtotime($order['order_date'])); ?>"
                                                                    data-status="<?php echo htmlspecialchars($order['status']); ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger delete-order" 
                                                                    data-id="<?php echo $order['id']; ?>"
                                                                    data-product="<?php echo htmlspecialchars($order['product_name']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include_once('../templates/footer.php'); ?>
    </div>
    
    <!-- Модальное окно добавления заказа -->
    <div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOrderModalLabel">Новый заказ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addOrderForm">
                        <div class="mb-3">
                            <label for="add_client_id" class="form-label">Клиент</label>
                            <select class="form-select" id="add_client_id" name="client_id" required>
                                <option value="">Выберите клиента</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="add_product_name" class="form-label">Товар/Услуга</label>
                            <input type="text" class="form-control" id="add_product_name" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_amount" class="form-label">Сумма</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="add_amount" name="amount" step="0.01" min="0" required>
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="add_order_date" class="form-label">Дата заказа</label>
                            <input type="text" class="form-control datepicker" id="add_order_date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_status" class="form-label">Статус</label>
                            <select class="form-select" id="add_status" name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo ($status == 'Новый') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="saveOrderBtn">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно редактирования заказа -->
    <div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOrderModalLabel">Редактирование заказа</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editOrderForm">
                        <input type="hidden" id="edit_order_id" name="id">
                        <div class="mb-3">
                            <label for="edit_client_id" class="form-label">Клиент</label>
                            <select class="form-select" id="edit_client_id" name="client_id" required>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_name" class="form-label">Товар/Услуга</label>
                            <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amount" class="form-label">Сумма</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="edit_amount" name="amount" step="0.01" min="0" required>
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_order_date" class="form-label">Дата заказа</label>
                            <input type="text" class="form-control datepicker" id="edit_order_date" name="order_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Статус</label>
                            <select class="form-select" id="edit_status" name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo $status; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="updateOrderBtn">Сохранить изменения</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteOrderModalLabel">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите удалить заказ "<span id="delete_order_name"></span>"?</p>
                    <form id="deleteOrderForm">
                        <input type="hidden" id="delete_order_id" name="id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Удалить</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    <script src="/assets/js/orders.js"></script>
</body>
</html>

<?php
// Вспомогательная функция для определения класса бейджа статуса
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Новый':
            return 'bg-primary';
        case 'В обработке':
            return 'bg-warning';
        case 'Выполнен':
            return 'bg-success';
        case 'Отменен':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?> 