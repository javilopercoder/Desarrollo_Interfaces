<?php
/**
 * Header común para todas las páginas
 */

// Incluir archivo de sesiones
require_once __DIR__ . '/sesion.php';

// Iniciar sesión
iniciarSesion();

// Verificar si hay una cookie de usuario
verificarCookieUsuario();

// Función para determinar la ruta base según la ubicación del script
function getBasePath() {
    // Obtener la URL completa actual
    $request_uri = $_SERVER['REQUEST_URI'];
    
    // Detectar si estamos en una URL con doble 'admin'
    if (strpos($request_uri, '/admin/admin/') !== false) {
        // Estamos en /admin/admin/
        return '../../';
    } else if (strpos($request_uri, '/admin/') !== false) {
        // Estamos en /admin/
        return '../';
    }
    
    // Estamos en la raíz
    return '';
}

// Verificar si hay usuario logueado
$logged_in = estaAutenticado();
$is_admin = tieneRol('administrador');
$is_support = tieneRol('soporte');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Sistema de Ticketing' : 'Sistema de Ticketing'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="/assets/css/knowledge-styles.css">
    <link rel="stylesheet" href="/assets/css/featured-articles.css">
    <link rel="stylesheet" href="/assets/css/menu-fixes.css">
    <link rel="stylesheet" href="/assets/css/advanced-search.css">
    <link rel="stylesheet" href="/assets/css/breadcrumbs.css">
    <link rel="stylesheet" href="/assets/css/rating-system.css">
    <link rel="stylesheet" href="/assets/css/related-articles.css">
    <link rel="stylesheet" href="/assets/css/password-toggle.css">
    <!-- Estilos de búsqueda -->
    <link rel="stylesheet" href="/assets/css/search-box.css">
    <link rel="stylesheet" href="/assets/css/search-fixes.css">
    <link rel="stylesheet" href="/assets/css/filter-buttons.css">
    <link rel="stylesheet" href="/assets/css/contact.css">
</head>
<body>
    <header>
        <div class="container header-content">
            <a href="/index.php" class="logo">
                <i class="fas fa-ticket-alt"></i> Sistema de Ticketing
            </a>
            
            <button id="menu-toggle" class="menu-toggle d-md-none">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="/index.php">Inicio</a></li>
                    
                    <?php if ($logged_in): ?>
                        <li>
                            <a href="/tickets.php">Tickets</a>
                            <ul class="submenu">
                                <li><a href="/tickets.php">Ver todos</a></li>
                                <li><a href="/nuevo_ticket.php">Crear nuevo</a></li>
                                <?php if ($is_admin || $is_support): ?>
                                    <li><a href="/tickets.php?estado=abierto">Tickets abiertos</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        
                        <li><a href="/conocimientos.php">Base de conocimientos</a></li>
                        
                        <?php if ($is_admin): ?>
                            <li>
                                <a href="/admin/index.php">Administración</a>
                                <ul class="submenu">
                                    <li><a href="/admin/usuarios.php">Usuarios</a></li>
                                    <li><a href="/admin/reportes.php">Reportes</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        
                        <li>
                            <a href="/perfil.php">Mi cuenta</a>
                            <ul class="submenu">
                                <li><a href="/perfil.php">Perfil</a></li>
                                <li><a href="/logout.php">Cerrar sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="/conocimientos.php">Base de conocimientos</a></li>
                        <li><a href="/login.php">Iniciar sesión</a></li>
                        <li><a href="/registro.php">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <?php if (isset($show_visit_counter) && $show_visit_counter): ?>
            <div class="visit-counter">
                <p>Visitas: <span id="visit-counter"><?php echo isset($visitas) ? $visitas : 0; ?></span></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?>">
                <?php 
                    echo $_SESSION['mensaje']; 
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['mensaje_tipo']);
                ?>
            </div>
        <?php endif; ?>
