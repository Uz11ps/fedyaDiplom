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
if (empty($input['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID клиента']);
    exit();
}

$client_id = (int)$input['client_id'];
$product_name = isset($input['product_name']) ? trim($input['product_name']) : null;
$amount = isset($input['amount']) ? (float)$input['amount'] : null;
$order_date = isset($input['order_date']) ? trim($input['order_date']) : date('Y-m-d');
$status = isset($input['status']) ? trim($input['status']) : 'Новый';

// Проверка обязательных полей
if (empty($product_name) || $amount === null) {
    echo json_encode(['success' => false, 'message' => 'Необходимо указать название продукта и сумму']);
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
    
    // Проверка существования таблицы заказов
    $checkTableQuery = "SELECT COUNT(*) as count FROM information_schema.tables 
                      WHERE table_schema = DATABASE() 
                      AND table_name = 'orders'";
    $checkTableStmt = executeQuery($conn, $checkTableQuery);
    $tableExists = fetchArray($checkTableStmt)['count'] > 0;
    
    if (!$tableExists) {
        // Создание таблицы заказов, если она не существует
        $createTableQuery = "CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            order_date DATE NOT NULL,
            status VARCHAR(50) DEFAULT 'Новый',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        )";
        executeQuery($conn, $createTableQuery);
    } else {
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
        
        // Если нужных столбцов нет, добавляем их
        if (!$hasProductName) {
            $alterQuery = "ALTER TABLE orders ADD COLUMN product_name VARCHAR(255) NOT NULL DEFAULT 'Не указано'";
            $conn->exec($alterQuery);
        }
        
        if (!$hasOrderDate) {
            $alterQuery = "ALTER TABLE orders ADD COLUMN order_date DATE DEFAULT CURRENT_DATE";
            $conn->exec($alterQuery);
        }
        
        if (!$hasStatus) {
            $alterQuery = "ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'Новый'";
            $conn->exec($alterQuery);
        }
    }
    
    // Добавление нового заказа
    $insertQuery = "INSERT INTO orders (client_id, product_name, amount, order_date, status) VALUES (?, ?, ?, ?, ?)";
    $insertParams = [$client_id, $product_name, $amount, $order_date, $status];
    executeQuery($conn, $insertQuery, $insertParams);
    
    // Получение ID добавленного заказа
    $orderId = $conn->lastInsertId();
    
    // Получение имени клиента
    $clientQuery = "SELECT name FROM clients WHERE id = ?";
    $clientStmt = executeQuery($conn, $clientQuery, [$client_id]);
    $client = fetchArray($clientStmt);
    $clientName = $client['name'];
    
    // Отправка успешного ответа
    echo json_encode([
        'success' => true,
        'message' => 'Заказ успешно добавлен',
        'order' => [
            'id' => $orderId,
            'client_id' => $client_id,
            'client_name' => $clientName,
            'product_name' => $product_name,
            'amount' => $amount,
            'order_date' => $order_date,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Отправка ответа с ошибкой
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при добавлении заказа: ' . $e->getMessage()
    ]);
} finally {
    // Закрытие соединения
    closeConnection($conn);
}
?> 