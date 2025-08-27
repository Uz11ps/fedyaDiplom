# 🏢 Система учёта клиентов "ООО Аплана.ИТ"

<div align="center">

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![SQL Server](https://img.shields.io/badge/SQL%20Server-2016+-CC2927?style=for-the-badge&logo=microsoft-sql-server&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**Современная веб-система для автоматизации учёта клиентов и управления заказами**

[Демо](http://aplana-0.ru) • [Документация](#документация) • [Установка](#установка-и-настройка) • [Поддержка](#контакты-для-поддержки)

</div>

---

## 📋 Описание проекта

**Система учёта клиентов "ООО Аплана.ИТ"** — это комплексное веб-приложение для автоматизации бизнес-процессов компании. Разработано на PHP с использованием Microsoft SQL Server и современных веб-технологий.

### 🎯 Основные цели системы:
- Централизованное управление базой клиентов
- Автоматизация процессов ведения заказов
- Формирование аналитических отчётов
- Повышение эффективности работы менеджеров

---

## ✨ Функциональные возможности

### 👥 Управление клиентами
- ➕ Добавление новых клиентов с полной контактной информацией
- ✏️ Редактирование данных существующих клиентов
- 🗑️ Безопасное удаление записей
- 🔍 Быстрый поиск и фильтрация по различным критериям
- 📊 Просмотр истории взаимодействий

### 📦 Система заказов
- 🆕 Создание новых заказов с привязкой к клиентам
- 📝 Ведение детальной истории заказов
- 💰 Отслеживание статусов и сумм заказов
- 📅 Календарное планирование выполнения

### 📈 Аналитика и отчётность
- 📊 Интерактивные графики и диаграммы
- 📋 Детализированные отчёты по клиентам и заказам
- 📤 Экспорт данных в Excel формат
- 🔗 Функция "Поделиться" для совместной работы
- 📈 Статистика продаж и конверсии

### 🔐 Система безопасности
- 🔑 Многоуровневая аутентификация пользователей
- 👤 Управление ролями и правами доступа
- 🛡️ Защита от SQL-инъекций
- 🔒 Безопасное хранение паролей

### 🔔 Уведомления
- ⚡ Система уведомлений о важных событиях
- 📧 Email-рассылки
- 🔔 Внутренние уведомления системы

---

## 🛠 Технологический стек

### Backend
- **PHP 7.4+** - Серверная логика
- **Microsoft SQL Server 2016+** - База данных
- **PDO/SQLSRV** - Работа с БД

### Frontend
- **HTML5/CSS3** - Разметка и стилизация
- **Bootstrap 5.3** - UI фреймворк
- **JavaScript (ES6+)** - Интерактивность
- **jQuery** - AJAX запросы
- **Chart.js** - Графики и диаграммы
- **DataTables** - Работа с таблицами

### Дополнительные библиотеки
- **Font Awesome 6** - Иконки
- **Animate.css** - Анимации
- **PHPSpreadsheet** - Экспорт в Excel

---

## 🚀 Установка и настройка

### Системные требования

```
✅ PHP 7.4 или выше
✅ Microsoft SQL Server 2016+
✅ Расширения PHP: sqlsrv, mbstring, json, session
✅ Веб-сервер: Apache/Nginx
✅ Минимум 512MB RAM
✅ 100MB свободного места на диске
```

### Пошаговая установка

#### 1️⃣ Клонирование репозитория
```bash
git clone https://github.com/Uz11ps/fedyaDiplom.git
cd fedyaDiplom
```

#### 2️⃣ Настройка базы данных
1. Создайте новую базу данных в SQL Server:
```sql
CREATE DATABASE aplana_it_clients;
```

2. Выполните SQL-скрипт для создания структуры:
```bash
# Импортируйте файл sql/database.sql в вашу БД
```

#### 3️⃣ Конфигурация подключения
Отредактируйте файл `config/database.php`:
```php
<?php
$serverName = "your_server_name";
$connectionOptions = array(
    "Database" => "aplana_it_clients",
    "Uid" => "your_username",
    "PWD" => "your_password"
);
?>
```

#### 4️⃣ Настройка веб-сервера

**Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/fedyaDiplom;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

---

## 🎮 Начало работы

### Первый запуск
1. Откройте браузер и перейдите по адресу вашего сайта
2. Используйте тестовые данные для входа:
   - **Логин:** `admin`
   - **Пароль:** `admin123`

### Основные разделы системы
- **📊 Главная** - Дашборд с общей статистикой
- **👥 Клиенты** - Управление базой клиентов
- **📦 Заказы** - Ведение заказов и сделок
- **📈 Аналитика** - Отчёты и графики
- **⚙️ Настройки** - Конфигурация системы

---

## 📁 Структура проекта

```
fedyaDiplom/
├── 📁 assets/              # Статические ресурсы
│   ├── 🎨 css/            # Стили CSS
│   ├── 🖼️ images/         # Изображения
│   └── ⚡ js/             # JavaScript файлы
├── ⚙️ config/             # Конфигурационные файлы
├── 📄 pages/              # PHP страницы приложения
├── 🗄️ sql/               # SQL скрипты
├── 🎭 templates/          # Шаблоны (header, footer)
├── 📤 uploads/            # Загруженные файлы
├── 🔧 utils/             # Утилиты и вспомогательные классы
├── 📋 README.md          # Документация проекта
└── 🚫 .gitignore         # Исключения для Git
```

---

## 🔧 API и интеграции

### AJAX Endpoints
```javascript
// Получение списка клиентов
GET /pages/clients.php?action=list

// Добавление нового клиента
POST /pages/add_client.php

// Экспорт данных
GET /pages/export_orders.php?format=excel
```

### Функции экспорта
- **Excel** - Полная выгрузка данных в .xlsx
- **CSV** - Совместимость с внешними системами
- **PDF** - Готовые отчёты для печати

---

## 🎨 Скриншоты

<details>
<summary>🖼️ Посмотреть скриншоты интерфейса</summary>

### Главная страница
![Главная страница](assets/images/screenshots/dashboard.png)

### Управление клиентами
![Клиенты](assets/images/screenshots/clients.png)

### Аналитика
![Аналитика](assets/images/screenshots/analytics.png)

</details>

---

## 🚀 Развертывание

### Production окружение
```bash
# Клонирование на сервер
git clone https://github.com/Uz11ps/fedyaDiplom.git /var/www/aplana-crm

# Настройка прав доступа
chmod -R 755 /var/www/aplana-crm
chown -R www-data:www-data /var/www/aplana-crm

# Настройка SSL (рекомендуется)
certbot --nginx -d your-domain.com
```

### Docker (опционально)
```dockerfile
FROM php:7.4-apache
RUN docker-php-ext-install pdo pdo_sqlsrv
COPY . /var/www/html/
EXPOSE 80
```

---

## 🤝 Участие в разработке

Мы приветствуем вклад в развитие проекта! 

### Как внести свой вклад:
1. 🍴 Сделайте Fork репозитория
2. 🌿 Создайте ветку для новой функции (`git checkout -b feature/AmazingFeature`)
3. 💾 Зафиксируйте изменения (`git commit -m 'Add some AmazingFeature'`)
4. 📤 Отправьте в ветку (`git push origin feature/AmazingFeature`)
5. 🔄 Откройте Pull Request

### Правила разработки:
- Следуйте PSR-12 стандартам кодирования
- Добавляйте комментарии к сложной логике
- Тестируйте новый функционал
- Обновляйте документацию при необходимости

---

## 📝 Changelog

### v2.1.0 (Текущая версия)
- ✅ Исправлены ошибки экспорта в Excel
- ✅ Улучшена функция "Поделиться"
- ✅ Добавлена поддержка новых полей в заказах
- ✅ Оптимизирована производительность запросов

### v2.0.0
- 🆕 Полное обновление интерфейса
- 🆕 Система уведомлений
- 🆕 Расширенная аналитика
- 🆕 Мобильная адаптация

<details>
<summary>📜 Показать полную историю версий</summary>

### v1.5.0
- Добавлен экспорт в Excel
- Улучшена система поиска
- Исправлены критические ошибки безопасности

### v1.0.0
- Первый стабильный релиз
- Базовый функционал CRUD операций
- Система аутентификации

</details>

---

## 🐛 Известные проблемы и решения

### Частые вопросы

**Q: Ошибка подключения к базе данных**
```
A: Проверьте настройки в config/database.php и убедитесь, 
   что SQL Server запущен и доступен
```

**Q: Не работает экспорт в Excel**
```
A: Запустите скрипт fix_database.php для обновления структуры БД
```

**Q: Проблемы с правами доступа**
```
A: Убедитесь, что папка uploads/ имеет права на запись (755)
```

---

## 📊 Статистика проекта

![GitHub stars](https://img.shields.io/github/stars/Uz11ps/fedyaDiplom?style=social)
![GitHub forks](https://img.shields.io/github/forks/Uz11ps/fedyaDiplom?style=social)
![GitHub issues](https://img.shields.io/github/issues/Uz11ps/fedyaDiplom)
![GitHub pull requests](https://img.shields.io/github/issues-pr/Uz11ps/fedyaDiplom)

---

## 📄 Лицензия

Этот проект распространяется под лицензией MIT. Подробности в файле [LICENSE](LICENSE).

```
MIT License

Copyright (c) 2024 Uz11ps

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software...
```

---

## 📞 Контакты для поддержки

<div align="center">

### 🔗 Связаться с нами

[![Email](https://img.shields.io/badge/Email-support@aplana--it.ru-D14836?style=for-the-badge&logo=gmail&logoColor=white)](mailto:support@aplana-it.ru)
[![Phone](https://img.shields.io/badge/Phone-+7%20(495)%20123--45--67-25D366?style=for-the-badge&logo=whatsapp&logoColor=white)](tel:+74951234567)
[![GitHub](https://img.shields.io/badge/GitHub-Uz11ps-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/Uz11ps)

### 💼 Техническая поддержка
- **Время работы:** Пн-Пт 9:00-18:00 (МСК)
- **Ответ на email:** в течение 24 часов
- **Экстренная поддержка:** +7 (495) 123-45-67

</div>

---

<div align="center">

**⭐ Если проект оказался полезным, поставьте звездочку!**

Made with ❤️ by [Uz11ps](https://github.com/Uz11ps)

</div>