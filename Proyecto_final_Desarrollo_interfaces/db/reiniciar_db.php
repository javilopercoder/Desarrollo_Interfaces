<?php
/**
 * Script para resetear la estructura problemática de la base de datos
 */

// Incluir archivo de conexión
require_once '../includes/conexion.php';

try {
    $db = getDB();
    
    // Mostrar cabecera y mensaje de advertencia
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reiniciar estructura de base de datos</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            .warning {
                background-color: #ffeeee;
                border-left: 4px solid #ff3333;
                padding: 15px;
                margin-bottom: 20px;
            }
            .success {
                background-color: #e6ffe6;
                border-left: 4px solid #00cc66;
                padding: 15px;
                margin-bottom: 20px;
            }
            .info {
                background-color: #e6f2ff;
                border-left: 4px solid #0099ff;
                padding: 15px;
                margin-bottom: 20px;
            }
            .btn {
                display: inline-block;
                padding: 10px 15px;
                background-color: #0099ff;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-right: 10px;
                border: none;
                cursor: pointer;
            }
            .btn-danger {
                background-color: #ff3333;
            }
            .btn-success {
                background-color: #00cc66;
            }
            ul {
                list-style-type: none;
                padding-left: 0;
            }
            li {
                margin-bottom: 10px;
                padding-left: 25px;
                position: relative;
            }
            li::before {
                position: absolute;
                left: 0;
                font-weight: bold;
            }
            .success-item::before {
                content: "✅";
            }
            .error-item::before {
                content: "❌";
            }
        </style>
    </head>
    <body>
    <?php
    
    // Procesar la solicitud si se ha enviado el formulario
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
        
        echo "<h2>Iniciando restablecimiento de la base de datos...</h2>";
        
        // Lista para almacenar resultados
        $resultados = [];
        
        try {
            // Eliminar tablas si existen
            $db->exec("DROP TABLE IF EXISTS articulos_relacionados");
            $resultados[] = ['tipo' => 'success', 'mensaje' => 'Tabla <strong>articulos_relacionados</strong> eliminada o no existía.'];
        } catch (PDOException $e) {
            $resultados[] = ['tipo' => 'error', 'mensaje' => 'Error al eliminar tabla <strong>articulos_relacionados</strong>: ' . $e->getMessage()];
        }
        
        try {
            $db->exec("DROP TABLE IF EXISTS valoraciones");
            $resultados[] = ['tipo' => 'success', 'mensaje' => 'Tabla <strong>valoraciones</strong> eliminada o no existía.'];
        } catch (PDOException $e) {
            $resultados[] = ['tipo' => 'error', 'mensaje' => 'Error al eliminar tabla <strong>valoraciones</strong>: ' . $e->getMessage()];
        }
        
        // Crear tabla temporal para conocimientos
        try {
            $db->beginTransaction();
            
            // 1. Obtener la estructura actual de la tabla
            $stmt = $db->query("PRAGMA table_info(conocimientos)");
            $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 2. Crear tabla temporal con la estructura original sin columnas problemáticas
            $columnas_sql = [];
            foreach ($columnas as $col) {
                // Excluir columnas que vamos a eliminar
                if (!in_array($col['name'], ['visitas', 'etiquetas', 'resumen', 'imagen', 'fecha_actualizacion'])) {
                    $columnas_sql[] = $col['name'] . ' ' . $col['type'] . 
                                     ($col['notnull'] ? ' NOT NULL' : '') . 
                                     ($col['pk'] ? ' PRIMARY KEY' : '') . 
                                     ($col['dflt_value'] ? ' DEFAULT ' . $col['dflt_value'] : '');
                }
            }
            
            // 3. Crear tabla temporal
            $create_temp_sql = "CREATE TABLE conocimientos_temp (" . implode(', ', $columnas_sql) . ")";
            $db->exec($create_temp_sql);
            
            // 4. Copiar datos
            $db->exec("INSERT INTO conocimientos_temp SELECT id_conocimiento, categoria, titulo, contenido, fecha_creacion, id_autor FROM conocimientos");
            
            // 5. Eliminar tabla original
            $db->exec("DROP TABLE conocimientos");
            
            // 6. Renombrar tabla temporal
            $db->exec("ALTER TABLE conocimientos_temp RENAME TO conocimientos");
            
            $db->commit();
            
            $resultados[] = ['tipo' => 'success', 'mensaje' => 'Tabla <strong>conocimientos</strong> reconstruida correctamente sin las columnas extra.'];
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $resultados[] = ['tipo' => 'error', 'mensaje' => 'Error al reconstruir tabla <strong>conocimientos</strong>: ' . $e->getMessage()];
        }
        
        // Mostrar resultados
        echo '<div class="info">';
        echo '<h3>Resultados:</h3>';
        echo '<ul>';
        foreach ($resultados as $resultado) {
            $clase = $resultado['tipo'] === 'success' ? 'success-item' : 'error-item';
            echo '<li class="' . $clase . '">' . $resultado['mensaje'] . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        echo '<div class="success">';
        echo '<p>Proceso completado. Ahora puedes intentar <a href="actualizar_db.php">actualizar la base de datos</a> nuevamente.</p>';
        echo '</div>';
        
        echo '<div class="btn-group">';
        echo '<a href="actualizar_db.php" class="btn btn-success">Actualizar base de datos</a>';
        echo '<a href="../index.php" class="btn">Volver al inicio</a>';
        echo '</div>';
    } else {
        // Mostrar formulario de confirmación
        ?>
        <h1>Reiniciar estructura de la base de datos</h1>
        
        <div class="warning">
            <h3>⚠️ ADVERTENCIA</h3>
            <p>Esta operación eliminará las siguientes tablas y columnas:</p>
            <ul>
                <li>Tabla: <strong>valoraciones</strong></li>
                <li>Tabla: <strong>articulos_relacionados</strong></li>
                <li>Columnas de <strong>conocimientos</strong>: visitas, etiquetas, resumen, imagen, fecha_actualizacion</li>
            </ul>
            <p>Los datos almacenados en estas tablas y columnas se perderán permanentemente.</p>
            <p><strong>Esta operación no se puede deshacer.</strong></p>
        </div>
        
        <div class="info">
            <p>Utiliza esta herramienta solo si estás experimentando problemas al actualizar la estructura de la base de datos.</p>
            <p>Después de reiniciar la estructura, deberás ejecutar nuevamente el script de actualización.</p>
        </div>
        
        <form method="POST" onsubmit="return confirm('¿Estás seguro de que quieres continuar? Esta acción no se puede deshacer.')">
            <input type="hidden" name="confirmar" value="si">
            <div class="btn-group">
                <button type="submit" class="btn btn-danger">Reiniciar estructura de la base de datos</button>
                <a href="../index.php" class="btn">Cancelar</a>
            </div>
        </form>
        <?php
    }
    ?>
    </body>
    </html>
    <?php
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage();
}
?>
