$(document).ready(function() {
    // Инициализация календарей
    initDatepickers();
    
    // Обработчики событий для модальных окон
    initModalEvents();
    
    // Обработчик для кнопки добавления нового заказа
    $("#newOrderBtn").click(function() {
        $('#addOrderModal').modal('show');
    });
    
    // Обработчик для кнопки редактирования заказа
    $(document).on('click', '.edit-order', function() {
        const id = $(this).data('id');
        const clientId = $(this).data('client');
        const productName = $(this).data('product');
        const amount = $(this).data('amount');
        const orderDate = $(this).data('order-date');
        const status = $(this).data('status');
        
        // Заполняем форму данными
        $('#edit_order_id').val(id);
        $('#edit_client_id').val(clientId);
        $('#edit_product_name').val(productName);
        $('#edit_amount').val(amount);
        $('#edit_order_date').val(orderDate);
        $('#edit_status').val(status);
        
        // Обновляем календарь
        initDatepickers();
        
        // Открываем модальное окно
        $('#editOrderModal').modal('show');
    });
    
    // Обработчик для кнопки удаления заказа
    $(document).on('click', '.delete-order', function() {
        const id = $(this).data('id');
        const productName = $(this).data('product');
        
        $('#delete_order_id').val(id);
        $('#delete_order_name').text(productName);
        $('#deleteOrderModal').modal('show');
    });
    
    // Обработчик для кнопки печати
    $("#printOrders").click(function(e) {
        e.preventDefault();
        printOrders();
    });
});

/**
 * Инициализация календарей
 */
function initDatepickers() {
    flatpickr(".datepicker", {
        locale: "ru",
        dateFormat: "Y-m-d",
        allowInput: true
    });
}

/**
 * Инициализация обработчиков событий для модальных окон
 */
function initModalEvents() {
    // Сохранение нового заказа
    $('#saveOrderBtn').click(function() {
        const clientId = $('#add_client_id').val();
        const productName = $('#add_product_name').val();
        const amount = $('#add_amount').val();
        const orderDate = $('#add_order_date').val();
        const status = $('#add_status').val();
        
        // Валидация
        if (!clientId || !productName || !amount || !orderDate) {
            alert('Все поля формы обязательны для заполнения');
            return;
        }
        
        // Отправка данных на сервер
        $.ajax({
            url: '/pages/add_order.php',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify({
                client_id: clientId,
                product_name: productName,
                amount: amount,
                order_date: orderDate,
                status: status
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    // Закрываем модальное окно
                    $('#addOrderModal').modal('hide');
                    
                    // Показываем сообщение об успехе
                    showAlert('success', response.message);
                    
                    // Перезагружаем страницу для отображения новых данных
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('danger', 'Ошибка при добавлении заказа: ' + error);
            }
        });
    });
    
    // Обновление существующего заказа
    $('#updateOrderBtn').click(function() {
        const orderId = $('#edit_order_id').val();
        const clientId = $('#edit_client_id').val();
        const productName = $('#edit_product_name').val();
        const amount = $('#edit_amount').val();
        const orderDate = $('#edit_order_date').val();
        const status = $('#edit_status').val();
        
        // Валидация
        if (!orderId || !clientId || !productName || !amount || !orderDate) {
            alert('Все поля формы обязательны для заполнения');
            return;
        }
        
        // Проверка формата даты
        try {
            const dateObj = new Date(orderDate);
            if (isNaN(dateObj.getTime())) {
                alert('Введите корректную дату');
                return;
            }
        } catch (e) {
            alert('Ошибка при проверке даты: ' + e.message);
            return;
        }
        
        // Отправка данных на сервер
        $.ajax({
            url: '/pages/edit_order.php',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify({
                id: orderId,
                client_id: clientId,
                product_name: productName,
                amount: amount,
                order_date: orderDate,
                status: status
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    // Закрываем модальное окно
                    $('#editOrderModal').modal('hide');
                    
                    // Показываем сообщение об успехе
                    showAlert('success', response.message);
                    
                    // Перезагружаем страницу для отображения обновленных данных
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('danger', 'Ошибка при обновлении заказа: ' + error);
            }
        });
    });
    
    // Удаление заказа
    $('#confirmDeleteBtn').click(function() {
        const orderId = $('#delete_order_id').val();
        
        // Отправка данных на сервер
        $.ajax({
            url: '/pages/delete_order.php',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify({
                id: orderId
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    // Закрываем модальное окно
                    $('#deleteOrderModal').modal('hide');
                    
                    // Показываем сообщение об успехе
                    showAlert('success', response.message);
                    
                    // Перезагружаем страницу для отображения обновленных данных
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('danger', 'Ошибка при удалении заказа: ' + error);
            }
        });
    });
}

/**
 * Показать всплывающее уведомление
 * 
 * @param {string} type Тип уведомления ('success', 'danger', 'warning', 'info')
 * @param {string} message Текст сообщения
 */
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Добавляем alert перед таблицей
    $("#orders-table").before(alertHtml);
    
    // Автоматическое скрытие через 5 секунд
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

/**
 * Печать списка заказов
 */
function printOrders() {
    // Создаем новое окно для печати
    const printWindow = window.open('', '_blank');
    
    // Формируем HTML для печати
    const printHtml = `
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Список заказов</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                h1 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
                .text-right {
                    text-align: right;
                }
                .total-row {
                    font-weight: bold;
                }
                .print-date {
                    text-align: right;
                    margin-bottom: 20px;
                    font-style: italic;
                }
            </style>
        </head>
        <body>
            <div class="print-date">Дата печати: ${new Date().toLocaleDateString()}</div>
            <h1>Список заказов</h1>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Клиент</th>
                        <th>Товар/Услуга</th>
                        <th>Сумма</th>
                        <th>Дата заказа</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    ${getOrdersForPrint()}
                </tbody>
            </table>
        </body>
        </html>
    `;
    
    // Записываем HTML в новое окно
    printWindow.document.write(printHtml);
    printWindow.document.close();
    
    // Запускаем печать после загрузки содержимого
    printWindow.onload = function() {
        printWindow.print();
        // printWindow.close();
    };
}

/**
 * Получить данные заказов для печати
 * 
 * @returns {string} HTML таблицы заказов
 */
function getOrdersForPrint() {
    let html = '';
    let totalAmount = 0;
    
    // Перебираем все строки таблицы заказов
    $('#orders-table tbody tr').each(function() {
        const cells = $(this).find('td');
        
        const id = $(cells[0]).text();
        const client = $(cells[1]).text();
        const product = $(cells[2]).text();
        const amount = $(cells[3]).text();
        const date = $(cells[4]).text();
        const status = $(cells[5]).text().trim();
        
        // Добавляем строку в HTML
        html += `
            <tr>
                <td>${id}</td>
                <td>${client}</td>
                <td>${product}</td>
                <td class="text-right">${amount}</td>
                <td>${date}</td>
                <td>${status}</td>
            </tr>
        `;
        
        // Добавляем сумму к общей сумме
        const amountValue = parseFloat(amount.replace(/\s/g, '').replace('₽', '').replace(',', '.'));
        if (!isNaN(amountValue)) {
            totalAmount += amountValue;
        }
    });
    
    // Добавляем строку с общей суммой
    html += `
        <tr class="total-row">
            <td colspan="3" class="text-right">Общая сумма:</td>
            <td class="text-right">${totalAmount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$& ')} ₽</td>
            <td colspan="2"></td>
        </tr>
    `;
    
    return html;
} 