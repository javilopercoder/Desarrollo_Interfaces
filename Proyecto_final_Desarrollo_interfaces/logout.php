<?php
/**
 * Script para cerrar sesión
 */

// Incluir archivo de sesión
require_once 'includes/sesion.php';

// Cerrar la sesión del usuario
cerrarSesion();

// Eliminar cookie de usuario si existe
if (isset($_COOKIE['usuario_data'])) {
    setcookie('usuario_data', '', time() - 3600, '/');
}

// Redirigir a la página de inicio
header('Location: index.php');
exit;
