<?php
session_start();
require_once('../config/database_pdo.php');
require_once('../utils/ExcelExport.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Подключение к базе данных
$conn = getConnection();

try {
    // Обработка параметров фильтрации
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    
    $searchCondition = '';
    $params = array();
    
    if (!empty($search)) {
        $searchCondition = "AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
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
              ORDER BY c.name ASC";
    
    $stmt = executeQuery($conn, $query, $params);
    
    if ($stmt === false) {
        die("Ошибка при получении списка клиентов");
    }
    
    // Получение клиентов
    $clients = array();
    while ($row = fetchArray($stmt)) {
        $clients[] = $row;
    }
    
    // Закрытие соединения
    freeStatement($stmt);
    closeConnection($conn);
    
    // Подготовка данных для экспорта
    $exportData = ExcelExport::prepareClientsData($clients);
    
    // Экспорт в Excel
    ExcelExport::export('clients_export', $exportData['headers'], $exportData['data'], $exportData['formats']);
} catch (Exception $e) {
    echo "Произошла ошибка: " . $e->getMessage();
    exit();
}
?> 