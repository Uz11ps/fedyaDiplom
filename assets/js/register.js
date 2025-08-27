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
    
    // Проверка силы пароля
    const passwordInput = document.getElementById('password');
    const passwordStrengthDiv = document.querySelector('.password-strength');
    
    if (passwordInput && passwordStrengthDiv) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let message = '';
            let color = '';
            
            // Длина пароля
            if (password.length > 6) strength += 1;
            if (password.length > 10) strength += 1;
            
            // Наличие цифр
            if (/\d/.test(password)) strength += 1;
            
            // Наличие специальных символов
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;
            
            // Наличие букв разного регистра
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
            
            // Определение силы пароля
            if (password.length === 0) {
                passwordStrengthDiv.innerHTML = '';
                return;
            } else if (strength < 2) {
                message = 'Слабый пароль';
                color = '#e74a3b'; // Красный
            } else if (strength < 4) {
                message = 'Средний пароль';
                color = '#f6c23e'; // Желтый
            } else {
                message = 'Сильный пароль';
                color = '#1cc88a'; // Зеленый
            }
            
            // Создаем индикатор силы
            passwordStrengthDiv.innerHTML = `
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar" role="progressbar" 
                        style="width: ${strength * 20}%; background-color: ${color};" 
                        aria-valuenow="${strength * 20}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small style="color: ${color};">${message}</small>
            `;
        });
    }
    
    // Валидация формы при отправке
    const registerForm = document.querySelector('.register-form');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            let isValid = true;
            
            // Проверка имени пользователя
            if (!usernameInput.value.trim()) {
                isValid = false;
                showError(usernameInput, 'Введите имя пользователя');
            } else if (usernameInput.value.length < 3) {
                isValid = false;
                showError(usernameInput, 'Имя пользователя должно содержать не менее 3 символов');
            } else {
                clearError(usernameInput);
            }
            
            // Проверка email
            if (!emailInput.value.trim()) {
                isValid = false;
                showError(emailInput, 'Введите email');
            } else if (!isValidEmail(emailInput.value)) {
                isValid = false;
                showError(emailInput, 'Введите корректный email');
            } else {
                clearError(emailInput);
            }
            
            // Проверка пароля
            if (!passwordInput.value) {
                isValid = false;
                showError(passwordInput, 'Введите пароль');
            } else if (passwordInput.value.length < 6) {
                isValid = false;
                showError(passwordInput, 'Пароль должен содержать не менее 6 символов');
            } else {
                clearError(passwordInput);
            }
            
            // Проверка подтверждения пароля
            if (!confirmPasswordInput.value) {
                isValid = false;
                showError(confirmPasswordInput, 'Подтвердите пароль');
            } else if (confirmPasswordInput.value !== passwordInput.value) {
                isValid = false;
                showError(confirmPasswordInput, 'Пароли не совпадают');
            } else {
                clearError(confirmPasswordInput);
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    }
    
    // Функция проверки email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
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
    
    // Анимация элементов формы
    const formElements = document.querySelectorAll('.form-group');
    formElements.forEach((element, index) => {
        setTimeout(() => {
            element.classList.add('animate__animated', 'animate__fadeInUp');
        }, index * 100);
    });
    
    // Эффект при наведении на кнопки
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            anime({
                targets: this,
                scale: 1.05,
                duration: 300,
                easing: 'easeInOutQuad'
            });
        });
        
        button.addEventListener('mouseleave', function() {
            anime({
                targets: this,
                scale: 1,
                duration: 300,
                easing: 'easeInOutQuad'
            });
        });
    });
}); 