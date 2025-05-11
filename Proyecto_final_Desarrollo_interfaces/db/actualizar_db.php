<?php
/**
 * Script para actualizar la estructura de la base de datos
 */

// Incluir archivo de conexión
require_once '../includes/conexion.php';

try {
    $db = getDB();
    
    // Verificar si las tablas necesarias existen

    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='conocimientos'");
    $tabla_conocimientos_existe = ($stmt->fetch() !== false);
    
    if (!$tabla_conocimientos_existe) {
        echo "<div style='color:red; margin: 20px 0; padding: 15px; background-color: #ffeeee; border-left: 4px solid red;'>";
        echo "<strong>Error:</strong> La tabla 'conocimientos' no existe en la base de datos. ";
        echo "Esto indica que la base de datos no está correctamente inicializada.";
        echo "</div>";
        echo "<a href='setup_db.php' class='btn' style='display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #0099ff; color: white; text-decoration: none; border-radius: 4px;'>Inicializar base de datos</a>";
        exit;
    }
    
    // Verificar si la columna 'visitas' existe en la tabla 'conocimientos'
    $stmt = $db->query("PRAGMA table_info(conocimientos)");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $visitas_existe = false;
    $etiquetas_existe = false;
    $resumen_existe = false;
    $imagen_existe = false;
    $fecha_actualizacion_existe = false;
    
    foreach ($columnas as $col) {
        if ($col['name'] === 'visitas') {
            $visitas_existe = true;
        }
        if ($col['name'] === 'etiquetas') {
            $etiquetas_existe = true;
        }
        if ($col['name'] === 'resumen') {
            $resumen_existe = true;
        }
        if ($col['name'] === 'imagen') {
            $imagen_existe = true;
        }
        if ($col['name'] === 'fecha_actualizacion') {
            $fecha_actualizacion_existe = true;
        }
    }
    
    // Verificar si las tablas valoraciones y articulos_relacionados existen
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='valoraciones'");
    $tabla_valoraciones_existe = ($stmt->fetch() !== false);
    
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='articulos_relacionados'");
    $tabla_articulos_relacionados_existe = ($stmt->fetch() !== false);
    
    echo "<div style='margin: 20px 0; padding: 15px; background-color: #f5f5f5; border-left: 4px solid #0099ff;'>";
    echo "<h3 style='margin-top:0'>Diagnóstico de base de datos:</h3>";
    echo "<ul>";
    echo "<li>Columna 'visitas': " . ($visitas_existe ? "✅ Existe" : "❌ No existe") . "</li>";
    echo "<li>Columna 'etiquetas': " . ($etiquetas_existe ? "✅ Existe" : "❌ No existe") . "</li>";
    echo "<li>Columna 'resumen': " . ($resumen_existe ? "✅ Existe" : "❌ No existe") . "</li>";
    echo "<li>Columna 'imagen': " . ($imagen_existe ? "✅ Existe" : "❌ No existe") . "</li>";
    echo "<li>Columna 'fecha_actualizacion': " . ($fecha_actualizacion_existe ? "✅ Existe" : "❌ No existe") . "</li>";
    echo "<li>Tabla 'valoraciones': " . ($tabla_valoraciones_existe ? "✅ Existe" : "❌ No existe") . "</li>";
    echo "<li>Tabla 'articulos_relacionados': " . ($tabla_articulos_relacionados_existe ? "✅ Existe" : "❌ No existe") . "</li>";
    echo "</ul>";
    echo "</div>";
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Agregar columna 'visitas' si no existe
    if (!$visitas_existe) {
        $db->exec("ALTER TABLE conocimientos ADD COLUMN visitas INTEGER DEFAULT 0");
        echo "- Columna 'visitas' añadida correctamente.<br>";
    } else {
        echo "- Columna 'visitas' ya existe.<br>";
    }
    
    // Agregar columna 'etiquetas' si no existe
    if (!$etiquetas_existe) {
        $db->exec("ALTER TABLE conocimientos ADD COLUMN etiquetas TEXT");
        echo "- Columna 'etiquetas' añadida correctamente.<br>";
    } else {
        echo "- Columna 'etiquetas' ya existe.<br>";
    }
    
    // Agregar columna 'resumen' si no existe
    if (!$resumen_existe) {
        $db->exec("ALTER TABLE conocimientos ADD COLUMN resumen TEXT");
        echo "- Columna 'resumen' añadida correctamente.<br>";
        
        // Actualizar resúmenes existentes
        $db->exec("UPDATE conocimientos SET resumen = substr(contenido, 1, 200) || '...' WHERE resumen IS NULL");
        echo "- Actualizados resúmenes automáticos para artículos existentes.<br>";
    } else {
        echo "- Columna 'resumen' ya existe.<br>";
    }
    
    // Agregar columna 'imagen' si no existe
    if (!$imagen_existe) {
        $db->exec("ALTER TABLE conocimientos ADD COLUMN imagen VARCHAR(255)");
        echo "- Columna 'imagen' añadida correctamente.<br>";
    } else {
        echo "- Columna 'imagen' ya existe.<br>";
    }
    
    // Agregar columna 'fecha_actualizacion' si no existe
    if (!$fecha_actualizacion_existe) {
        // En SQLite no podemos usar DEFAULT CURRENT_TIMESTAMP en ALTER TABLE
        $db->exec("ALTER TABLE conocimientos ADD COLUMN fecha_actualizacion DATETIME");
        echo "- Columna 'fecha_actualizacion' añadida correctamente.<br>";
        
        // Establecer la fecha actual para todas las filas existentes
        $fecha_actual = date('Y-m-d H:i:s');
        $db->exec("UPDATE conocimientos SET fecha_actualizacion = '$fecha_actual'");
        
        // Alternativamente, copiar fechas de creación a fechas de actualización
        $db->exec("UPDATE conocimientos SET fecha_actualizacion = fecha_creacion WHERE fecha_actualizacion IS NULL");
        echo "- Actualizadas fechas de actualización para artículos existentes.<br>";
    } else {
        echo "- Columna 'fecha_actualizacion' ya existe.<br>";
    }
    
    // Las tablas y los índices se crearán en la sección siguiente con manejo de errores individual
    
    // Para cada tabla nueva, vamos a usar try/catch independiente para que si falla una, siga con las otras
    try {
        // Crear tabla 'valoraciones' si no existe
        $db->exec("
            CREATE TABLE IF NOT EXISTS valoraciones (
                id_valoracion INTEGER PRIMARY KEY AUTOINCREMENT,
                id_conocimiento INTEGER NOT NULL,
                id_usuario INTEGER NOT NULL,
                valoracion INTEGER NOT NULL CHECK (valoracion IN (1, 2, 3, 4, 5)),
                comentario TEXT,
                fecha_valoracion DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_conocimiento) REFERENCES conocimientos(id_conocimiento),
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
                UNIQUE(id_conocimiento, id_usuario)
            )
        ");
        echo "- Tabla 'valoraciones' creada si no existía.<br>";
    } catch (PDOException $e) {
        echo "- <span style='color:orange'>Advertencia al crear tabla 'valoraciones': " . $e->getMessage() . "</span><br>";
    }
    
    try {
        // Crear tabla 'articulos_relacionados' si no existe
        $db->exec("
            CREATE TABLE IF NOT EXISTS articulos_relacionados (
                id_relacion INTEGER PRIMARY KEY AUTOINCREMENT,
                id_articulo INTEGER NOT NULL,
                id_articulo_relacionado INTEGER NOT NULL,
                FOREIGN KEY (id_articulo) REFERENCES conocimientos(id_conocimiento),
                FOREIGN KEY (id_articulo_relacionado) REFERENCES conocimientos(id_conocimiento),
                UNIQUE(id_articulo, id_articulo_relacionado)
            )
        ");
        echo "- Tabla 'articulos_relacionados' creada si no existía.<br>";
    } catch (PDOException $e) {
        echo "- <span style='color:orange'>Advertencia al crear tabla 'articulos_relacionados': " . $e->getMessage() . "</span><br>";
    }
    
    try {
        // Crear índices necesarios
        $db->exec("CREATE INDEX IF NOT EXISTS idx_conocimientos_categoria ON conocimientos(categoria)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_conocimientos_etiquetas ON conocimientos(etiquetas)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_valoraciones_conocimiento ON valoraciones(id_conocimiento)");
        echo "- Índices creados correctamente.<br>";
    } catch (PDOException $e) {
        echo "- <span style='color:orange'>Advertencia al crear índices: " . $e->getMessage() . "</span><br>";
    }
    
    // Confirmar cambios
    $db->commit();
    
    echo "<br><strong style='color:green'>¡Base de datos actualizada correctamente!</strong>";
    echo "<br><a href='../conocimientos.php' class='btn' style='display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #0099ff; color: white; text-decoration: none; border-radius: 4px;'>Volver a la base de conocimientos</a>";
    
} catch (PDOException $e) {
    // Revertir cambios en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "<strong style='color:red'>Error al actualizar la base de datos:</strong> " . $e->getMessage();
    echo "<br><a href='../index.php' class='btn' style='display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #0099ff; color: white; text-decoration: none; border-radius: 4px;'>Volver al inicio</a>";
}
?>
