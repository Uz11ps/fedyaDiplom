-- Создание базы данных
CREATE DATABASE aplana_it_clients;
GO

USE aplana_it_clients;
GO

-- Создание таблицы пользователей системы
CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    username NVARCHAR(50) NOT NULL UNIQUE,
    email NVARCHAR(100) NOT NULL UNIQUE,
    password NVARCHAR(255) NOT NULL,
    first_name NVARCHAR(50),
    last_name NVARCHAR(50),
    role NVARCHAR(20) DEFAULT 'user',
    last_login DATETIME,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE(),
    is_active BIT DEFAULT 1
);
GO

-- Создание таблицы клиентов
CREATE TABLE clients (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(100) NOT NULL,
    email NVARCHAR(100),
    phone NVARCHAR(20),
    address NVARCHAR(255),
    category NVARCHAR(50) DEFAULT 'Розничный',
    status NVARCHAR(20) DEFAULT 'Активен',
    notes NVARCHAR(MAX),
    created_by INT REFERENCES users(id),
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- Создание таблицы контактных лиц клиентов
CREATE TABLE client_contacts (
    id INT IDENTITY(1,1) PRIMARY KEY,
    client_id INT REFERENCES clients(id) ON DELETE CASCADE,
    first_name NVARCHAR(50) NOT NULL,
    last_name NVARCHAR(50) NOT NULL,
    position NVARCHAR(100),
    email NVARCHAR(100),
    phone NVARCHAR(20),
    is_primary BIT DEFAULT 0,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- Создание таблицы заказов клиентов
CREATE TABLE orders (
    id INT IDENTITY(1,1) PRIMARY KEY,
    client_id INT REFERENCES clients(id) ON DELETE CASCADE,
    order_number NVARCHAR(20) NOT NULL UNIQUE,
    amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    status NVARCHAR(20) DEFAULT 'Новый',
    order_date DATETIME DEFAULT GETDATE(),
    completion_date DATETIME,
    payment_status NVARCHAR(20) DEFAULT 'Ожидает оплаты',
    created_by INT REFERENCES users(id),
    notes NVARCHAR(MAX),
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- Создание таблицы продуктов/услуг
CREATE TABLE products (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(100) NOT NULL,
    description NVARCHAR(MAX),
    price DECIMAL(15, 2) NOT NULL DEFAULT 0,
    category NVARCHAR(50),
    sku NVARCHAR(50),
    is_active BIT DEFAULT 1,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- Создание таблицы элементов заказа
CREATE TABLE order_items (
    id INT IDENTITY(1,1) PRIMARY KEY,
    order_id INT REFERENCES orders(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id),
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(15, 2) NOT NULL,
    discount DECIMAL(5, 2) DEFAULT 0,
    total DECIMAL(15, 2) NOT NULL,
    notes NVARCHAR(MAX),
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- Создание таблицы задач/активностей по клиентам
CREATE TABLE activities (
    id INT IDENTITY(1,1) PRIMARY KEY,
    client_id INT REFERENCES clients(id) ON DELETE CASCADE,
    user_id INT REFERENCES users(id),
    type NVARCHAR(50) NOT NULL,
    title NVARCHAR(255) NOT NULL,
    description NVARCHAR(MAX),
    status NVARCHAR(20) DEFAULT 'Открыта',
    due_date DATETIME,
    completed_at DATETIME,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- Создание таблицы документов клиентов
CREATE TABLE documents (
    id INT IDENTITY(1,1) PRIMARY KEY,
    client_id INT REFERENCES clients(id) ON DELETE CASCADE,
    name NVARCHAR(255) NOT NULL,
    type NVARCHAR(50),
    file_path NVARCHAR(255),
    file_size INT,
    upload_date DATETIME DEFAULT GETDATE(),
    uploaded_by INT REFERENCES users(id),
    notes NVARCHAR(MAX)
);
GO

-- Создание таблицы уведомлений
CREATE TABLE notifications (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    title NVARCHAR(255) NOT NULL,
    message NVARCHAR(MAX),
    type NVARCHAR(50) DEFAULT 'info',
    is_read BIT DEFAULT 0,
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- Создание таблицы логов действий пользователей
CREATE TABLE activity_logs (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT REFERENCES users(id),
    action NVARCHAR(255) NOT NULL,
    entity_type NVARCHAR(50),
    entity_id INT,
    details NVARCHAR(MAX),
    ip_address NVARCHAR(50),
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- Индексы для оптимизации запросов
CREATE INDEX idx_clients_category ON clients(category);
CREATE INDEX idx_clients_status ON clients(status);
CREATE INDEX idx_orders_client_id ON orders(client_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);
CREATE INDEX idx_activities_client_id ON activities(client_id);
CREATE INDEX idx_activities_status ON activities(status);
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
GO

-- Представление для отображения всей информации о клиентах
CREATE VIEW vw_client_details AS
SELECT 
    c.id, c.name, c.email, c.phone, c.address, c.category, c.status,
    c.created_at, c.updated_at,
    COUNT(DISTINCT o.id) AS orders_count,
    SUM(o.amount) AS total_orders_amount,
    MAX(o.order_date) AS last_order_date,
    COUNT(DISTINCT a.id) AS activities_count,
    COUNT(DISTINCT cc.id) AS contacts_count
FROM clients c
LEFT JOIN orders o ON c.id = o.client_id
LEFT JOIN activities a ON c.id = a.client_id
LEFT JOIN client_contacts cc ON c.id = cc.client_id
GROUP BY c.id, c.name, c.email, c.phone, c.address, c.category, c.status, c.created_at, c.updated_at;
GO

-- Представление для отображения статистики по заказам
CREATE VIEW vw_order_statistics AS
SELECT 
    YEAR(order_date) AS year,
    MONTH(order_date) AS month,
    COUNT(*) AS orders_count,
    SUM(amount) AS total_amount,
    AVG(amount) AS average_amount,
    COUNT(DISTINCT client_id) AS unique_clients
FROM orders
GROUP BY YEAR(order_date), MONTH(order_date);
GO

-- Триггер для обновления времени изменения клиента
CREATE TRIGGER trg_client_update
ON clients
AFTER UPDATE
AS
BEGIN
    UPDATE clients
    SET updated_at = GETDATE()
    FROM clients c
    INNER JOIN inserted i ON c.id = i.id;
END;
GO

-- Триггер для обновления времени изменения заказа
CREATE TRIGGER trg_order_update
ON orders
AFTER UPDATE
AS
BEGIN
    UPDATE orders
    SET updated_at = GETDATE()
    FROM orders o
    INNER JOIN inserted i ON o.id = i.id;
END;
GO

-- Триггер для логирования действий с клиентами
CREATE TRIGGER trg_client_log
ON clients
AFTER INSERT, UPDATE, DELETE
AS
BEGIN
    DECLARE @action NVARCHAR(10);
    
    IF EXISTS (SELECT * FROM inserted) AND EXISTS (SELECT * FROM deleted)
        SET @action = 'UPDATE';
    ELSE IF EXISTS (SELECT * FROM inserted)
        SET @action = 'INSERT';
    ELSE
        SET @action = 'DELETE';
    
    IF @action = 'INSERT'
    BEGIN
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details)
        SELECT created_by, 'Создание клиента', 'client', id, N'Создан новый клиент: ' + name
        FROM inserted;
    END
    ELSE IF @action = 'UPDATE'
    BEGIN
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details)
        SELECT i.created_by, 'Обновление клиента', 'client', i.id, N'Обновлен клиент: ' + i.name
        FROM inserted i
        INNER JOIN deleted d ON i.id = d.id;
    END
    ELSE
    BEGIN
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details)
        SELECT NULL, 'Удаление клиента', 'client', id, N'Удален клиент: ' + name
        FROM deleted;
    END
END;
GO

-- Создание процедуры для добавления нового клиента
CREATE PROCEDURE sp_add_client
    @name NVARCHAR(100),
    @email NVARCHAR(100),
    @phone NVARCHAR(20),
    @address NVARCHAR(255),
    @category NVARCHAR(50),
    @status NVARCHAR(20),
    @notes NVARCHAR(MAX),
    @created_by INT,
    @client_id INT OUTPUT
AS
BEGIN
    INSERT INTO clients (name, email, phone, address, category, status, notes, created_by)
    VALUES (@name, @email, @phone, @address, @category, @status, @notes, @created_by);
    
    SET @client_id = SCOPE_IDENTITY();
    
    -- Создаем запись в логе активностей
    INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details)
    VALUES (@created_by, N'Создание клиента', 'client', @client_id, N'Создан новый клиент: ' + @name);
    
    -- Создаем уведомление для пользователя
    INSERT INTO notifications (user_id, title, message, type)
    VALUES (@created_by, N'Новый клиент', N'Вы создали нового клиента: ' + @name, 'success');
    
    RETURN @client_id;
END;
GO

-- Создание процедуры для получения статистики по клиентам
CREATE PROCEDURE sp_get_client_statistics
    @start_date DATE = NULL,
    @end_date DATE = NULL,
    @category NVARCHAR(50) = NULL
AS
BEGIN
    IF @start_date IS NULL
        SET @start_date = DATEADD(MONTH, -12, GETDATE());
    
    IF @end_date IS NULL
        SET @end_date = GETDATE();
    
    -- Статистика по новым клиентам
    SELECT 
        YEAR(created_at) AS year,
        MONTH(created_at) AS month,
        COUNT(*) AS new_clients_count,
        category
    FROM clients
    WHERE created_at BETWEEN @start_date AND @end_date
    AND (@category IS NULL OR category = @category)
    GROUP BY YEAR(created_at), MONTH(created_at), category
    ORDER BY year, month;
    
    -- Статистика по заказам клиентов
    SELECT 
        c.category,
        COUNT(o.id) AS orders_count,
        SUM(o.amount) AS total_amount,
        AVG(o.amount) AS average_amount
    FROM clients c
    LEFT JOIN orders o ON c.id = o.client_id
    WHERE o.order_date BETWEEN @start_date AND @end_date
    AND (@category IS NULL OR c.category = @category)
    GROUP BY c.category
    ORDER BY total_amount DESC;
    
    -- Статистика по активности клиентов
    SELECT 
        c.category,
        COUNT(a.id) AS activities_count,
        COUNT(DISTINCT c.id) AS active_clients_count
    FROM clients c
    LEFT JOIN activities a ON c.id = a.client_id
    WHERE a.created_at BETWEEN @start_date AND @end_date
    AND (@category IS NULL OR c.category = @category)
    GROUP BY c.category;
END;
GO

-- Вставка тестовых данных
-- Создание администратора системы
INSERT INTO users (username, email, password, first_name, last_name, role)
VALUES 
('admin', 'admin@aplana-it.ru', '$2y$10$0FGlUjWPV96uKK/mWnu9SeOmNlBKGTcWlYK1PZ.h4i2UNjPODJkDa', 'Администратор', 'Системы', 'admin');

-- Создание обычного пользователя
INSERT INTO users (username, email, password, first_name, last_name, role)
VALUES 
('manager', 'manager@aplana-it.ru', '$2y$10$0FGlUjWPV96uKK/mWnu9SeOmNlBKGTcWlYK1PZ.h4i2UNjPODJkDa', 'Иван', 'Петров', 'manager');

-- Создание тестовых клиентов
INSERT INTO clients (name, email, phone, address, category, status, created_by)
VALUES 
('ООО "ТехноСтар"', 'info@technostar.ru', '+7 (495) 123-45-67', 'г. Москва, ул. Ленина, д. 10', 'Корпоративный', 'Активен', 1),
('ИП Смирнов А.В.', 'smirnov@mail.ru', '+7 (926) 765-43-21', 'г. Москва, ул. Пушкина, д. 5', 'Розничный', 'Активен', 1),
('ЗАО "МегаТрейд"', 'info@megatrade.ru', '+7 (499) 987-65-43', 'г. Москва, Проспект Мира, д. 120', 'VIP', 'Активен', 2),
('ООО "СтройМаркет"', 'sales@stroymarket.ru', '+7 (495) 555-77-88', 'г. Москва, ул. Строителей, д. 15', 'Корпоративный', 'Активен', 2),
('ИП Иванова Е.П.', 'ivanova@gmail.com', '+7 (916) 333-22-11', 'г. Москва, ул. Гагарина, д. 7', 'Розничный', 'Неактивен', 1);

-- Создание тестовых заказов
INSERT INTO orders (client_id, order_number, amount, status, payment_status, created_by)
VALUES 
(1, 'ORD-2023-001', 150000.00, 'Выполнен', 'Оплачен', 1),
(1, 'ORD-2023-002', 75000.00, 'В обработке', 'Ожидает оплаты', 1),
(2, 'ORD-2023-003', 15000.00, 'Выполнен', 'Оплачен', 2),
(3, 'ORD-2023-004', 350000.00, 'Выполнен', 'Оплачен', 2),
(4, 'ORD-2023-005', 120000.00, 'В обработке', 'Частично оплачен', 1),
(3, 'ORD-2023-006', 250000.00, 'Новый', 'Ожидает оплаты', 2);

-- Создание тестовых продуктов
INSERT INTO products (name, description, price, category)
VALUES 
('Разработка веб-сайта', 'Разработка корпоративного веб-сайта', 150000.00, 'Разработка'),
('SEO-оптимизация', 'Оптимизация сайта для поисковых систем', 50000.00, 'Маркетинг'),
('Техническая поддержка', 'Ежемесячная техническая поддержка', 15000.00, 'Поддержка'),
('Мобильное приложение', 'Разработка мобильного приложения', 350000.00, 'Разработка'),
('Корпоративная почта', 'Настройка корпоративной почты', 25000.00, 'Инфраструктура');

-- Создание элементов заказов
INSERT INTO order_items (order_id, product_id, quantity, price, total)
VALUES 
(1, 1, 1, 150000.00, 150000.00),
(2, 2, 1, 50000.00, 50000.00),
(2, 3, 1, 25000.00, 25000.00),
(3, 3, 1, 15000.00, 15000.00),
(4, 4, 1, 350000.00, 350000.00),
(5, 1, 0.8, 150000.00, 120000.00),
(6, 4, 0.5, 350000.00, 175000.00),
(6, 2, 1.5, 50000.00, 75000.00);

-- Создание контактных лиц клиентов
INSERT INTO client_contacts (client_id, first_name, last_name, position, email, phone, is_primary)
VALUES 
(1, 'Александр', 'Петров', 'Генеральный директор', 'petrov@technostar.ru', '+7 (495) 123-45-68', 1),
(1, 'Елена', 'Сидорова', 'Финансовый директор', 'sidorova@technostar.ru', '+7 (495) 123-45-69', 0),
(3, 'Сергей', 'Козлов', 'Генеральный директор', 'kozlov@megatrade.ru', '+7 (499) 987-65-44', 1),
(4, 'Ольга', 'Морозова', 'Руководитель отдела закупок', 'morozova@stroymarket.ru', '+7 (495) 555-77-89', 1);

-- Создание тестовых активностей
INSERT INTO activities (client_id, user_id, type, title, description, status)
VALUES 
(1, 1, 'Встреча', 'Обсуждение нового проекта', 'Встреча с клиентом для обсуждения требований к новому проекту', 'Завершена'),
(1, 2, 'Звонок', 'Уточнение деталей', 'Телефонный звонок для уточнения деталей проекта', 'Завершена'),
(2, 1, 'Email', 'Коммерческое предложение', 'Отправка коммерческого предложения', 'Завершена'),
(3, 2, 'Встреча', 'Презентация решения', 'Презентация решения для клиента', 'Открыта'),
(4, 1, 'Задача', 'Подготовка документов', 'Подготовка договора и спецификации', 'В процессе');

-- Создание уведомлений
INSERT INTO notifications (user_id, title, message, type)
VALUES 
(1, 'Новый заказ', 'Получен новый заказ от клиента ООО "ТехноСтар"', 'info'),
(1, 'Задача назначена', 'Вам назначена задача: Подготовка документов', 'task'),
(2, 'Оплата получена', 'Получена оплата по заказу ORD-2023-001', 'success'),
(2, 'Встреча запланирована', 'Запланирована встреча с клиентом ЗАО "МегаТрейд"', 'info');
GO 