<?php
// Script para verificar la contraseña del administrador

// Ruta a la base de datos
$db_path = __DIR__ . '/db/ticketing.db';

try {
    // Crear conexión a la base de datos SQLite
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener el usuario administrador
    $stmt = $db->prepare('SELECT id_usuario, nombre, correo, rol, contraseña FROM usuarios WHERE correo = ?');
    $stmt->execute(['admin@sistema.com']);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "Usuario encontrado:\n";
        echo "- ID: " . $usuario['id_usuario'] . "\n";
        echo "- Nombre: " . $usuario['nombre'] . "\n";
        echo "- Correo: " . $usuario['correo'] . "\n";
        echo "- Rol: " . $usuario['rol'] . "\n";
        echo "- Hash de contraseña: " . $usuario['contraseña'] . "\n\n";
        
        // Verificar si la contraseña 'Admin123' coincide con el hash almacenado
        $contrasena = 'Admin123';
        $verificacion = password_verify($contrasena, $usuario['contraseña']);
        
        echo "Verificación de contraseña 'Admin123': " . ($verificacion ? "CORRECTA" : "INCORRECTA") . "\n";
        
        // Generar un nuevo hash para ver cómo se vería
        $nuevo_hash = password_hash('Admin123', PASSWORD_DEFAULT);
        echo "Nuevo hash generado para 'Admin123': " . $nuevo_hash . "\n";
    } else {
        echo "No se encontró el usuario administrador.\n";
    }
} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
}
?>
