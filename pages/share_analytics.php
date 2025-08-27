<?php
session_start();
require_once('../config/database_pdo.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit();
}

// Получение параметров
$share_method = isset($_POST['share_method']) ? $_POST['share_method'] : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$period = isset($_POST['period']) ? $_POST['period'] : '30days';

// Проверка параметров
if (empty($share_method)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных для шаринга']);
    exit();
}

// Получение данных аналитики
$conn = getConnection();
$data = [];

try {
    // Определение периода
    $date_period = "30 DAY";
    if ($period == "90days") {
        $date_period = "90 DAY";
    } elseif ($period == "year") {
        $date_period = "1 YEAR";
    }
    
    // Основные показатели
    $kpiQuery = "SELECT 
                COUNT(DISTINCT client_id) as active_clients,
                COUNT(*) as orders_count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
             FROM orders
             WHERE order_date >= DATE_SUB(NOW(), INTERVAL $date_period)";
    
    $kpiStmt = executeQuery($conn, $kpiQuery);
    $data['kpi'] = fetchArray($kpiStmt);
    
    // Статистика по категориям
    $categoryQuery = "SELECT c.category, COUNT(o.id) as orders_count, IFNULL(SUM(o.amount), 0) as total_amount
                     FROM clients c
                     LEFT JOIN orders o ON c.id = o.client_id 
                     WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL $date_period) OR o.order_date IS NULL
                     GROUP BY c.category
                     ORDER BY total_amount DESC";
    
    $categoryStmt = executeQuery($conn, $categoryQuery);
    $data['categories'] = [];
    
    while ($row = fetchArray($categoryStmt)) {
        $data['categories'][] = $row;
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ошибка при получении данных: ' . $e->getMessage()]);
    exit();
} finally {
    closeConnection($conn);
}

// Формирование ссылки для шаринга
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$share_url = $base_url . '/pages/analytics.php?period=' . $period . '&shared=1';

// Формирование текста для шаринга
$share_text = 'Аналитика ООО "Аплана.ИТ"' . "\n\n";
$share_text .= 'Период: ' . ($period == '30days' ? '30 дней' : ($period == '90days' ? '90 дней' : 'Год')) . "\n";
$share_text .= 'Активных клиентов: ' . $data['kpi']['active_clients'] . "\n";
$share_text .= 'Количество заказов: ' . $data['kpi']['orders_count'] . "\n";
$share_text .= 'Общая сумма: ' . number_format($data['kpi']['total_amount'], 2, '.', ' ') . ' ₽' . "\n";
$share_text .= 'Средний чек: ' . number_format($data['kpi']['avg_amount'], 2, '.', ' ') . ' ₽' . "\n\n";

$share_text .= 'Статистика по категориям:' . "\n";
foreach ($data['categories'] as $category) {
    $share_text .= '- ' . $category['category'] . ': ' . number_format($category['total_amount'], 2, '.', ' ') . ' ₽ (' . $category['orders_count'] . ' заказов)' . "\n";
}

// Обработка метода шаринга
$result = ['success' => false, 'message' => 'Не удалось отправить данные'];

switch ($share_method) {
    case 'email':
        // Проверяем наличие email
        if (empty($email)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Email не указан']);
            exit();
        }
        
        // В реальном проекте здесь бы использовалась библиотека для отправки email
        // Имитация отправки email
        $success = true;
        
        if ($success) {
            $result = [
                'success' => true, 
                'message' => 'Данные успешно отправлены на ' . $email,
                'share_url' => $share_url
            ];
        }
        break;
        
    case 'link':
        // Генерация ссылки для копирования
        $result = [
            'success' => true,
            'message' => 'Ссылка успешно создана',
            'share_url' => $share_url,
            'share_text' => $share_text
        ];
        break;
        
    case 'telegram':
    case 'whatsapp':
    case 'viber':
        // Формирование URL для шаринга в мессенджеры
        $encoded_text = urlencode($share_text . "\n\nПодробнее: " . $share_url);
        
        $app_url = '';
        if ($share_method === 'telegram') {
            $app_url = 'https://t.me/share/url?url=' . urlencode($share_url) . '&text=' . urlencode($share_text);
        } elseif ($share_method === 'whatsapp') {
            $app_url = 'https://wa.me/?text=' . $encoded_text;
        } elseif ($share_method === 'viber') {
            $app_url = 'viber://forward?text=' . $encoded_text;
        }
        
        $result = [
            'success' => true,
            'message' => 'Перенаправление в ' . ucfirst($share_method),
            'share_url' => $app_url,
            'redirect' => true
        ];
        break;
        
    default:
        $result = ['success' => false, 'message' => 'Неизвестный метод шаринга'];
}

// Возвращаем результат
header('Content-Type: application/json');
echo json_encode($result);
exit();
?> 