<?php
session_start();
require_once('config/database_pdo.php');

// Проверка авторизации (опционально)
if (!isset($_SESSION['user_id'])) {
    echo "Требуется авторизация. <a href='/pages/login.php'>Войти</a>";
    exit();
}

// Подключение к базе данных
$conn = getConnection();

try {
    // Проверяем наличие колонки product_name
    $checkColumnQuery = "SELECT COUNT(*) as count 
                        FROM information_schema.columns 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'orders' 
                        AND column_name = 'product_name'";
    $checkStmt = executeQuery($conn, $checkColumnQuery);
    $productColumnExists = fetchArray($checkStmt)['count'] > 0;
    
    // Проверяем наличие колонки order_date
    $checkOrderDateQuery = "SELECT COUNT(*) as count 
                          FROM information_schema.columns 
                          WHERE table_schema = DATABASE() 
                          AND table_name = 'orders' 
                          AND column_name = 'order_date'";
    $checkOrderDateStmt = executeQuery($conn, $checkOrderDateQuery);
    $orderDateColumnExists = fetchArray($checkOrderDateStmt)['count'] > 0;
    
    $updatesApplied = false;
    
    // Добавляем колонку product_name, если её нет
    if (!$productColumnExists) {
        $alterQuery = "ALTER TABLE orders ADD COLUMN product_name VARCHAR(255) NOT NULL DEFAULT 'Товар/Услуга'";
        executeQuery($conn, $alterQuery);
        echo "Добавлена колонка product_name в таблицу orders.<br>";
        $updatesApplied = true;
    }
    
    // Добавляем колонку order_date, если её нет
    if (!$orderDateColumnExists) {
        $alterQuery = "ALTER TABLE orders ADD COLUMN order_date DATE DEFAULT CURRENT_DATE";
        executeQuery($conn, $alterQuery);
        echo "Добавлена колонка order_date в таблицу orders.<br>";
        $updatesApplied = true;
    }
    
    // Заполняем product_name случайными значениями для существующих заказов
    if ($updatesApplied) {
        $products = [
            'Консультация', 
            'Разработка сайта', 
            'Обслуживание сервера', 
            'Техническая поддержка', 
            'SEO-продвижение', 
            'Контекстная реклама'
        ];
        
        $ordersQuery = "SELECT id FROM orders";
        $ordersStmt = executeQuery($conn, $ordersQuery);
        
        while ($order = fetchArray($ordersStmt)) {
            $randomProduct = $products[array_rand($products)];
            $updateQuery = "UPDATE orders SET product_name = ? WHERE id = ?";
            executeQuery($conn, $updateQuery, [$randomProduct, $order['id']]);
        }
        
        echo "Заполнены имена продуктов для существующих заказов.<br>";
    }
    
    echo "<br>Обновление структуры таблицы orders завершено успешно.<br>";
    echo "<a href='/pages/main.php'>Вернуться на главную</a>";
    
} catch (Exception $e) {
    echo "Ошибка при обновлении структуры таблицы: " . $e->getMessage();
} finally {
    closeConnection($conn);
}
?> 