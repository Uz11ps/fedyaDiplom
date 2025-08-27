/**
 * Главный JavaScript файл для приложения
 */
document.addEventListener('DOMContentLoaded', function() {
    // Применение темы
    applyTheme();
    
    // Показывать всплывающие подсказки Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Инициализация поповеров
    const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
    popovers.forEach(popover => {
        new bootstrap.Popover(popover);
    });
    
    // Анимация элементов при скролле
    const animateOnScroll = function() {
        const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
        
        elementsToAnimate.forEach(element => {
            const position = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (position < windowHeight - 50) {
                const animation = element.dataset.animation || 'fadeIn';
                element.classList.add('animate__animated', `animate__${animation}`);
                element.style.opacity = 1;
            }
        });
    };
    
    // Запуск анимации при скролле и при загрузке
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll();
    
    // Анимация карточек на странице
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = 1;
            card.style.transform = 'translateY(0)';
        }, index * 150);
    });
    
    // Интерактивные эффекты при наведении на элементы меню
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.classList.add('pulse');
        });
        
        link.addEventListener('mouseleave', function() {
            this.classList.remove('pulse');
        });
    });
    
    // Анимация для уведомлений
    const notificationBell = document.querySelector('.notification-bell');
    if (notificationBell) {
        setInterval(() => {
            notificationBell.classList.add('animate__animated', 'animate__tada');
            
            setTimeout(() => {
                notificationBell.classList.remove('animate__animated', 'animate__tada');
            }, 1000);
        }, 10000);
        
        // Автоматическое обновление счетчика уведомлений каждые 30 секунд
        setInterval(updateNotificationCount, 30000);
    }
    
    // Создание графиков на страницах статистики
    createCharts();
    
    // Обработка клика по значку уведомлений
    if (notificationBell) {
        // Удаляем обработчик события, так как теперь у нас есть прямая ссылка на страницу уведомлений
        notificationBell.classList.add('animated-bell');
        
        // Обновление количества непрочитанных уведомлений при загрузке страницы
        updateNotificationCount();
    }
    
    // Обработка поиска (если есть на странице)
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchInput = this.querySelector('input').value;
            
            if (searchInput.trim() !== '') {
                // Здесь код для обработки поиска
                console.log('Search for:', searchInput);
            }
        });
    }
});

/**
 * Применение темы ко всему сайту
 */
function applyTheme() {
    // Получение темы из куки
    const theme = getCookie('theme') || 'light';
    
    // Применение темы к HTML
    document.documentElement.setAttribute('data-bs-theme', theme);
    
    // Сохранение темы в localStorage для согласованности между страницами
    localStorage.setItem('theme', theme);
    
    // Обработчик переключения темы в настройках
    const themeRadios = document.querySelectorAll('input[name="theme"]');
    if (themeRadios.length > 0) {
        themeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Применение новой темы
                document.documentElement.setAttribute('data-bs-theme', this.value);
                // Сохранение темы в localStorage
                localStorage.setItem('theme', this.value);
            });
        });
    }
}

/**
 * Получение значения куки по имени
 */
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

// Функция для создания графиков на странице статистики
function createCharts() {
    // График клиентов по месяцам (если есть canvas с id "clientsChart")
    const clientsChartElement = document.getElementById('clientsChart');
    if (clientsChartElement) {
        const ctx = clientsChartElement.getContext('2d');
        
        const clientsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
                datasets: [{
                    label: 'Новые клиенты',
                    data: [12, 19, 3, 5, 2, 3, 20, 33, 15, 22, 14, 9],
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
    
    // График распределения клиентов по категориям (если есть canvas с id "categoriesChart")
    const categoriesChartElement = document.getElementById('categoriesChart');
    if (categoriesChartElement) {
        const ctx = categoriesChartElement.getContext('2d');
        
        const categoriesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Розничные', 'Корпоративные', 'Партнеры', 'VIP'],
                datasets: [{
                    data: [35, 25, 15, 25],
                    backgroundColor: [
                        'rgba(78, 115, 223, 0.8)',
                        'rgba(28, 200, 138, 0.8)',
                        'rgba(246, 194, 62, 0.8)',
                        'rgba(231, 74, 59, 0.8)'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right'
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
}

// Функция для обновления количества непрочитанных уведомлений
function updateNotificationCount() {
    fetch('/pages/get_unread_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector('.notification-bell .badge');
                if (badge) {
                    badge.textContent = data.count;
                    if (data.count == 0) {
                        badge.style.display = 'none';
                    } else {
                        badge.style.display = 'inline-block';
                    }
                }
            }
        })
        .catch(error => console.error('Ошибка при получении уведомлений:', error));
} 