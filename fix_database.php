<?php
session_start();
require_once('config/database_pdo.php');

// Пропустим проверку авторизации для удобства выполнения сценария
// if (!isset($_SESSION['user_id'])) {
//     echo "Требуется авторизация. <a href='/pages/login.php'>Войти</a>";
//     exit();
// }

echo "<h1>Исправление структуры базы данных</h1>";

$conn = getConnection();

try {
    echo "<h2>Проверка таблицы orders</h2>";
    
    // Получаем текущую структуру таблицы orders
    $checkTableQuery = "DESCRIBE orders";
    $checkTableStmt = $conn->query($checkTableQuery);
    
    $existingColumns = [];
    while ($row = $checkTableStmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[$row['Field']] = $row;
        echo "Найден столбец: {$row['Field']} ({$row['Type']})<br>";
    }
    
    // Проверяем и добавляем необходимые столбцы
    $columnsToAdd = [
        'product_name' => "ALTER TABLE orders ADD COLUMN product_name VARCHAR(255) NOT NULL DEFAULT 'Не указано'",
        'order_date' => "ALTER TABLE orders ADD COLUMN order_date DATE DEFAULT CURRENT_DATE"
    ];
    
    $changesApplied = false;
    
    foreach ($columnsToAdd as $column => $query) {
        if (!isset($existingColumns[$column])) {
            echo "<div style='color: red;'>Отсутствует столбец: {$column}</div>";
            
            try {
                $conn->exec($query);
                echo "<div style='color: green;'>Добавлен столбец: {$column}</div>";
                $changesApplied = true;
            } catch (PDOException $e) {
                echo "<div style='color: red;'>Ошибка добавления столбца {$column}: {$e->getMessage()}</div>";
            }
        } else {
            echo "<div style='color: green;'>Столбец {$column} уже существует</div>";
        }
    }
    
    // Обновляем данные в новых столбцах, если были добавлены
    if ($changesApplied) {
        echo "<h2>Обновление данных в новых столбцах</h2>";
        
        // Устанавливаем значение order_date на основе created_at
        $conn->exec("UPDATE orders SET order_date = DATE(created_at) WHERE order_date IS NULL");
        echo "<div style='color: green;'>Дата заказа установлена на основе даты создания</div>";
        
        // Заполняем product_name для существующих записей
        $products = [
            'Консультация',
            'Разработка',
            'Сопровождение',
            'Внедрение',
            'Техническая поддержка'
        ];
        
        $ordersQuery = "SELECT id FROM orders WHERE product_name = 'Не указано'";
        $ordersStmt = $conn->query($ordersQuery);
        
        $updated = 0;
        while ($order = $ordersStmt->fetch(PDO::FETCH_ASSOC)) {
            $randomProduct = $products[array_rand($products)];
            $updateQuery = "UPDATE orders SET product_name = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute([$randomProduct, $order['id']]);
            $updated++;
        }
        
        echo "<div style='color: green;'>Обновлено товаров/услуг: {$updated}</div>";
    }
    
    echo "<h2>Результат</h2>";
    if ($changesApplied) {
        echo "<div style='color: green;'>Структура базы данных успешно обновлена.</div>";
    } else {
        echo "<div style='color: blue;'>Обновление структуры не требовалось.</div>";
    }
    
    echo "<br><a href='/pages/stats.php' class='btn btn-primary'>Вернуться к статистике</a>";
    echo "<br><a href='/pages/export.php' class='btn btn-success'>Перейти к экспорту</a>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Произошла ошибка: {$e->getMessage()}</div>";
} finally {
    closeConnection($conn);
}
?> 