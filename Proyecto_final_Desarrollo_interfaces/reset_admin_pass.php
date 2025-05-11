<?php
// Script para actualizar la contraseña del administrador a una versión más simple

// Ruta a la base de datos
$db_path = __DIR__ . '/db/ticketing.db';

try {
    // Crear conexión a la base de datos SQLite
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Establecer una contraseña simple para el administrador
    $nueva_contraseña = password_hash('123456', PASSWORD_DEFAULT);
    
    // Actualizar la contraseña del administrador
    $stmt = $db->prepare('UPDATE usuarios SET contraseña = ? WHERE correo = ?');
    $resultado = $stmt->execute([$nueva_contraseña, 'admin@sistema.com']);
    
    if ($resultado) {
        echo "La contraseña del administrador se ha actualizado a '123456'.\n";
    } else {
        echo "No se pudo actualizar la contraseña.\n";
    }
} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
}
?>
