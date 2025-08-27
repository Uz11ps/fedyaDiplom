<footer class="main-footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="footer-logo">
                    <h4>ООО "Аплана.ИТ"</h4>
                    <p>Система учёта клиентов</p>
                </div>
                <div class="footer-social">
                    <a href="#" class="social-icon"><i class="fab fa-vk"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-telegram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            
            <div class="col-md-4">
                <h5>Навигация</h5>
                <ul class="footer-links">
                    <li><a href="/pages/main.php"><i class="fas fa-home"></i> Главная</a></li>
                    <li><a href="/pages/clients.php"><i class="fas fa-users"></i> Клиенты</a></li>
                    <li><a href="/pages/stats.php"><i class="fas fa-chart-bar"></i> Статистика</a></li>
                    <li><a href="/pages/settings.php"><i class="fas fa-cog"></i> Настройки</a></li>
                </ul>
            </div>
            
            <div class="col-md-4">
                <h5>Контактная информация</h5>
                <address>
                    <p><i class="fas fa-map-marker-alt"></i> г. Москва, ул. Примерная, д. 123</p>
                    <p><i class="fas fa-phone"></i> +7 (495) 123-45-67</p>
                    <p><i class="fas fa-envelope"></i> info@aplana-it.ru</p>
                </address>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> ООО "Аплана.ИТ". Все права защищены.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Версия: 1.0.0 | Последнее обновление: <?php echo date('d.m.Y'); ?></p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Дополнительные скрипты -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Анимация элементов при скролле
    const animateOnScroll = function() {
        const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
        
        elementsToAnimate.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 50) {
                const animationClass = element.getAttribute('data-animation') || 'animate__fadeIn';
                element.classList.add('animate__animated', animationClass);
            }
        });
    };
    
    // Инициализация тултипов и поповеров
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Активация анимации при скролле
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Запуск при загрузке страницы
});
</script> 