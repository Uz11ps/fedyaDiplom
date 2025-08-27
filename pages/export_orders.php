<?php
session_start();
require_once('../config/database_pdo.php');
require_once('../utils/ExcelExport.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Получение ID клиента (если указан)
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;

// Подключение к базе данных
$conn = getConnection();

try {
    // Проверяем наличие необходимых столбцов в таблице orders
    $checkColumnsQuery = "SHOW COLUMNS FROM orders LIKE 'product_name'";
    $checkStmt = $conn->query($checkColumnsQuery);
    $hasProductName = $checkStmt->rowCount() > 0;
    
    $checkColumnsQuery = "SHOW COLUMNS FROM orders LIKE 'order_date'";
    $checkStmt = $conn->query($checkColumnsQuery);
    $hasOrderDate = $checkStmt->rowCount() > 0;
    
    // SQL запрос для получения заказов
    $query = "SELECT o.id, ";
    
    // Добавляем столбцы, проверяя их наличие
    if ($hasProductName) {
        $query .= "o.product_name, ";
    } else {
        $query .= "'Не указано' as product_name, ";
    }
    
    $query .= "o.amount, ";
    
    if ($hasOrderDate) {
        $query .= "o.order_date, ";
    } else {
        $query .= "DATE(o.created_at) as order_date, ";
    }
    
    $query .= "o.created_at, 
               c.id as client_id, c.name as client_name
        FROM orders o
        JOIN clients c ON o.client_id = c.id";
    
    $params = array();
    
    // Если указан ID клиента, фильтруем заказы
    if ($client_id) {
        $query .= " WHERE o.client_id = ?";
        $params[] = $client_id;
    }
    
    if ($hasOrderDate) {
        $query .= " ORDER BY o.order_date DESC";
    } else {
        $query .= " ORDER BY o.created_at DESC";
    }
    
    $stmt = executeQuery($conn, $query, $params);
    
    if ($stmt === false) {
        die("Ошибка при получении списка заказов");
    }
    
    // Получение заказов
    $orders = array();
    while ($row = fetchArray($stmt)) {
        $orders[] = $row;
    }
    
    // Закрытие соединения
    freeStatement($stmt);
    closeConnection($conn);
    
    // Подготовка данных для экспорта
    $exportData = ExcelExport::prepareOrdersData($orders);
    
    // Экспорт в Excel
    ExcelExport::export('orders_export', $exportData['headers'], $exportData['data'], $exportData['formats']);
} catch (Exception $e) {
    echo "Произошла ошибка: " . $e->getMessage();
    exit();
}
?> 