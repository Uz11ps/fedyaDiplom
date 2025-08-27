<?php
session_start();
require_once('../config/database_pdo.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit();
}

$count = 0;
$success = false;

try {
    $conn = getConnection();
    
    // Проверка существования таблицы notifications
    $checkTableQuery = "SELECT COUNT(*) as count FROM information_schema.tables 
                       WHERE table_schema = DATABASE() 
                       AND table_name = 'notifications'";
    $checkTableStmt = executeQuery($conn, $checkTableQuery);
    $tableExists = (fetchArray($checkTableStmt)['count'] > 0);
    
    if ($tableExists) {
        // Получение количества непрочитанных уведомлений
        $unreadQuery = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $unreadStmt = executeQuery($conn, $unreadQuery, array($_SESSION['user_id']));
        $count = fetchArray($unreadStmt)['count'];
        $success = true;
    } else {
        $success = false;
    }
    
    closeConnection($conn);
} catch (Exception $e) {
    $success = false;
}

echo json_encode(['success' => $success, 'count' => $count]);
?> 