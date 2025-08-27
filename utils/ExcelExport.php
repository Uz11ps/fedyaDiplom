<?php
/**
 * Класс для экспорта данных в Excel
 */
class ExcelExport {
    /**
     * Экспортирует данные в формате Excel
     * 
     * @param string $filename Имя файла для экспорта
     * @param array $headers Заголовки таблицы
     * @param array $data Данные для экспорта
     * @param array $columnFormats Форматы для столбцов (ключ - индекс столбца, значение - формат)
     */
    public static function export($filename, $headers, $data, $columnFormats = []) {
        // Проверка, не отправлен ли уже вывод
        if (headers_sent($file, $line)) {
            die("Ошибка: заголовки уже отправлены в $file на строке $line");
        }
        
        // Заголовки для скачивания файла Excel
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '_' . date('Y-m-d') . '.xls');
        header('Pragma: no-cache');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        
        // Формирование стилей для таблицы
        $css = '
            <style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                th {
                    background-color: #4CAF50;
                    color: white;
                    font-weight: bold;
                    text-align: center;
                    padding: 5px;
                }
                td {
                    border: 1px solid #ddd;
                    padding: 5px;
                }
                tr:nth-child(even) {
                    background-color: #f2f2f2;
                }
                .number {
                    text-align: right;
                }
                .date {
                    text-align: center;
                }
                .text {
                    text-align: left;
                }
            </style>
        ';
        
        // Создание файла Excel
        echo '<!DOCTYPE html>
        <html xmlns:o="urn:schemas-microsoft-com:office:office" 
              xmlns:x="urn:schemas-microsoft-com:office:excel" 
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <meta name="ProgId" content="Excel.Sheet">
            ' . $css . '
        </head>
        <body>
            <table border="1">
                <tr>';
        
        // Вывод заголовков
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        
        echo '</tr>';
        
        // Вывод данных
        foreach ($data as $row) {
            echo '<tr>';
            $colIndex = 0;
            foreach ($row as $value) {
                $class = isset($columnFormats[$colIndex]) ? $columnFormats[$colIndex] : 'text';
                
                if (is_numeric($value) && $class === 'number') {
                    // Форматирование числовых значений
                    $value = number_format($value, 2, '.', ' ');
                } elseif ($class === 'date' && !empty($value)) {
                    // Форматирование дат
                    if (strlen($value) > 10) {
                        $value = substr($value, 0, 10);
                    }
                }
                
                echo '<td class="' . $class . '">' . htmlspecialchars($value) . '</td>';
                $colIndex++;
            }
            echo '</tr>';
        }
        
        echo '</table></body></html>';
        exit();
    }
    
    /**
     * Подготавливает данные для экспорта клиентов
     * 
     * @param array $clients Массив клиентов
     * @return array Ассоциативный массив с заголовками, данными и форматами
     */
    public static function prepareClientsData($clients) {
        $headers = [
            'ID', 'Имя клиента', 'Email', 'Телефон', 'Категория', 
            'Статус', 'Количество заказов', 'Сумма заказов', 'Дата создания'
        ];
        
        $data = [];
        foreach ($clients as $client) {
            $data[] = [
                $client['id'],
                $client['name'],
                $client['email'],
                $client['phone'],
                $client['category'],
                $client['status'],
                $client['orders_count'],
                $client['total_amount'],
                $client['created_at']
            ];
        }
        
        $columnFormats = [
            0 => 'number',  // ID
            6 => 'number',  // Количество заказов
            7 => 'number',  // Сумма заказов
            8 => 'date'     // Дата создания
        ];
        
        return [
            'headers' => $headers,
            'data' => $data,
            'formats' => $columnFormats
        ];
    }
    
    /**
     * Подготавливает данные для экспорта заказов
     * 
     * @param array $orders Массив заказов
     * @return array Ассоциативный массив с заголовками, данными и форматами
     */
    public static function prepareOrdersData($orders) {
        $headers = [
            'ID заказа', 'Клиент', 'Товар/Услуга', 'Сумма', 'Дата заказа', 'Дата создания'
        ];
        
        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                $order['id'],
                $order['client_name'] . ' (ID: ' . $order['client_id'] . ')',
                $order['product_name'],
                $order['amount'],
                $order['order_date'],
                $order['created_at']
            ];
        }
        
        $columnFormats = [
            0 => 'number',  // ID заказа
            3 => 'number',  // Сумма
            4 => 'date',    // Дата заказа
            5 => 'date'     // Дата создания
        ];
        
        return [
            'headers' => $headers,
            'data' => $data,
            'formats' => $columnFormats
        ];
    }
} 