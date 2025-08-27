<?php
session_start();
require_once('../config/database_pdo.php');
require_once('../utils/ExcelExport.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Период отчета (по умолчанию - текущий месяц)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Подключение к базе данных
$conn = getConnection();

try {
    // Проверяем наличие необходимых столбцов в таблице orders
    $checkColumnsQuery = "SHOW COLUMNS FROM orders LIKE 'order_date'";
    $checkStmt = $conn->query($checkColumnsQuery);
    $hasOrderDate = $checkStmt->rowCount() > 0;
    
    // Получение статистики по категориям клиентов
    $categoryQuery = "SELECT 
                      c.category, 
                      COUNT(DISTINCT c.id) as clients_count,
                      COUNT(o.id) as orders_count,
                      IFNULL(SUM(o.amount), 0) as total_amount
                    FROM clients c
                    LEFT JOIN orders o ON c.id = o.client_id";
    
    if ($hasOrderDate) {
        $categoryQuery .= " AND o.order_date BETWEEN ? AND ?";
    } else {
        $categoryQuery .= " AND DATE(o.created_at) BETWEEN ? AND ?";
    }
    
    $categoryQuery .= " GROUP BY c.category
                        ORDER BY total_amount DESC";
    
    $categoryStmt = executeQuery($conn, $categoryQuery, [$start_date, $end_date]);
    
    if ($categoryStmt === false) {
        die("Ошибка при получении статистики по категориям");
    }
    
    $categoryStats = [];
    while ($row = fetchArray($categoryStmt)) {
        $categoryStats[] = $row;
    }
    freeStatement($categoryStmt);
    
    // Получение статистики по месяцам
    $monthlyQuery = "SELECT ";
    
    if ($hasOrderDate) {
        $monthlyQuery .= "DATE_FORMAT(o.order_date, '%Y-%m') as month,";
    } else {
        $monthlyQuery .= "DATE_FORMAT(o.created_at, '%Y-%m') as month,";
    }
    
    $monthlyQuery .= "COUNT(DISTINCT o.client_id) as clients_count,
                      COUNT(o.id) as orders_count,
                      IFNULL(SUM(o.amount), 0) as total_amount
                    FROM orders o
                    WHERE ";
    
    if ($hasOrderDate) {
        $monthlyQuery .= "o.order_date BETWEEN ? AND ?";
    } else {
        $monthlyQuery .= "DATE(o.created_at) BETWEEN ? AND ?";
    }
    
    $monthlyQuery .= " GROUP BY month
                      ORDER BY month DESC";
    
    $monthlyStmt = executeQuery($conn, $monthlyQuery, [$start_date, $end_date]);
    
    if ($monthlyStmt === false) {
        die("Ошибка при получении статистики по месяцам");
    }
    
    $monthlyStats = [];
    while ($row = fetchArray($monthlyStmt)) {
        $monthlyStats[] = $row;
    }
    freeStatement($monthlyStmt);
    
    // Закрытие соединения
    closeConnection($conn);
    
    // Подготовка данных для экспорта
    $headers = [
        'Категория клиентов',
        'Количество клиентов',
        'Количество заказов',
        'Общая сумма заказов'
    ];
    
    $data = [];
    foreach ($categoryStats as $stat) {
        $data[] = [
            $stat['category'],
            $stat['clients_count'],
            $stat['orders_count'],
            $stat['total_amount']
        ];
    }
    
    // Добавляем статистику по месяцам
    $data[] = ['', '', '', ''];  // Пустая строка-разделитель
    $data[] = ['Статистика по месяцам', '', '', ''];
    $data[] = ['Месяц', 'Количество клиентов', 'Количество заказов', 'Общая сумма заказов'];
    
    foreach ($monthlyStats as $stat) {
        $data[] = [
            $stat['month'],
            $stat['clients_count'],
            $stat['orders_count'],
            $stat['total_amount']
        ];
    }
    
    $columnFormats = [
        1 => 'number',  // Количество клиентов
        2 => 'number',  // Количество заказов
        3 => 'number',  // Общая сумма заказов
    ];
    
    // Экспорт в Excel
    ExcelExport::export('statistics_export', $headers, $data, $columnFormats);
} catch (Exception $e) {
    echo "Произошла ошибка: " . $e->getMessage();
    exit();
}
?> 