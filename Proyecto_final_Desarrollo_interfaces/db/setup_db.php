<?php
// Configuración de la base de datos
$db_path = __DIR__ . '/ticketing.db';
$schema_path = __DIR__ . '/schema.sql';

// Comprobar si el archivo de la base de datos ya existe
$db_exists = file_exists($db_path);

try {
    // Crear conexión a la base de datos SQLite
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Si la base de datos no existe, inicializarla con el esquema
    if (!$db_exists) {
        // Leer el archivo de esquema
        $schema = file_get_contents($schema_path);
        
        // Ejecutar el esquema
        $db->exec($schema);
        
        echo "Base de datos inicializada correctamente.<br>";
        
        // Insertar usuario administrador por defecto
        $stmt = $db->prepare('INSERT INTO usuarios (nombre, correo, rol, contraseña) VALUES (?, ?, ?, ?)');
        
        // Contraseña: Admin123 (hasheada con algoritmo bcrypt)
        $admin_password = password_hash('Admin123', PASSWORD_BCRYPT);
        $stmt->execute(['Administrador', 'admin@sistema.com', 'administrador', $admin_password]);
        
        echo "Usuario administrador creado:<br>";
        echo "- Email: admin@sistema.com<br>";
        echo "- Contraseña: Admin123<br>";
        
        // Insertar algunas categorías de conocimiento para iniciar
        $categorias = [
            ['Red', 'Acceso a red WiFi y soluciones a errores de red'],
            ['Inventario', 'Gestión de equipos'],
            ['Software', 'Soluciones fáciles a los diferentes problemas de software que puedan ocurrir']
        ];
        
        $stmt = $db->prepare('INSERT INTO conocimientos (categoria, titulo, contenido, id_autor) VALUES (?, ?, ?, 1)');
        
        foreach ($categorias as $categoria) {
            $stmt->execute([$categoria[0], $categoria[0], $categoria[1]]);
            echo "Categoría de conocimiento añadida: " . $categoria[0] . "<br>";
        }
        
        // Insertar algunos artículos de conocimiento iniciales
        $articulos = [
            ['Red', 'Configuración WiFi', 'Guía para configurar la conexión WiFi en diferentes dispositivos.', 1],
            ['Equipo Digitech FP', 'Configuración WiFi', 'Pasos específicos para configurar WiFi en equipos Digitech FP.', 1],
            ['Inventario', 'Proceso Inventario Equipos', 'Procedimiento para registrar nuevos equipos en el inventario.', 1],
            ['Gestor Inventario', 'Proceso Inventario Equipos', 'Cómo utilizar el gestor de inventario para registrar equipos.', 1],
            ['Software', 'Soluciones frecuentes', 'Problemas comunes de software y sus soluciones.', 1],
            ['Android Studio', 'Resolución Android Studio 2023', 'Problemas y soluciones al usar Android Studio 2023.', 1]
        ];
        
        $stmt = $db->prepare('INSERT INTO conocimientos (categoria, titulo, contenido, id_autor) VALUES (?, ?, ?, ?)');
        
        foreach ($articulos as $articulo) {
            $stmt->execute($articulo);
        }
        
        echo "Artículos de conocimiento iniciales añadidos.<br>";
    } else {
        echo "La base de datos ya existe. No se realizó ninguna inicialización.<br>";
    }
    
    echo "Conexión a la base de datos establecida correctamente.<br>";
    
} catch (PDOException $e) {
    die("Error al conectar o inicializar la base de datos: " . $e->getMessage());
}
