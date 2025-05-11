<?php
/**
 * Gestión de sesiones y autenticación
 */

// Incluir archivo de utilidades
require_once __DIR__ . '/utilidades.php';

/**
 * Inicia la sesión si no está iniciada
 */
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verifica si el usuario está autenticado
 *
 * @return boolean True si el usuario está autenticado, false en caso contrario
 */
function estaAutenticado() {
    iniciarSesion();
    return isset($_SESSION['usuario_id']);
}

/**
 * Verifica si el usuario tiene un rol específico
 *
 * @param string|array $roles Rol o roles permitidos
 * @return boolean True si el usuario tiene el rol, false en caso contrario
 */
function tieneRol($roles) {
    if (!estaAutenticado()) {
        return false;
    }
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['rol'], $roles);
}

/**
 * Requiere autenticación para acceder a la página
 * Redirige a login si no está autenticado
 */
function requireLogin() {
    if (!estaAutenticado()) {
        $_SESSION['mensaje'] = 'Por favor, inicie sesión para acceder a esta página.';
        $_SESSION['mensaje_tipo'] = 'warning';
        header('Location: login.php');
        exit;
    }
}

/**
 * Requiere un rol específico para acceder a la página
 * 
 * @param string|array $roles Rol o roles permitidos
 */
function requireRole($roles) {
    requireLogin();
    
    if (!tieneRol($roles)) {
        $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta página.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: index.php');
        exit;
    }
}

/**
 * Autentica al usuario y establece las variables de sesión
 * 
 * @param array $usuario Datos del usuario
 */
function autenticarUsuario($usuario) {
    iniciarSesion();
    
    $_SESSION['usuario_id'] = $usuario['id_usuario'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['correo'] = $usuario['correo'];
    $_SESSION['rol'] = $usuario['rol'];
    
    // Registrar la última vez que inició sesión
    registrarUltimoLogin($usuario['id_usuario']);
}

/**
 * Registra la fecha y hora del último inicio de sesión
 * 
 * @param int $id_usuario ID del usuario
 */
function registrarUltimoLogin($id_usuario) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE usuarios SET ultimo_login = CURRENT_TIMESTAMP WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
    } catch (PDOException $e) {
        // Silenciar error, no es crítico
    }
}

/**
 * Cierra la sesión del usuario
 */
function cerrarSesion() {
    iniciarSesion();
    
    // Eliminar todas las variables de sesión
    $_SESSION = array();
    
    // Eliminar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}

/**
 * Establece una cookie con datos de usuario
 * 
 * @param array $usuario Datos del usuario
 * @param boolean $recordar Si se debe recordar al usuario
 */
function establecerCookieUsuario($usuario, $recordar = false) {
    if ($recordar) {
        $cookieData = json_encode([
            'id' => $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo']
        ]);
        
        // Cookie válida por 30 días
        setcookie('usuario_data', $cookieData, time() + (30 * 24 * 60 * 60), '/');
    }
}

/**
 * Verifica si hay una cookie de usuario y lo autentica
 */
function verificarCookieUsuario() {
    if (!estaAutenticado() && isset($_COOKIE['usuario_data'])) {
        $userData = json_decode($_COOKIE['usuario_data'], true);
        
        if ($userData && isset($userData['id'])) {
            try {
                $db = getDB();
                $stmt = $db->prepare('SELECT id_usuario, nombre, correo, rol FROM usuarios WHERE id_usuario = ?');
                $stmt->execute([$userData['id']]);
                $usuario = $stmt->fetch();
                
                if ($usuario) {
                    autenticarUsuario($usuario);
                }
            } catch (PDOException $e) {
                // Error al verificar usuario, continuar sin autenticar
            }
        }
    }
}
?>
