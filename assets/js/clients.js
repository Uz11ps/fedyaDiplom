/**
 * Клиентский JavaScript для страницы клиентов
 */
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация таблицы клиентов
    const clientsTable = $('#clientsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
        },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Все"]],
        responsive: true
    });
    
    // Обработка формы добавления клиента
    $('#saveClientBtn').on('click', function() {
        const form = $('#addClientForm');
        const name = $('#name').val();
        const email = $('#email').val();
        const phone = $('#phone').val();
        const category = $('#category').val();
        const status = $('#status').val();
        
        // Валидация
        if (!name) {
            showAlert('Пожалуйста, укажите название клиента', 'danger');
            return;
        }
        
        if (email && !isValidEmail(email)) {
            showAlert('Пожалуйста, укажите корректный email', 'danger');
            return;
        }
        
        // Отправка данных на сервер
        $.ajax({
            url: '/pages/add_client.php',
            type: 'POST',
            dataType: 'json',
            data: {
                name: name,
                email: email,
                phone: phone,
                category: category,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    // Добавление клиента в таблицу
                    const client = response.client;
                    const categoryClass = getCategoryClass(client.category);
                    const statusClass = client.status === 'Активен' ? 'success' : 'secondary';
                    
                    const actions = `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary view-client" 
                                    data-id="${client.id}" data-bs-toggle="tooltip" title="Просмотр">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning edit-client"
                                    data-id="${client.id}" data-bs-toggle="tooltip" title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-client"
                                    data-id="${client.id}" data-bs-toggle="tooltip" title="Удалить">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    
                    clientsTable.row.add([
                        client.id,
                        escapeHtml(client.name),
                        escapeHtml(client.email || ''),
                        escapeHtml(client.phone || ''),
                        `<span class="badge rounded-pill bg-${categoryClass}">${escapeHtml(client.category)}</span>`,
                        `<span class="badge rounded-pill bg-${statusClass}">${escapeHtml(client.status)}</span>`,
                        '0', // заказы
                        '0 ₽', // сумма
                        client.created_at,
                        actions
                    ]).draw();
                    
                    // Закрытие модального окна и сброс формы
                    $('#addClientModal').modal('hide');
                    form[0].reset();
                    
                    // Обновление обработчиков для новых кнопок
                    bindClientActions();
                    
                    showAlert(response.message, 'success');
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Произошла ошибка при добавлении клиента';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert(errorMessage, 'danger');
            }
        });
    });
    
    // Функция для привязки обработчиков к кнопкам
    function bindClientActions() {
        // Обработка клика по кнопке "Просмотр"
        $('.view-client').off('click').on('click', function() {
            const clientId = $(this).data('id');
            viewClient(clientId);
        });
        
        // Обработка клика по кнопке "Редактировать"
        $('.edit-client').off('click').on('click', function() {
            const clientId = $(this).data('id');
            editClient(clientId);
        });
        
        // Обработка клика по кнопке "Удалить"
        $('.delete-client').off('click').on('click', function() {
            const clientId = $(this).data('id');
            
            if (confirm('Вы уверены, что хотите удалить этого клиента? Все заказы клиента также будут удалены.')) {
                deleteClient(clientId);
            }
        });
    }
    
    // Просмотр клиента
    function viewClient(clientId) {
        $.ajax({
            url: '/pages/get_client.php',
            type: 'GET',
            dataType: 'json',
            data: { id: clientId },
            success: function(response) {
                if (response.success) {
                    const client = response.client;
                    
                    // Создаем модальное окно для просмотра
                    let modalHtml = `
                        <div class="modal fade" id="viewClientModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Информация о клиенте</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="client-info">
                                            <h3>${escapeHtml(client.name)}</h3>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <p><strong>Email:</strong> ${escapeHtml(client.email || 'Не указан')}</p>
                                                    <p><strong>Телефон:</strong> ${escapeHtml(client.phone || 'Не указан')}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Категория:</strong> <span class="badge rounded-pill bg-${getCategoryClass(client.category)}">${escapeHtml(client.category)}</span></p>
                                                    <p><strong>Статус:</strong> <span class="badge rounded-pill bg-${client.status === 'Активен' ? 'success' : 'secondary'}">${escapeHtml(client.status)}</span></p>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h4>Заказы клиента</h4>
                                                <button type="button" class="btn btn-sm btn-success" id="addOrderBtn">
                                                    <i class="fas fa-plus"></i> Добавить заказ
                                                </button>
                                            </div>
                    `;
                    
                    if (client.orders && client.orders.length > 0) {
                        modalHtml += `
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Товар/Услуга</th>
                                            <th>Сумма</th>
                                            <th>Дата</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        client.orders.forEach(order => {
                            modalHtml += `
                                <tr>
                                    <td>${order.id}</td>
                                    <td>${escapeHtml(order.product_name || 'Нет данных')}</td>
                                    <td>${formatCurrency(order.amount)}</td>
                                    <td>${formatDate(order.order_date)}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-warning edit-order" 
                                                    data-id="${order.id}" 
                                                    data-product="${escapeHtml(order.product_name || '')}"
                                                    data-amount="${order.amount}"
                                                    data-date="${order.order_date}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-order"
                                                    data-id="${order.id}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        modalHtml += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        modalHtml += `
                            <div class="alert alert-info">
                                У клиента пока нет заказов.
                            </div>
                        `;
                    }
                    
                    modalHtml += `
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Удаляем существующее модальное окно, если оно есть
                    $('#viewClientModal').remove();
                    
                    // Добавляем новое модальное окно
                    $('body').append(modalHtml);
                    
                    // Показываем модальное окно
                    const viewModal = new bootstrap.Modal(document.getElementById('viewClientModal'));
                    viewModal.show();
                    
                    // Обработчик для добавления заказа
                    $('#addOrderBtn').on('click', function() {
                        // Создаем форму добавления заказа
                        const addOrderModal = `
                            <div class="modal fade" id="addOrderModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Добавление заказа</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="addOrderForm">
                                                <input type="hidden" id="clientId" value="${client.id}">
                                                <div class="mb-3">
                                                    <label for="productName" class="form-label">Товар/Услуга</label>
                                                    <input type="text" class="form-control" id="productName" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="orderAmount" class="form-label">Сумма</label>
                                                    <input type="number" class="form-control" id="orderAmount" step="0.01" min="0" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="orderDate" class="form-label">Дата заказа</label>
                                                    <input type="date" class="form-control" id="orderDate" value="${new Date().toISOString().substr(0, 10)}" required>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                            <button type="button" class="btn btn-primary" id="saveOrderBtn">Сохранить</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Удаляем существующее модальное окно, если оно есть
                        $('#addOrderModal').remove();
                        
                        // Добавляем новое модальное окно
                        $('body').append(addOrderModal);
                        
                        // Показываем модальное окно
                        const orderModal = new bootstrap.Modal(document.getElementById('addOrderModal'));
                        orderModal.show();
                        
                        // Обработчик для сохранения заказа
                        $('#saveOrderBtn').on('click', function() {
                            const productName = $('#productName').val();
                            const orderAmount = $('#orderAmount').val();
                            const orderDate = $('#orderDate').val();
                            
                            if (!productName || !orderAmount) {
                                showAlert('Пожалуйста, заполните все обязательные поля', 'danger');
                                return;
                            }
                            
                            $.ajax({
                                url: '/pages/add_order.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    client_id: client.id,
                                    product_name: productName,
                                    amount: orderAmount,
                                    order_date: orderDate
                                },
                                success: function(response) {
                                    if (response.success) {
                                        showAlert(response.message, 'success');
                                        
                                        // Закрыть модальное окно заказа
                                        orderModal.hide();
                                        
                                        // Перезагрузить модальное окно клиента для показа нового заказа
                                        viewModal.hide();
                                        viewClient(client.id);
                                    } else {
                                        showAlert(response.message, 'danger');
                                    }
                                },
                                error: function(xhr) {
                                    let errorMessage = 'Произошла ошибка при добавлении заказа';
                                    
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }
                                    
                                    showAlert(errorMessage, 'danger');
                                }
                            });
                        });
                    });
                    
                    // Обработчик для редактирования заказа
                    $('.edit-order').on('click', function() {
                        const orderId = $(this).data('id');
                        const productName = $(this).data('product');
                        const amount = $(this).data('amount');
                        const orderDate = $(this).data('date');
                        
                        // Создаем форму редактирования заказа
                        const editOrderModal = `
                            <div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Редактирование заказа</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="editOrderForm">
                                                <input type="hidden" id="editOrderId" value="${orderId}">
                                                <div class="mb-3">
                                                    <label for="editProductName" class="form-label">Товар/Услуга</label>
                                                    <input type="text" class="form-control" id="editProductName" value="${productName}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="editOrderAmount" class="form-label">Сумма</label>
                                                    <input type="number" class="form-control" id="editOrderAmount" step="0.01" min="0" value="${amount}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="editOrderDate" class="form-label">Дата заказа</label>
                                                    <input type="date" class="form-control" id="editOrderDate" value="${orderDate}" required>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                            <button type="button" class="btn btn-primary" id="updateOrderBtn">Сохранить</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Удаляем существующее модальное окно, если оно есть
                        $('#editOrderModal').remove();
                        
                        // Добавляем новое модальное окно
                        $('body').append(editOrderModal);
                        
                        // Показываем модальное окно
                        const editModal = new bootstrap.Modal(document.getElementById('editOrderModal'));
                        editModal.show();
                        
                        // Обработчик для обновления заказа
                        $('#updateOrderBtn').on('click', function() {
                            const editProductName = $('#editProductName').val();
                            const editOrderAmount = $('#editOrderAmount').val();
                            const editOrderDate = $('#editOrderDate').val();
                            
                            if (!editProductName || !editOrderAmount) {
                                showAlert('Пожалуйста, заполните все обязательные поля', 'danger');
                                return;
                            }
                            
                            $.ajax({
                                url: '/pages/edit_order.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    id: orderId,
                                    product_name: editProductName,
                                    amount: editOrderAmount,
                                    order_date: editOrderDate
                                },
                                success: function(response) {
                                    if (response.success) {
                                        showAlert(response.message, 'success');
                                        
                                        // Закрыть модальное окно редактирования заказа
                                        editModal.hide();
                                        
                                        // Перезагрузить модальное окно клиента для показа обновленного заказа
                                        viewModal.hide();
                                        viewClient(client.id);
                                    } else {
                                        showAlert(response.message, 'danger');
                                    }
                                },
                                error: function(xhr) {
                                    let errorMessage = 'Произошла ошибка при обновлении заказа';
                                    
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }
                                    
                                    showAlert(errorMessage, 'danger');
                                }
                            });
                        });
                    });
                    
                    // Обработчик для удаления заказа
                    $('.delete-order').on('click', function() {
                        const orderId = $(this).data('id');
                        
                        if (confirm('Вы уверены, что хотите удалить этот заказ?')) {
                            $.ajax({
                                url: '/pages/delete_order.php',
                                type: 'POST',
                                dataType: 'json',
                                data: { id: orderId },
                                success: function(response) {
                                    if (response.success) {
                                        showAlert(response.message, 'success');
                                        
                                        // Перезагрузить модальное окно клиента для обновления списка заказов
                                        viewModal.hide();
                                        viewClient(client.id);
                                    } else {
                                        showAlert(response.message, 'danger');
                                    }
                                },
                                error: function(xhr) {
                                    let errorMessage = 'Произошла ошибка при удалении заказа';
                                    
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }
                                    
                                    showAlert(errorMessage, 'danger');
                                }
                            });
                        }
                    });
                    
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Произошла ошибка при получении информации о клиенте';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert(errorMessage, 'danger');
            }
        });
    }
    
    // Редактирование клиента
    function editClient(clientId) {
        $.ajax({
            url: '/pages/get_client.php',
            type: 'GET',
            dataType: 'json',
            data: { id: clientId },
            success: function(response) {
                if (response.success) {
                    const client = response.client;
                    
                    // Создаем модальное окно для редактирования
                    let modalHtml = `
                        <div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Редактирование клиента</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="editClientForm">
                                            <input type="hidden" id="edit_id" value="${client.id}">
                                            <div class="mb-3">
                                                <label for="edit_name" class="form-label">Название клиента</label>
                                                <input type="text" class="form-control" id="edit_name" value="${escapeHtml(client.name)}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="edit_email" value="${escapeHtml(client.email || '')}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_phone" class="form-label">Телефон</label>
                                                <input type="tel" class="form-control" id="edit_phone" value="${escapeHtml(client.phone || '')}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_category" class="form-label">Категория</label>
                                                <select class="form-select" id="edit_category" required>
                                                    <option value="Розничный" ${client.category === 'Розничный' ? 'selected' : ''}>Розничный</option>
                                                    <option value="Корпоративный" ${client.category === 'Корпоративный' ? 'selected' : ''}>Корпоративный</option>
                                                    <option value="Партнер" ${client.category === 'Партнер' ? 'selected' : ''}>Партнер</option>
                                                    <option value="VIP" ${client.category === 'VIP' ? 'selected' : ''}>VIP</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_status" class="form-label">Статус</label>
                                                <select class="form-select" id="edit_status" required>
                                                    <option value="Активен" ${client.status === 'Активен' ? 'selected' : ''}>Активен</option>
                                                    <option value="Неактивен" ${client.status === 'Неактивен' ? 'selected' : ''}>Неактивен</option>
                                                </select>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                        <button type="button" class="btn btn-primary" id="updateClientBtn">Сохранить изменения</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Удаляем предыдущее модальное окно, если оно существует
                    $('#editClientModal').remove();
                    
                    // Добавляем модальное окно и открываем его
                    $('body').append(modalHtml);
                    const editModal = new bootstrap.Modal(document.getElementById('editClientModal'));
                    editModal.show();
                    
                    // Обработчик для кнопки сохранения
                    $('#updateClientBtn').on('click', function() {
                        updateClient(editModal);
                    });
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Произошла ошибка при получении данных клиента';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert(errorMessage, 'danger');
            }
        });
    }
    
    // Обновление данных клиента
    function updateClient(modal) {
        const id = $('#edit_id').val();
        const name = $('#edit_name').val();
        const email = $('#edit_email').val();
        const phone = $('#edit_phone').val();
        const category = $('#edit_category').val();
        const status = $('#edit_status').val();
        
        // Валидация
        if (!name) {
            showAlert('Пожалуйста, укажите название клиента', 'danger');
            return;
        }
        
        if (email && !isValidEmail(email)) {
            showAlert('Пожалуйста, укажите корректный email', 'danger');
            return;
        }
        
        // Отправка данных на сервер
        $.ajax({
            url: '/pages/update_client.php',
            type: 'POST',
            dataType: 'json',
            data: {
                id: id,
                name: name,
                email: email,
                phone: phone,
                category: category,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    const client = response.client;
                    
                    // Обновление данных в таблице
                    const row = clientsTable.row($(`button.edit-client[data-id="${client.id}"]`).closest('tr'));
                    const rowData = row.data();
                    
                    if (rowData) {
                        // Обновляем только необходимые ячейки (сохраняем ID, заказы и сумму без изменений)
                        rowData[1] = escapeHtml(client.name);
                        rowData[2] = escapeHtml(client.email || '');
                        rowData[3] = escapeHtml(client.phone || '');
                        rowData[4] = `<span class="badge rounded-pill bg-${getCategoryClass(client.category)}">${escapeHtml(client.category)}</span>`;
                        rowData[5] = `<span class="badge rounded-pill bg-${client.status === 'Активен' ? 'success' : 'secondary'}">${escapeHtml(client.status)}</span>`;
                        
                        row.data(rowData).draw();
                    }
                    
                    // Закрытие модального окна
                    modal.hide();
                    
                    showAlert(response.message, 'success');
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Произошла ошибка при обновлении данных клиента';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert(errorMessage, 'danger');
            }
        });
    }
    
    // Удаление клиента
    function deleteClient(clientId) {
        $.ajax({
            url: '/pages/delete_client.php',
            type: 'POST',
            dataType: 'json',
            data: { id: clientId },
            success: function(response) {
                if (response.success) {
                    // Удаление строки из таблицы
                    const row = clientsTable.row($(`button.delete-client[data-id="${clientId}"]`).closest('tr'));
                    row.remove().draw();
                    
                    showAlert(response.message, 'success');
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Произошла ошибка при удалении клиента';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert(errorMessage, 'danger');
            }
        });
    }
    
    // Вспомогательные функции
    
    // Отображение уведомления
    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Удаляем предыдущие уведомления
        $('.alert-container').remove();
        
        // Добавляем новое уведомление
        $('<div class="alert-container"></div>')
            .html(alertHtml)
            .appendTo('.main-content')
            .css({
                'position': 'fixed',
                'top': '20px',
                'right': '20px',
                'max-width': '400px',
                'z-index': '9999'
            });
        
        // Автоматическое скрытие через 5 секунд
        setTimeout(() => {
            $('.alert-container .alert').alert('close');
        }, 5000);
    }
    
    // Получение класса цвета для категории
    function getCategoryClass(category) {
        switch(category) {
            case 'VIP': return 'danger';
            case 'Корпоративный': return 'primary';
            case 'Партнер': return 'warning';
            default: return 'success';
        }
    }
    
    // Проверка email
    function isValidEmail(email) {
        const re = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        return re.test(email);
    }
    
    // Форматирование денежной суммы
    function formatCurrency(amount) {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 2
        }).format(amount);
    }
    
    // Форматирование даты
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU');
    }
    
    // Экранирование HTML
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Инициализация обработчиков
    bindClientActions();
}); 