/**
 * Script principal para el sistema de ticketing
 */

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    
    // Gestión de cookies
    initCookieConsent();
    
    // Contador de visitas
    if (typeof updateVisitCounter === 'function') {
        updateVisitCounter();
    }
    
    // Validación de formularios
    if (typeof initFormValidation === 'function') {
        initFormValidation();
    }
    
    // Menú responsive
    initResponsiveMenu();
    
    // Inicializa los tooltips
    initTooltips();
    
    // Inicializa los inputs de archivos
    initFileInputs();
    
    // Verificar si existen mensajes flash y configurar su autodesaparición
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                fadeOut(alert);
            });
        }, 5000); // Desaparece después de 5 segundos
    }
});

/**
 * Inicializa el aviso de consentimiento de cookies
 */
function initCookieConsent() {
    // Verificar si ya se ha aceptado
    if (!localStorage.getItem('cookieConsent')) {
        // Crear el elemento de aviso
        const cookieConsent = document.createElement('div');
        cookieConsent.className = 'cookie-consent';
        cookieConsent.innerHTML = `
            <p>Este sitio utiliza cookies para mejorar la experiencia del usuario. Al continuar navegando, acepta nuestro uso de cookies.</p>
            <button class="btn" id="accept-cookies">Aceptar</button>
        `;
        
        document.body.appendChild(cookieConsent);
        
        // Manejador del botón de aceptar
        document.getElementById('accept-cookies').addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'accepted');
            cookieConsent.style.display = 'none';
        });
    }
}

/**
 * Actualiza el contador de visitas
 */
function updateVisitCounter() {
    const counterElement = document.getElementById('visit-counter');
    
    if (counterElement) {
        // Obtener el contador actual
        let count = parseInt(localStorage.getItem('visitCount') || '0');
        
        // Incrementar solo si es una nueva sesión
        if (!sessionStorage.getItem('counted')) {
            count++;
            localStorage.setItem('visitCount', count.toString());
            sessionStorage.setItem('counted', 'true');
        }
        
        // Mostrar el contador
        counterElement.textContent = count;
    }
}

/**
 * Inicializa la validación de formularios
 */
function initFormValidation() {
    // Formulario de login
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario');
            const password = document.getElementById('password');
            let isValid = true;
            
            // Limpiar mensajes de error previos
            clearErrors();
            
            // Validar usuario
            if (!validateUsername(usuario.value)) {
                displayError(usuario, 'El nombre de usuario debe tener entre 10 y 30 caracteres, sin caracteres especiales y no puede comenzar con un número');
                isValid = false;
            }
            
            // Validar contraseña
            if (!validatePassword(password.value)) {
                displayError(password, 'La contraseña debe tener entre 5 y 20 caracteres, al menos un número y una letra mayúscula');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Formulario de registro de usuario
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario');
            const password = document.getElementById('password');
            const email = document.getElementById('email');
            let isValid = true;
            
            // Limpiar mensajes de error previos
            clearErrors();
            
            // Validar usuario
            if (!validateUsername(usuario.value)) {
                displayError(usuario, 'El nombre de usuario debe tener entre 10 y 30 caracteres, sin caracteres especiales y no puede comenzar con un número');
                isValid = false;
            }
            
            // Validar contraseña
            if (!validatePassword(password.value)) {
                displayError(password, 'La contraseña debe tener entre 5 y 20 caracteres, al menos un número y una letra mayúscula');
                isValid = false;
            }
            
            // Validar email
            if (!validateEmail(email.value)) {
                displayError(email, 'Por favor ingrese un email válido');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Formulario de nuevo ticket
    const ticketForm = document.getElementById('ticket-form');
    if (ticketForm) {
        ticketForm.addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo');
            const descripcion = document.getElementById('descripcion');
            let isValid = true;
            
            // Limpiar mensajes de error previos
            clearErrors();
            
            // Validar título
            if (titulo.value.trim() === '') {
                displayError(titulo, 'El título es obligatorio');
                isValid = false;
            }
            
            // Validar descripción
            if (descripcion.value.trim() === '') {
                displayError(descripcion, 'La descripción es obligatoria');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Valida el nombre de usuario
 */
function validateUsername(username) {
    return /^[a-zA-Z][a-zA-Z0-9]{9,29}$/.test(username);
}

/**
 * Valida la contraseña
 */
function validatePassword(password) {
    return /^(?=.*[0-9])(?=.*[A-Z]).{5,20}$/.test(password);
}

/**
 * Valida el email
 */
function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Muestra un mensaje de error para un campo del formulario
 */
function displayError(inputElement, message) {
    inputElement.classList.add('error');
    
    const errorElement = document.createElement('div');
    errorElement.className = 'error-message';
    errorElement.textContent = message;
    
    inputElement.parentNode.appendChild(errorElement);
}

/**
 * Limpia todos los mensajes de error
 */
function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    const inputErrors = document.querySelectorAll('.error');
    
    errorElements.forEach(element => {
        element.parentNode.removeChild(element);
    });
    
    inputErrors.forEach(input => {
        input.classList.remove('error');
    });
}

/**
 * Inicializa el menú responsive
 */
function initResponsiveMenu() {
    const menuToggle = document.getElementById('menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            
            // Cambiar el icono según el estado del menú
            const icon = this.querySelector('i');
            if (icon) {
                if (mainNav.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
        
        // Cerrar el menú al hacer clic fuera de él
        document.addEventListener('click', function(event) {
            if (mainNav.classList.contains('active') && 
                !event.target.closest('.main-nav') && 
                !event.target.closest('#menu-toggle')) {
                
                mainNav.classList.remove('active');
                
                // Restaurar el icono
                const icon = menuToggle.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
}

/**
 * Inicializa los tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(function(tooltip) {
        tooltip.addEventListener('mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'tooltip';
            tooltipEl.textContent = text;
            document.body.appendChild(tooltipEl);
            
            const rect = this.getBoundingClientRect();
            tooltipEl.style.top = (rect.top - tooltipEl.offsetHeight - 10) + 'px';
            tooltipEl.style.left = rect.left + (rect.width / 2) - (tooltipEl.offsetWidth / 2) + 'px';
            tooltipEl.style.opacity = '1';
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipEl = document.querySelector('.tooltip');
            if (tooltipEl) {
                tooltipEl.remove();
            }
        });
    });
}

/**
 * Inicializa los campos de archivo para mostrar el nombre del archivo seleccionado
 */
function initFileInputs() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const fileName = this.files.length > 1 
                ? this.files.length + ' archivos seleccionados' 
                : this.files[0] ? this.files[0].name : 'Seleccionar archivo';
            
            // Buscar el elemento de nombre de archivo (si existe)
            const fileNameElement = this.parentElement.querySelector('.file-name');
            if (fileNameElement) {
                fileNameElement.textContent = fileName;
            } else {
                // Crear el elemento si no existe
                const span = document.createElement('span');
                span.className = 'file-name';
                span.textContent = fileName;
                this.parentElement.appendChild(span);
            }
        });
    });
}

/**
 * Desvanece un elemento
 * 
 * @param {Element} element - Elemento a desvanecer
 * @param {Function} callback - Función a ejecutar al terminar
 */
function fadeOut(element, callback) {
    let opacity = 1;
    const timer = setInterval(function() {
        if (opacity <= 0.1) {
            clearInterval(timer);
            element.style.display = 'none';
            if (callback) {
                callback();
            }
        }
        element.style.opacity = opacity;
        opacity -= 0.1;
    }, 50);
}
