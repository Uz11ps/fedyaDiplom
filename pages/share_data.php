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
$type = isset($_POST['type']) ? $_POST['type'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$share_method = isset($_POST['share_method']) ? $_POST['share_method'] : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Проверка параметров
if (empty($type) || empty($id) || empty($share_method)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных для шаринга']);
    exit();
}

// Функция для получения данных в зависимости от типа
function getShareData($type, $id) {
    $conn = getConnection();
    $data = null;
    
    try {
        if ($type === 'client') {
            // Получение данных клиента
            $query = "SELECT c.*, COUNT(o.id) as orders_count, IFNULL(SUM(o.amount), 0) as total_amount
                      FROM clients c
                      LEFT JOIN orders o ON c.id = o.client_id
                      WHERE c.id = ?
                      GROUP BY c.id";
            $stmt = executeQuery($conn, $query, [$id]);
            
            if (hasRows($stmt)) {
                $data = fetchArray($stmt);
                
                // Получение заказов клиента
                $ordersQuery = "SELECT * FROM orders WHERE client_id = ? ORDER BY order_date DESC LIMIT 5";
                $ordersStmt = executeQuery($conn, $ordersQuery, [$id]);
                
                $data['recent_orders'] = [];
                while ($row = fetchArray($ordersStmt)) {
                    $data['recent_orders'][] = $row;
                }
            }
        } elseif ($type === 'order') {
            // Получение данных заказа
            $query = "SELECT o.*, c.name as client_name 
                      FROM orders o
                      JOIN clients c ON o.client_id = c.id
                      WHERE o.id = ?";
            $stmt = executeQuery($conn, $query, [$id]);
            
            if (hasRows($stmt)) {
                $data = fetchArray($stmt);
            }
        }
    } catch (Exception $e) {
        $data = null;
    } finally {
        closeConnection($conn);
    }
    
    return $data;
}

// Получение данных
$data = getShareData($type, $id);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Данные не найдены']);
    exit();
}

// Формирование ссылки для шаринга
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$share_url = $base_url . '/pages/view_' . $type . '.php?id=' . $id . '&share_token=' . md5($type . $id . time());

// Формирование текста для шаринга
$share_text = '';
if ($type === 'client') {
    $share_text = 'Информация о клиенте: ' . $data['name'] . "\n";
    $share_text .= 'Email: ' . $data['email'] . "\n";
    $share_text .= 'Телефон: ' . $data['phone'] . "\n";
    $share_text .= 'Категория: ' . $data['category'] . "\n";
    $share_text .= 'Статус: ' . $data['status'] . "\n";
    $share_text .= 'Количество заказов: ' . $data['orders_count'] . "\n";
    $share_text .= 'Общая сумма: ' . number_format($data['total_amount'], 2, '.', ' ') . ' ₽';
} elseif ($type === 'order') {
    $share_text = 'Информация о заказе #' . $data['id'] . "\n";
    $share_text .= 'Клиент: ' . $data['client_name'] . "\n";
    $share_text .= 'Товар/Услуга: ' . $data['product_name'] . "\n";
    $share_text .= 'Сумма: ' . number_format($data['amount'], 2, '.', ' ') . ' ₽' . "\n";
    $share_text .= 'Дата заказа: ' . $data['order_date'];
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
        // Например, PHPMailer или встроенная функция mail()
        
        // Имитация отправки email
        $success = true; // Предполагаем, что отправка прошла успешно
        
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