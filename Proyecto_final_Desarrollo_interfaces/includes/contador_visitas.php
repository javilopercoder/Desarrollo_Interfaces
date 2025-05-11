<?php
/**
 * Archivo para gestionar el contador de visitas
 * 
 * Versión mejorada que utiliza cookies para evitar contar múltiples visitas
 * del mismo usuario en un período corto de tiempo y registra estadísticas
 * más completas sobre las visitas.
 */

// Ruta a los archivos del contador
$contador_file = __DIR__ . '/../data/contador.txt';
$contador_log = __DIR__ . '/../data/visitas.log';

/**
 * Función para incrementar y obtener el contador de visitas
 * 
 * @param bool $count_unique_only Si es true, solo cuenta visitantes únicos (por defecto)
 * @param int $cookie_expiry Tiempo de expiración de la cookie en segundos (por defecto 1 hora)
 * @return int El número total de visitas
 */
function getContadorVisitas($count_unique_only = true, $cookie_expiry = 3600) {
    global $contador_file, $contador_log;
    
    // Asegurarse que el directorio existe
    $dir = dirname($contador_file);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Si el archivo no existe, crearlo con valor inicial 0
    if (!file_exists($contador_file)) {
        file_put_contents($contador_file, '0');
        $contador = 0;
    } else {
        // Leer el valor actual
        $contador = (int) file_get_contents($contador_file);
    }
    
    // Si queremos contar solo visitantes únicos, comprobar la cookie
    $is_new_visit = true;
    if ($count_unique_only) {
        $cookie_name = 'visitor_counted';
        
        if (isset($_COOKIE[$cookie_name])) {
            // El usuario ya ha visitado la página recientemente
            $is_new_visit = false;
        } else {
            // Establecer una cookie para este usuario
            setcookie($cookie_name, '1', time() + $cookie_expiry, '/');
            $is_new_visit = true;
        }
    }
    
    // Solo incrementar el contador si es una nueva visita o si contamos todas las visitas
    if ($is_new_visit || !$count_unique_only) {
        $contador++;
        file_put_contents($contador_file, (string) $contador);
        
        // Registrar esta visita en el log
        logVisit($is_new_visit);
    }
    
    return $contador;
}

/**
 * Función para registrar detalles de la visita en un archivo log
 * 
 * @param bool $is_new_visit Si es una nueva visita o no
 */
function logVisit($is_new_visit) {
    global $contador_log;
    
    // Recopilar información sobre la visita
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'direct';
    $page = $_SERVER['REQUEST_URI'] ?? '/';
    $status = $is_new_visit ? 'NEW' : 'REPEAT';
    
    // Formatear la entrada del log
    $log_entry = json_encode([
        'date' => $date,
        'ip' => $ip,
        'user_agent' => $user_agent,
        'referer' => $referer,
        'page' => $page,
        'status' => $status
    ]);
    
    // Añadir al archivo log
    file_put_contents($contador_log, $log_entry . "\n", FILE_APPEND);
}
?>
