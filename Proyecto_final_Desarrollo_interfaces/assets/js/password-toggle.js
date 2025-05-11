/**
 * Funcionalidad para mostrar/ocultar contraseñas
 */

document.addEventListener('DOMContentLoaded', function() {
    // Obtener todos los botones de toggle de contraseña
    const toggleButtons = document.querySelectorAll('.password-toggle-btn');
    
    // Añadir evento a cada botón
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            // Obtener el campo de contraseña asociado (hermano previo al botón)
            const passwordField = this.parentNode.querySelector('input');
            
            // Cambiar el tipo de campo
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Cambiar el ícono
            const icon = this.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.setAttribute('title', 'Ocultar contraseña');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.setAttribute('title', 'Mostrar contraseña');
            }
        });
    });
});
