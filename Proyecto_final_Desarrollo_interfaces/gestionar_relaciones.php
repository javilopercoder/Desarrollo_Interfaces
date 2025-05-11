<?php
/**
 * Página para gestionar relaciones entre artículos de conocimiento
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Verificar si el usuario tiene permisos (administrador o soporte)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte')) {
    $_SESSION['mensaje'] = 'No tienes permisos para acceder a esta página.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: conocimientos.php');
    exit;
}

// Inicializar variables
$articulo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$articulos_relacionados = [];
$todos_articulos = [];
$articulo = null;

// Verificar si se proporcionó un ID válido
if ($articulo_id <= 0) {
    $_SESSION['mensaje'] = 'ID de artículo no válido.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: conocimientos.php');
    exit;
}

try {
    $db = getDB();
    
    // Obtener información del artículo principal
    $stmt = $db->prepare('SELECT * FROM conocimientos WHERE id_conocimiento = ?');
    $stmt->execute([$articulo_id]);
    $articulo = $stmt->fetch();
    
    if (!$articulo) {
        $_SESSION['mensaje'] = 'Artículo no encontrado.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: conocimientos.php');
        exit;
    }
    
    // Obtener artículos relacionados actuales
    $stmt = $db->prepare('
        SELECT c.* 
        FROM conocimientos c
        JOIN articulos_relacionados ar ON c.id_conocimiento = ar.id_articulo_relacionado
        WHERE ar.id_articulo = ?
    ');
    $stmt->execute([$articulo_id]);
    $articulos_relacionados = $stmt->fetchAll();
    
    // Obtener todos los artículos excepto el actual para el selector
    $stmt = $db->prepare('
        SELECT * 
        FROM conocimientos 
        WHERE id_conocimiento != ? 
        ORDER BY categoria, titulo
    ');
    $stmt->execute([$articulo_id]);
    $todos_articulos = $stmt->fetchAll();
    
    // Procesar el formulario cuando se envía
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Si se está agregando una nueva relación
        if (isset($_POST['agregar_relacion']) && isset($_POST['articulo_relacionado'])) {
            $id_relacionado = (int)$_POST['articulo_relacionado'];
            
            // Verificar que el artículo relacionado existe
            $stmt = $db->prepare('SELECT COUNT(*) FROM conocimientos WHERE id_conocimiento = ?');
            $stmt->execute([$id_relacionado]);
            $existe = $stmt->fetchColumn();
            
            if ($existe && $id_relacionado != $articulo_id) {
                try {
                    // Insertar relación si no existe
                    $stmt = $db->prepare('
                        INSERT INTO articulos_relacionados (id_articulo, id_articulo_relacionado)
                        VALUES (?, ?)
                        ON CONFLICT (id_articulo, id_articulo_relacionado) DO NOTHING
                    ');
                    
                    // Si SQLite no soporta ON CONFLICT, usar esta alternativa
                    try {
                        $stmt->execute([$articulo_id, $id_relacionado]);
                    } catch (PDOException $e) {
                        // Si hay error por duplicado, ignorarlo
                        if (!strpos($e->getMessage(), 'UNIQUE constraint failed')) {
                            throw $e;
                        }
                    }
                    
                    // También crear la relación inversa (si se desea que sea bidireccional)
                    try {
                        $stmt->execute([$id_relacionado, $articulo_id]);
                    } catch (PDOException $e) {
                        // Si hay error por duplicado, ignorarlo
                        if (!strpos($e->getMessage(), 'UNIQUE constraint failed')) {
                            throw $e;
                        }
                    }
                    
                    $_SESSION['mensaje'] = 'Relación agregada correctamente.';
                    $_SESSION['mensaje_tipo'] = 'success';
                    
                    // Recargar la página para mostrar los cambios
                    header('Location: gestionar_relaciones.php?id=' . $articulo_id);
                    exit;
                    
                } catch (PDOException $e) {
                    $_SESSION['mensaje'] = 'Error al agregar relación: ' . $e->getMessage();
                    $_SESSION['mensaje_tipo'] = 'error';
                }
            } else {
                $_SESSION['mensaje'] = 'El artículo relacionado no es válido.';
                $_SESSION['mensaje_tipo'] = 'error';
            }
        }
        
        // Si se está eliminando una relación
        if (isset($_POST['eliminar_relacion']) && isset($_POST['id_eliminar'])) {
            $id_eliminar = (int)$_POST['id_eliminar'];
            
            try {
                $stmt = $db->prepare('
                    DELETE FROM articulos_relacionados 
                    WHERE id_articulo = ? AND id_articulo_relacionado = ?
                ');
                $stmt->execute([$articulo_id, $id_eliminar]);
                
                // También eliminar la relación inversa
                $stmt->execute([$id_eliminar, $articulo_id]);
                
                $_SESSION['mensaje'] = 'Relación eliminada correctamente.';
                $_SESSION['mensaje_tipo'] = 'success';
                
                // Recargar la página para mostrar los cambios
                header('Location: gestionar_relaciones.php?id=' . $articulo_id);
                exit;
                
            } catch (PDOException $e) {
                $_SESSION['mensaje'] = 'Error al eliminar relación: ' . $e->getMessage();
                $_SESSION['mensaje_tipo'] = 'error';
            }
        }
    }
    
    // Actualizar lista de artículos relacionados después de cambios
    $stmt = $db->prepare('
        SELECT c.* 
        FROM conocimientos c
        JOIN articulos_relacionados ar ON c.id_conocimiento = ar.id_articulo_relacionado
        WHERE ar.id_articulo = ?
    ');
    $stmt->execute([$articulo_id]);
    $articulos_relacionados = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar datos: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
}

// Establecer título de página
$page_title = 'Gestionar artículos relacionados';

// Incluir header
include 'includes/header.php';
?>

<section class="manage-relations">
    <div class="breadcrumbs">
        <a href="index.php">Inicio</a> &raquo;
        <a href="conocimientos.php">Base de conocimientos</a> &raquo;
        <a href="conocimientos.php?categoria=<?php echo urlencode($articulo['categoria']); ?>"><?php echo htmlspecialchars($articulo['categoria']); ?></a> &raquo;
        <a href="ver_articulo.php?id=<?php echo $articulo_id; ?>"><?php echo htmlspecialchars($articulo['titulo']); ?></a> &raquo;
        <span>Gestionar relaciones</span>
    </div>
    
    <h1 class="form-title">Gestionar artículos relacionados</h1>
    
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?>">
            <?php 
                echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']);
                unset($_SESSION['mensaje_tipo']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="article-info-box">
        <h3>Artículo principal:</h3>
        <p><strong><?php echo htmlspecialchars($articulo['titulo']); ?></strong></p>
        <p>Categoría: <?php echo htmlspecialchars($articulo['categoria']); ?></p>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Agregar artículo relacionado</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="gestionar_relaciones.php?id=<?php echo $articulo_id; ?>">
                        <div class="form-group">
                            <label for="articulo_relacionado">Seleccionar artículo:</label>
                            <select id="articulo_relacionado" name="articulo_relacionado" class="form-control" required>
                                <option value="">Seleccione un artículo...</option>
                                <?php
                                $categorias = [];
                                foreach ($todos_articulos as $art) {
                                    if (!isset($categorias[$art['categoria']])) {
                                        $categorias[$art['categoria']] = [];
                                    }
                                    $categorias[$art['categoria']][] = $art;
                                }
                                
                                foreach ($categorias as $cat => $arts) {
                                    echo '<optgroup label="' . htmlspecialchars($cat) . '">';
                                    foreach ($arts as $art) {
                                        // Verificar si ya está relacionado para no mostrarlo de nuevo
                                        $ya_relacionado = false;
                                        foreach ($articulos_relacionados as $rel) {
                                            if ($rel['id_conocimiento'] == $art['id_conocimiento']) {
                                                $ya_relacionado = true;
                                                break;
                                            }
                                        }
                                        
                                        if (!$ya_relacionado) {
                                            echo '<option value="' . $art['id_conocimiento'] . '">';
                                            echo htmlspecialchars($art['titulo']);
                                            echo '</option>';
                                        }
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" name="agregar_relacion" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar relación
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Artículos relacionados actuales</h3>
                </div>
                <div class="card-body">
                    <?php if (count($articulos_relacionados) > 0): ?>
                        <ul class="related-articles-list">
                            <?php foreach($articulos_relacionados as $rel): ?>
                                <li>
                                    <div class="related-article-item">
                                        <div class="related-article-info">
                                            <strong><?php echo htmlspecialchars($rel['titulo']); ?></strong>
                                            <span class="category"><?php echo htmlspecialchars($rel['categoria']); ?></span>
                                        </div>
                                        <form method="POST" action="gestionar_relaciones.php?id=<?php echo $articulo_id; ?>" class="delete-form">
                                            <input type="hidden" name="id_eliminar" value="<?php echo $rel['id_conocimiento']; ?>">
                                            <button type="submit" name="eliminar_relacion" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No hay artículos relacionados. Agrega algunos usando el formulario.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-actions mt-4">
        <a href="ver_articulo.php?id=<?php echo $articulo_id; ?>" class="btn">
            <i class="fas fa-arrow-left"></i> Volver al artículo
        </a>
    </div>
</section>

<?php
// Incluir footer
include 'includes/footer.php';
?>
