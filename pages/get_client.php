<?php
session_start();
require_once('../config/database_pdo.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit();
}

// Проверка наличия ID клиента
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Не указан ID клиента']);
    exit();
}

$client_id = (int)$_GET['id'];

// Подключение к базе данных
$conn = getConnection();

try {
    // Получение информации о клиенте
    $clientQuery = "SELECT * FROM clients WHERE id = ?";
    $clientStmt = executeQuery($conn, $clientQuery, [$client_id]);
    
    if (!hasRows($clientStmt)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Клиент не найден']);
        exit();
    }
    
    $client = fetchArray($clientStmt);
    
    // Проверяем наличие необходимых столбцов в таблице orders
    $checkColumnsQuery = "SHOW COLUMNS FROM orders LIKE 'product_name'";
    $checkStmt = $conn->query($checkColumnsQuery);
    $hasProductName = $checkStmt->rowCount() > 0;
    
    $checkColumnsQuery = "SHOW COLUMNS FROM orders LIKE 'order_date'";
    $checkStmt = $conn->query($checkColumnsQuery);
    $hasOrderDate = $checkStmt->rowCount() > 0;
    
    // Получение заказов клиента
    $ordersQuery = "SELECT id, ";
    
    // Добавляем столбцы, проверяя их наличие
    if ($hasProductName) {
        $ordersQuery .= "product_name, ";
    } else {
        $ordersQuery .= "'Не указано' as product_name, ";
    }
    
    if ($hasOrderDate) {
        $ordersQuery .= "order_date, ";
    } else {
        $ordersQuery .= "DATE(created_at) as order_date, ";
    }
    
    $ordersQuery .= "amount FROM orders WHERE client_id = ? ";
    
    // Сортировка по дате
    if ($hasOrderDate) {
        $ordersQuery .= "ORDER BY order_date DESC";
    } else {
        $ordersQuery .= "ORDER BY created_at DESC";
    }
    
    $ordersStmt = executeQuery($conn, $ordersQuery, [$client_id]);
    
    $orders = [];
    while ($row = fetchArray($ordersStmt)) {
        $orders[] = $row;
    }
    
    // Добавление заказов к ответу
    $client['orders'] = $orders;
    
    // Отправка успешного ответа
    echo json_encode([
        'success' => true,
        'client' => $client
    ]);
    
} catch (Exception $e) {
    // Отправка ответа с ошибкой
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при получении данных клиента: ' . $e->getMessage()
    ]);
} finally {
    // Закрытие соединения
    closeConnection($conn);
}
?> 