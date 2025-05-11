/**
 * Funciones para la gestión de cookies
 */

// Al cargar la página, comprobamos si debe mostrarse el aviso de cookies
document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar si la función getCookie existe
    if (typeof getCookie === 'function' && !getCookie('cookies_accepted')) {
        showCookieNotice();
    }
});

/**
 * Muestra el aviso de cookies
 */
function showCookieNotice() {
    // Si ya existe el elemento, no lo creamos de nuevo
    if (document.getElementById('cookie-notice')) {
        return;
    }
    
    // Crear el elemento para el aviso
    const cookieNotice = document.createElement('div');
    cookieNotice.id = 'cookie-notice';
    cookieNotice.className = 'cookie-notice';
    
    // Contenido del aviso
    cookieNotice.innerHTML = `
        <div class="cookie-content">
            <p>Este sitio utiliza cookies para mejorar la experiencia de usuario. 
            Al continuar usando este sitio, acepta nuestro uso de cookies.</p>
            <div class="cookie-buttons">
                <button id="accept-cookies" class="btn btn-primary">Aceptar</button>
                <button id="reject-cookies" class="btn">Rechazar</button>
            </div>
        </div>
    `;
    
    // Añadir el aviso al final del body
    document.body.appendChild(cookieNotice);
    
    // Añadir estilos al aviso
    const style = document.createElement('style');
    style.textContent = `
        .cookie-notice {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 1rem;
            z-index: 1000;
            display: flex;
            justify-content: center;
        }
        .cookie-content {
            max-width: 1200px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .cookie-content p {
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }
        .cookie-buttons {
            display: flex;
            gap: 0.5rem;
        }
        @media (max-width: 768px) {
            .cookie-content {
                flex-direction: column;
                text-align: center;
            }
            .cookie-content p {
                margin-right: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Añadir eventos a los botones
    document.getElementById('accept-cookies').addEventListener('click', function() {
        setCookie('cookies_accepted', 'true', 365);
        hideCookieNotice();
    });
    
    document.getElementById('reject-cookies').addEventListener('click', function() {
        hideCookieNotice();
    });
}

/**
 * Oculta el aviso de cookies
 */
function hideCookieNotice() {
    const cookieNotice = document.getElementById('cookie-notice');
    if (cookieNotice) {
        cookieNotice.remove();
    }
}

/**
 * Establece una cookie
 * 
 * @param {string} name - Nombre de la cookie
 * @param {string} value - Valor de la cookie
 * @param {number} days - Días de validez
 */
function setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

/**
 * Obtiene el valor de una cookie
 * 
 * @param {string} name - Nombre de la cookie
 * @return {string} Valor de la cookie o cadena vacía si no existe
 */
function getCookie(name) {
    const cookieName = name + "=";
    const cookies = document.cookie.split(';');
    
    for (let i = 0; i < cookies.length; i++) {
        let cookie = cookies[i].trim();
        if (cookie.indexOf(cookieName) === 0) {
            return cookie.substring(cookieName.length, cookie.length);
        }
    }
    
    return "";
}
