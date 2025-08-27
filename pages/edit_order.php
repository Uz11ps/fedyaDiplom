<?php
session_start();
require_once('../config/database_pdo.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit();
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit();
}

// Получение данных из запроса
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Если данные не пришли как JSON, пробуем получить из POST
    $input = $_POST;
}

// Валидация данных
if (empty($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID заказа']);
    exit();
}

$order_id = (int)$input['id'];
$client_id = isset($input['client_id']) ? (int)$input['client_id'] : null;
$product_name = isset($input['product_name']) ? trim($input['product_name']) : null;
$amount = isset($input['amount']) ? (float)$input['amount'] : null;
$order_date = isset($input['order_date']) ? trim($input['order_date']) : null;
$status = isset($input['status']) ? trim($input['status']) : 'Новый';

// Проверяем и форматируем дату
if (!empty($order_date)) {
    // Попытка преобразовать дату в нужный формат
    $date = new DateTime($order_date);
    $order_date = $date->format('Y-m-d');
}

// Проверка обязательных полей
if (empty($product_name) || $amount === null || empty($order_date)) {
    echo json_encode(['success' => false, 'message' => 'Все поля обязательны для заполнения']);
    exit();
}

// Подключение к базе данных
$conn = getConnection();

try {
    // Проверка существования заказа
    $checkQuery = "SELECT COUNT(*) as count FROM orders WHERE id = ?";
    $checkStmt = executeQuery($conn, $checkQuery, [$order_id]);
    $checkResult = fetchArray($checkStmt);
    
    if ($checkResult['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
        exit();
    }

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
    
    // Если столбцов нет, добавляем их
    if (!$hasProductName) {
        $conn->exec("ALTER TABLE orders ADD COLUMN product_name VARCHAR(255) NOT NULL DEFAULT 'Не указано'");
        $hasProductName = true;
    }
    
    if (!$hasOrderDate) {
        $conn->exec("ALTER TABLE orders ADD COLUMN order_date DATE DEFAULT CURRENT_DATE");
        $hasOrderDate = true;
    }
    
    if (!$hasStatus) {
        $conn->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'Новый'");
        $hasStatus = true;
    }
    
    // Формируем запрос в зависимости от наличия столбцов
    $updateFields = [];
    $updateParams = [];
    
    // Добавляем client_id, если он предоставлен
    if ($client_id) {
        $updateFields[] = "client_id = ?";
        $updateParams[] = $client_id;
    }
    
    if ($hasProductName) {
        $updateFields[] = "product_name = ?";
        $updateParams[] = $product_name;
    }
    
    $updateFields[] = "amount = ?";
    $updateParams[] = $amount;
    
    if ($hasOrderDate) {
        $updateFields[] = "order_date = ?";
        $updateParams[] = $order_date;
        // Обновляем created_at для корректной работы с графиками
        $updateFields[] = "created_at = NOW()";
    }
    
    if ($hasStatus) {
        $updateFields[] = "status = ?";
        $updateParams[] = $status;
    }
    
    $updateParams[] = $order_id;
    
    // Обновление данных заказа
    $updateQuery = "UPDATE orders SET " . implode(", ", $updateFields) . " WHERE id = ?";
    executeQuery($conn, $updateQuery, $updateParams);
    
    // Получаем обновленный заказ
    $selectQuery = "SELECT o.id, o.client_id, c.name as client_name, o.product_name, o.amount, o.order_date, o.status
                   FROM orders o
                   JOIN clients c ON o.client_id = c.id
                   WHERE o.id = ?";
    $selectStmt = executeQuery($conn, $selectQuery, [$order_id]);
    $order = fetchArray($selectStmt);
    
    // Отправка успешного ответа
    echo json_encode([
        'success' => true,
        'message' => 'Заказ успешно обновлен',
        'order' => $order
    ]);
    
} catch (Exception $e) {
    // Отправка ответа с ошибкой
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при обновлении заказа: ' . $e->getMessage()
    ]);
} finally {
    // Закрытие соединения
    closeConnection($conn);
}
?> 