document.addEventListener('DOMContentLoaded', function() {
    // Функционал показа/скрытия пароля
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            // Изменяем тип поля ввода
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Валидация формы при отправке
    const loginForm = document.querySelector('.login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            const loginInput = document.getElementById('login');
            const passwordInput = document.getElementById('password');
            let isValid = true;
            
            // Проверка логина
            if (!loginInput.value.trim()) {
                isValid = false;
                showError(loginInput, 'Введите логин или email');
            } else {
                clearError(loginInput);
            }
            
            // Проверка пароля
            if (!passwordInput.value) {
                isValid = false;
                showError(passwordInput, 'Введите пароль');
            } else {
                clearError(passwordInput);
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    }
    
    // Функция отображения ошибки
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        input.classList.add('is-invalid');
        
        // Удаляем предыдущее сообщение об ошибке, если оно есть
        const existingError = formGroup.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
        
        // Создаем и добавляем новое сообщение об ошибке
        const error = document.createElement('div');
        error.className = 'invalid-feedback';
        error.innerText = message;
        
        // Добавляем после input-group
        const inputGroup = input.closest('.input-group');
        inputGroup.insertAdjacentElement('afterend', error);
    }
    
    // Функция очистки ошибки
    function clearError(input) {
        input.classList.remove('is-invalid');
        const formGroup = input.closest('.form-group');
        const error = formGroup.querySelector('.invalid-feedback');
        if (error) {
            error.remove();
        }
    }
    
    // Анимация при загрузке страницы
    const logoContainer = document.querySelector('.logo-container');
    if (logoContainer) {
        setTimeout(() => {
            logoContainer.classList.add('animate__animated', 'animate__pulse');
        }, 500);
    }
    
    // Добавляем эффект волны при нажатии на кнопку
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mousedown', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
}); 