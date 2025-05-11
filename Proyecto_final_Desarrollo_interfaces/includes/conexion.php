<?php
/**
 * Archivo de conexión a la base de datos SQLite
 */

// Ruta al archivo de la base de datos
$db_path = __DIR__ . '/../db/ticketing.db';

// Función para obtener la conexión a la base de datos
function getDB() {
    global $db_path;
    
    try {
        // Crear conexión a la base de datos SQLite
        $db = new PDO('sqlite:' . $db_path);
        
        // Configurar para lanzar excepciones en caso de error
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Configurar para devolver resultados asociativos
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $db;
    } catch (PDOException $e) {
        // En caso de error, mostrar mensaje y terminar script
        die("Error de conexión: " . $e->getMessage());
    }
}

// Función para registrar un acceso en el archivo de log
function registrarAcceso($usuario) {
    $logfile = __DIR__ . '/../logs/accesos.txt';
    
    // Crear directorio de logs si no existe
    $logdir = dirname($logfile);
    if (!file_exists($logdir)) {
        mkdir($logdir, 0777, true);
    }
    
    // Registrar el acceso con fecha y hora
    $fecha = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    $navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
    
    $registro = "$fecha | Usuario: $usuario | IP: $ip | Navegador: $navegador\n";
    
    // Escribir en el archivo
    file_put_contents($logfile, $registro, FILE_APPEND);
}

// Función para sanear entradas de texto (prevenir XSS)
if (!function_exists('sanitizar')) {
    function sanitizar($texto) {
        return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
    }
}

// Función para validar nombre de usuario
if (!function_exists('validarNombreUsuario_simple')) {
    function validarNombreUsuario_simple($nombre) {
        // No contener caracteres especiales, longitud entre 10-30, no comenzar con número
        return preg_match('/^[a-zA-Z][a-zA-Z0-9]{9,29}$/', $nombre);
    }
}

// Función para validar contraseña simple
if (!function_exists('validarContrasenaSimple')) {
    function validarContrasenaSimple($contrasena) {
        // Al menos un número, una mayúscula, longitud entre 5-20
        return preg_match('/^(?=.*[0-9])(?=.*[A-Z]).{5,20}$/', $contrasena);
    }
}

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
