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
    echo json_encode(['success' => false, 'message' => 'Не указан ID клиента']);
    exit();
}

$client_id = (int)$input['id'];

// Подключение к базе данных
$conn = getConnection();

try {
    // Проверка существования клиента
    $checkQuery = "SELECT COUNT(*) as count FROM clients WHERE id = ?";
    $checkStmt = executeQuery($conn, $checkQuery, [$client_id]);
    $checkResult = fetchArray($checkStmt);
    
    if ($checkResult['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Клиент не найден']);
        exit();
    }
    
    // Перед удалением клиента удаляем его заказы
    $deleteOrdersQuery = "DELETE FROM orders WHERE client_id = ?";
    executeQuery($conn, $deleteOrdersQuery, [$client_id]);
    
    // Удаление клиента
    $deleteQuery = "DELETE FROM clients WHERE id = ?";
    executeQuery($conn, $deleteQuery, [$client_id]);
    
    // Проверка, сколько клиентов осталось в таблице
    $countQuery = "SELECT COUNT(*) as count FROM clients";
    $countStmt = executeQuery($conn, $countQuery);
    $remainingClients = fetchArray($countStmt)['count'];
    
    // Если клиентов не осталось, сбрасываем AUTO_INCREMENT
    if ($remainingClients == 0) {
        $resetAutoIncrementQuery = "ALTER TABLE clients AUTO_INCREMENT = 1";
        executeQuery($conn, $resetAutoIncrementQuery);
    }
    
    // Отправка успешного ответа
    echo json_encode([
        'success' => true,
        'message' => 'Клиент успешно удален',
        'id' => $client_id
    ]);
    
} catch (Exception $e) {
    // Отправка ответа с ошибкой
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при удалении клиента: ' . $e->getMessage()
    ]);
} finally {
    // Закрытие соединения
    closeConnection($conn);
}
?> 