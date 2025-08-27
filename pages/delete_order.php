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
    
    // Удаление заказа
    $deleteQuery = "DELETE FROM orders WHERE id = ?";
    executeQuery($conn, $deleteQuery, [$order_id]);
    
    // Отправка успешного ответа
    echo json_encode([
        'success' => true,
        'message' => 'Заказ успешно удален',
        'id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Отправка ответа с ошибкой
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при удалении заказа: ' . $e->getMessage()
    ]);
} finally {
    // Закрытие соединения
    closeConnection($conn);
}
?> 