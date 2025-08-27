<?php
session_start();
require_once('../config/database_pdo.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Получение списка клиентов
$clients = [];
$conn = getConnection();

// Обработка поиска
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
$params = array();

if (!empty($search)) {
    $searchCondition = "AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Обработка фильтрации по категории
$category = isset($_GET['category']) && !empty($_GET['category']) ? $_GET['category'] : '';
$categoryCondition = '';

if (!empty($category)) {
    $categoryCondition = "AND c.category = ?";
    $params[] = $category;
}

// SQL запрос для получения клиентов
$query = "SELECT c.id, c.name, c.email, c.phone, c.category, c.status, c.created_at, 
          COUNT(o.id) as orders_count, IFNULL(SUM(o.amount), 0) as total_amount
          FROM clients c
          LEFT JOIN orders o ON c.id = o.client_id
          WHERE 1=1 $searchCondition $categoryCondition
          GROUP BY c.id, c.name, c.email, c.phone, c.category, c.status, c.created_at
          ORDER BY c.created_at DESC";

$stmt = executeQuery($conn, $query, $params);

if ($stmt === false) {
    die("Ошибка при получении списка клиентов");
}

while ($row = fetchArray($stmt)) {
    $clients[] = $row;
}

freeStatement($stmt);

// Получение категорий клиентов для фильтра
$categoriesQuery = "SELECT DISTINCT category FROM clients ORDER BY category";
$categoriesStmt = executeQuery($conn, $categoriesQuery);

$categories = [];
if ($categoriesStmt !== false) {
    while ($row = fetchArray($categoriesStmt)) {
        $categories[] = $row['category'];
    }
    freeStatement($categoriesStmt);
}

closeConnection($conn);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление клиентами | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <div class="app-container">
        <?php include_once('../templates/header.php'); ?>
        
        <main class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Управление клиентами</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                        <i class="fas fa-plus"></i> Добавить клиента
                    </button>
                </div>
                
                <div class="card shadow mb-4 animate__animated animate__fadeIn">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Список клиентов</h6>
                        <div class="d-flex">
                            <div class="me-2">
                                <form method="GET" action="" class="d-flex">
                                    <select name="category" class="form-control me-2" style="width: auto;">
                                        <option value="">Все категории</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category == $cat) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Фильтр
                                    </button>
                                </form>
                            </div>
                            <form method="GET" action="" class="d-flex search-form">
                                <input type="text" name="search" class="form-control me-2" 
                                       placeholder="Поиск по имени, email, телефону" 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="clientsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Имя клиента</th>
                                        <th>Email</th>
                                        <th>Телефон</th>
                                        <th>Категория</th>
                                        <th>Статус</th>
                                        <th>Заказы</th>
                                        <th>Сумма</th>
                                        <th>Дата создания</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($clients)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">Клиенты не найдены</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?php echo $client['id']; ?></td>
                                        <td><?php echo htmlspecialchars($client['name']); ?></td>
                                        <td><?php echo htmlspecialchars($client['email']); ?></td>
                                        <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?php 
                                                switch($client['category']) {
                                                    case 'VIP': echo 'danger'; break;
                                                    case 'Корпоративный': echo 'primary'; break;
                                                    case 'Партнер': echo 'warning'; break;
                                                    default: echo 'success';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($client['category']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?php 
                                                echo ($client['status'] == 'Активен') ? 'success' : 'secondary';
                                            ?>">
                                                <?php echo htmlspecialchars($client['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $client['orders_count']; ?></td>
                                        <td><?php echo number_format($client['total_amount'] ?? 0, 2, '.', ' '); ?> ₽</td>
                                        <td><?php echo substr($client['created_at'], 0, 10); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary view-client" 
                                                        data-id="<?php echo $client['id']; ?>" 
                                                        data-bs-toggle="tooltip" title="Просмотр">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning edit-client"
                                                        data-id="<?php echo $client['id']; ?>"
                                                        data-bs-toggle="tooltip" title="Редактировать">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-client"
                                                        data-id="<?php echo $client['id']; ?>"
                                                        data-bs-toggle="tooltip" title="Удалить">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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
    
    <!-- Модальное окно добавления клиента -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClientModalLabel">Добавление нового клиента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addClientForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Имя клиента</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Категория</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="Розничный">Розничный</option>
                                <option value="Корпоративный">Корпоративный</option>
                                <option value="Партнер">Партнер</option>
                                <option value="VIP">VIP</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Статус</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Активен">Активен</option>
                                <option value="Неактивен">Неактивен</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="saveClientBtn">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/clients.js"></script>
</body>
</html> 