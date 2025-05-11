<?php
/**
 * Archivo de verificación de rutas
 * Este archivo verifica si la URL contiene rutas duplicadas como "/admin/admin/"
 * y redirige a la ruta correcta si es necesario.
 */

// Verificar si la URL contiene "/admin/admin/"
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '/admin/admin/') !== false) {
    // Reemplazar "/admin/admin/" por "/admin/"
    $redirect_uri = str_replace('/admin/admin/', '/admin/', $request_uri);
    
    // Redirigir a la URL corregida
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $redirect_uri);
    exit;
}
?>
