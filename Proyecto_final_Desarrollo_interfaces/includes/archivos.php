<?php
/**
 * Funciones para gestionar la subida de archivos
 */

// Tipos de archivos permitidos
$allowed_file_types = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/zip',
    'text/plain'
];

// Tamaño máximo de archivo (5MB)
$max_file_size = 5 * 1024 * 1024;

/**
 * Sube un archivo y registra en la base de datos
 *
 * @param array $file Archivo a subir ($_FILES['file'])
 * @param int $id_ticket ID del ticket relacionado
 * @return array Resultado de la operación
 */
function subirArchivo($file, $id_ticket) {
    global $allowed_file_types, $max_file_size;
    
    $resultado = [
        'success' => false,
        'error' => '',
        'file_id' => null,
        'file_path' => ''
    ];
    
    // Verificar que el archivo se subió correctamente
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $resultado['error'] = obtenerErrorSubida($file['error'] ?? -1);
        return $resultado;
    }
    
    // Verificar tamaño del archivo
    if ($file['size'] > $max_file_size) {
        $resultado['error'] = 'El archivo excede el tamaño máximo permitido (5MB).';
        return $resultado;
    }
    
    // Verificar tipo de archivo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_file_types)) {
        $resultado['error'] = 'Tipo de archivo no permitido.';
        return $resultado;
    }
    
    // Crear directorio si no existe
    $upload_dir = __DIR__ . '/../uploads/ticket_' . $id_ticket;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generar nombre de archivo seguro
    $filename = sanitizarNombreArchivo($file['name']);
    $unique_filename = time() . '_' . $filename;
    $file_path = $upload_dir . '/' . $unique_filename;
    
    // Subir archivo
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        $resultado['error'] = 'Error al subir el archivo.';
        return $resultado;
    }
    
    // Registrar en base de datos
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO archivos (id_ticket, nombre_archivo, ruta_archivo, tipo_archivo) VALUES (?, ?, ?, ?)');
        $ruta_relativa = 'uploads/ticket_' . $id_ticket . '/' . $unique_filename;
        $stmt->execute([$id_ticket, $filename, $ruta_relativa, $file_type]);
        
        $resultado['success'] = true;
        $resultado['file_id'] = $db->lastInsertId();
        $resultado['file_path'] = $ruta_relativa;
    } catch (PDOException $e) {
        // Si hay error en BD, eliminar el archivo
        @unlink($file_path);
        $resultado['error'] = 'Error al registrar el archivo: ' . $e->getMessage();
    }
    
    return $resultado;
}

/**
 * Obtiene los archivos asociados a un ticket
 *
 * @param int $id_ticket ID del ticket
 * @return array Lista de archivos
 */
function obtenerArchivosTicket($id_ticket) {
    $archivos = [];
    
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM archivos WHERE id_ticket = ? ORDER BY fecha_subida DESC');
        $stmt->execute([$id_ticket]);
        $archivos = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Retornar array vacío en caso de error
    }
    
    return $archivos;
}

/**
 * Elimina un archivo
 *
 * @param int $id_archivo ID del archivo
 * @return bool True si se eliminó correctamente
 */
function eliminarArchivo($id_archivo) {
    try {
        $db = getDB();
        
        // Obtener información del archivo
        $stmt = $db->prepare('SELECT ruta_archivo FROM archivos WHERE id_archivo = ?');
        $stmt->execute([$id_archivo]);
        $archivo = $stmt->fetch();
        
        if (!$archivo) {
            return false;
        }
        
        // Eliminar archivo físico
        $file_path = __DIR__ . '/../' . $archivo['ruta_archivo'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        
        // Eliminar de la base de datos
        $stmt = $db->prepare('DELETE FROM archivos WHERE id_archivo = ?');
        $stmt->execute([$id_archivo]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Sanitiza un nombre de archivo
 *
 * @param string $filename Nombre de archivo a sanitizar
 * @return string Nombre sanitizado
 */
function sanitizarNombreArchivo($filename) {
    // Eliminar caracteres no seguros
    $filename = preg_replace('/[^\p{L}\p{N}_.-]/u', '_', $filename);
    
    // Evitar nombres de archivo peligrosos
    $filename = str_replace(['..', './', '/.'], '', $filename);
    
    // Limitar longitud
    if (strlen($filename) > 100) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = substr($name, 0, 90);
        $filename = $name . '.' . $ext;
    }
    
    return $filename;
}

/**
 * Obtiene mensaje de error de subida
 *
 * @param int $error_code Código de error
 * @return string Mensaje de error
 */
function obtenerErrorSubida($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el servidor.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el formulario.';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo se subió parcialmente.';
        case UPLOAD_ERR_NO_FILE:
            return 'No se subió ningún archivo.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir el archivo en el disco.';
        case UPLOAD_ERR_EXTENSION:
            return 'Subida detenida por extensión.';
        default:
            return 'Error desconocido al subir el archivo.';
    }
}
?>
