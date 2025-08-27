/**
 * Обработка уведомлений
 */
document.addEventListener('DOMContentLoaded', function() {
    // Обработка клика по колокольчику
    function setupNotificationBell() {
        const notificationBell = document.querySelector('.notification-bell');
        if (notificationBell) {
            const bellLink = notificationBell.querySelector('a');
            
            if (bellLink) {
                // Добавляем обработчик события
                bellLink.addEventListener('click', function(e) {
                    e.preventDefault(); // Предотвращаем стандартное поведение
                    e.stopPropagation(); // Останавливаем всплытие события
                    
                    // Переходим на страницу уведомлений
                    window.location.href = '/pages/notifications.php';
                    return false;
                });
            }
        }
    }
    
    // Получение количества непрочитанных уведомлений
    function updateNotificationCount() {
        fetch('/pages/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-bell .badge');
                if (badge) {
                    badge.textContent = data.count;
                    if (data.count == 0) {
                        badge.style.display = 'none';
                    } else {
                        badge.style.display = 'inline-block';
                    }
                }
            })
            .catch(error => console.error('Ошибка:', error));
    }
    
    // Инициализация
    setupNotificationBell();
    
    // Обновляем каждые 60 секунд
    setInterval(updateNotificationCount, 60000);
}); 