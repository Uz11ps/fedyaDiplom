<?php
session_start();
require_once('../config/database_pdo.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Подключение к базе данных
$conn = getConnection();

// Получение списка категорий клиентов для фильтра
$categoryQuery = "SELECT DISTINCT category FROM clients ORDER BY category";
$categoryStmt = executeQuery($conn, $categoryQuery);

$categories = [];
if ($categoryStmt !== false) {
    while ($row = fetchArray($categoryStmt)) {
        $categories[] = $row['category'];
    }
}

// Закрытие соединения
closeConnection($conn);

$page_title = "Экспорт данных";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | ООО Аплана.ИТ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
<?php include_once('../templates/header.php'); ?>

<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-file-export"></i> Экспорт данных
            </h1>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Здесь вы можете экспортировать данные в формате Excel для дальнейшего анализа.
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users"></i> Экспорт клиентов
                    </h5>
                </div>
                <div class="card-body">
                    <p>Экспорт списка клиентов с возможностью фильтрации.</p>
                    
                    <form action="/pages/export_clients.php" method="get" class="mb-3">
                        <div class="mb-3">
                            <label for="search" class="form-label">Поиск по имени, email или телефону:</label>
                            <input type="text" class="form-control" id="search" name="search">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Категория клиента:</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Все категории</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-download"></i> Экспортировать клиентов
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart"></i> Экспорт заказов
                    </h5>
                </div>
                <div class="card-body">
                    <p>Экспорт списка заказов с возможностью фильтрации по клиенту.</p>
                    
                    <form action="/pages/export_orders.php" method="get" class="mb-3">
                        <div class="mb-3">
                            <label for="client_id" class="form-label">ID клиента (опционально):</label>
                            <input type="number" class="form-control" id="client_id" name="client_id" min="1">
                            <small class="form-text text-muted">Оставьте пустым для экспорта всех заказов</small>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-download"></i> Экспортировать заказы
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie"></i> Экспорт статистики
                    </h5>
                </div>
                <div class="card-body">
                    <p>Экспорт обобщенной статистики по клиентам и заказам.</p>
                    
                    <form action="/pages/export_statistics.php" method="get" class="mb-3">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Начальная дата:</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="end_date" class="form-label">Конечная дата:</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-info w-100 text-white">
                            <i class="fas fa-download"></i> Экспортировать статистику
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('../templates/footer.php'); ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</body>
</html> 