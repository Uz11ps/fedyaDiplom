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

if (empty($input['name'])) {
    echo json_encode(['success' => false, 'message' => 'Не указано название клиента']);
    exit();
}

// Обработка данных
$client_id = (int)$input['id'];
$name = trim($input['name']);
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$category = isset($input['category']) ? trim($input['category']) : 'Розничный';
$status = isset($input['status']) ? trim($input['status']) : 'Активен';

// Проверка email
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Указан некорректный email']);
    exit();
}

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
    
    // Проверка на существование другого клиента с таким email
    if (!empty($email)) {
        $emailCheckQuery = "SELECT COUNT(*) as count FROM clients WHERE email = ? AND id != ?";
        $emailCheckStmt = executeQuery($conn, $emailCheckQuery, [$email, $client_id]);
        $emailCheckResult = fetchArray($emailCheckStmt);
        
        if ($emailCheckResult['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Клиент с таким email уже существует']);
            exit();
        }
    }
    
    // Обновление данных клиента
    $updateQuery = "UPDATE clients SET name = ?, email = ?, phone = ?, category = ?, status = ? WHERE id = ?";
    $updateParams = [$name, $email, $phone, $category, $status, $client_id];
    
    executeQuery($conn, $updateQuery, $updateParams);
    
    // Подготовка данных для ответа
    $client = [
        'id' => $client_id,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'category' => $category,
        'status' => $status
    ];
    
    // Отправка успешного ответа
    echo json_encode([
        'success' => true,
        'message' => 'Данные клиента успешно обновлены',
        'client' => $client
    ]);
    
} catch (Exception $e) {
    // Отправка ответа с ошибкой
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при обновлении данных клиента: ' . $e->getMessage()
    ]);
} finally {
    // Закрытие соединения
    closeConnection($conn);
}
?> 