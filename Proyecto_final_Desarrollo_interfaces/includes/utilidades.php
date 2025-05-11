<?php
/**
 * Archivo de funciones auxiliares para validación
 */

/**
 * Sanea una cadena de texto
 * 
 * @param string $data Datos a sanitizar
 * @return string Datos sanitizados
 */
if (!function_exists('sanitizar')) {
    function sanitizar($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

/**
 * Valida un correo electrónico
 * 
 * @param string $email Correo a validar
 * @return bool True si es válido, false en caso contrario
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida la fortaleza de una contraseña
 * 
 * @param string $password Contraseña a validar
 * @return array Array con errores o vacío si es válida
 */
if (!function_exists('validarPassword')) {
    function validarPassword($password) {
        $errores = [];
        
        // Verificar longitud
        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        
        // Verificar que tenga al menos una letra mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos una letra mayúscula.';
        }
        
        // Verificar que tenga al menos una letra minúscula
        if (!preg_match('/[a-z]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos una letra minúscula.';
        }
        
        // Verificar que tenga al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos un número.';
        }
        
        // Verificar que tenga al menos un carácter especial
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos un carácter especial (!@#$%^&*()-_=+{};:,<.>).';
        }
        
        return $errores;
    }
}

/**
 * Formatea una fecha para mostrar
 * 
 * @param string $fecha Fecha en formato MySQL
 * @return string Fecha formateada
 */
function formatearFecha($fecha) {
    $timestamp = strtotime($fecha);
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Formatea una diferencia de tiempo de manera legible
 * 
 * @param string $fecha_anterior Fecha anterior en formato MySQL
 * @param string $fecha_actual Fecha actual en formato MySQL (opcional)
 * @return string Tiempo transcurrido en formato legible
 */
function tiempoTranscurrido($fecha_anterior, $fecha_actual = null) {
    if (!$fecha_actual) {
        $fecha_actual = date('Y-m-d H:i:s');
    }
    
    $ts1 = strtotime($fecha_anterior);
    $ts2 = strtotime($fecha_actual);
    $diff = $ts2 - $ts1;
    
    $minutos = round($diff / 60);
    
    if ($minutos < 1) {
        return 'hace unos segundos';
    } elseif ($minutos < 60) {
        return 'hace ' . $minutos . ' minuto' . ($minutos != 1 ? 's' : '');
    } elseif ($minutos < 1440) { // 60 * 24
        $horas = round($minutos / 60);
        return 'hace ' . $horas . ' hora' . ($horas != 1 ? 's' : '');
    } elseif ($minutos < 10080) { // 60 * 24 * 7
        $dias = round($minutos / 1440);
        return 'hace ' . $dias . ' día' . ($dias != 1 ? 's' : '');
    } elseif ($minutos < 43200) { // 60 * 24 * 30
        $semanas = round($minutos / 10080);
        return 'hace ' . $semanas . ' semana' . ($semanas != 1 ? 's' : '');
    } elseif ($minutos < 525600) { // 60 * 24 * 365
        $meses = round($minutos / 43200);
        return 'hace ' . $meses . ' mes' . ($meses != 1 ? 'es' : '');
    } else {
        $años = round($minutos / 525600);
        return 'hace ' . $años . ' año' . ($años != 1 ? 's' : '');
    }
}

/**
 * Trunca un texto a una longitud determinada
 * 
 * @param string $texto Texto a truncar
 * @param int $longitud Longitud máxima
 * @return string Texto truncado
 */
function truncarTexto($texto, $longitud = 100) {
    if (strlen($texto) <= $longitud) {
        return $texto;
    }
    
    return substr($texto, 0, $longitud) . '...';
}

/**
 * Convierte un texto en formato markdown a HTML
 * 
 * @param string $text Texto en formato markdown
 * @return string HTML generado
 */
function markdownToHTML($text) {
    // Convertir negrita **texto** a <strong>texto</strong>
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    
    // Convertir cursiva *texto* a <em>texto</em>
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
    
    // Convertir subrayado _texto_ a <u>texto</u>
    $text = preg_replace('/_([^_]+)_/', '<u>$1</u>', $text);
    
    // Convertir listas con viñetas
    $text = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/((?:<li>.*<\/li>\n?)+)/', '<ul>$1</ul>', $text);
    
    // Convertir listas numeradas
    $text = preg_replace('/^\d+\. (.*?)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/((?:<li>.*<\/li>\n?)+)/', '<ol>$1</ol>', $text);
    
    // Convertir saltos de línea en <br>
    $text = nl2br($text);
    
    return $text;
}
/**
 * Valida la contraseña (versión alternativa)
 *
 * Requisitos:
 * - Al menos 8 caracteres
 * - Al menos una letra minúscula
 * - Al menos una letra mayúscula
 * - Al menos un número
 * - Al menos un carácter especial
 * 
 * @param string $contrasena Contraseña a validar
 * @return array Array con resultado y mensaje
 */
if (!function_exists('validarContrasena')) {
    function validarContrasena($contrasena) {
        $resultado = ['valido' => true, 'mensaje' => ''];
        
        if (strlen($contrasena) < 8) {
            $resultado['valido'] = false;
            $resultado['mensaje'] = 'La contraseña debe tener al menos 8 caracteres.';
        } 
        elseif (!preg_match('/[a-z]/', $contrasena)) {
            $resultado['valido'] = false;
            $resultado['mensaje'] = 'La contraseña debe incluir al menos una letra minúscula.';
        }
        elseif (!preg_match('/[A-Z]/', $contrasena)) {
            $resultado['valido'] = false;
            $resultado['mensaje'] = 'La contraseña debe incluir al menos una letra mayúscula.';
        }
        elseif (!preg_match('/[0-9]/', $contrasena)) {
            $resultado['valido'] = false;
            $resultado['mensaje'] = 'La contraseña debe incluir al menos un número.';
        }
        elseif (!preg_match('/[^a-zA-Z0-9]/', $contrasena)) {
            $resultado['valido'] = false;
            $resultado['mensaje'] = 'La contraseña debe incluir al menos un carácter especial.';
        }
        
        return $resultado;
    }
}

/**
 * Valida un nombre de usuario
 * Requisitos:
 * - Entre 3 y 30 caracteres
 * - Solo letras, números, guiones y guiones bajos
 * 
 * @param string $nombre Nombre a validar
 * @return array Array con resultado y mensaje
 */
if (!function_exists('validarNombreUsuario')) {
    function validarNombreUsuario($nombre) {
        $resultado = ['valido' => true, 'mensaje' => ''];
        
        if (strlen($nombre) < 3 || strlen($nombre) > 30) {
            $resultado['valido'] = false;
            $resultado['mensaje'] = 'El nombre debe tener entre 3 y 30 caracteres.';
        }
        elseif (!preg_match('/^[a-zA-Z0-9_\- áéíóúÁÉÍÓÚñÑ]+$/', $nombre)) {
            $resultado['valido'] = false;
            $resultado['mensaje'] = 'El nombre solo puede contener letras, números, espacios, guiones y guiones bajos.';
        }
        
        return $resultado;
    }
}

/**
 * Genera una cadena aleatoria para tokens
 * 
 * @param int $length Longitud de la cadena
 * @return string Cadena aleatoria
 */
function generarToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
?>
