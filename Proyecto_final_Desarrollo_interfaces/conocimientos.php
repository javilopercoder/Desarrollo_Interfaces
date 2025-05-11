<?php
/**
 * Página de base de conocimientos
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Obtener categoría seleccionada (si existe)
$categoria_seleccionada = isset($_GET['categoria']) ? sanitizar($_GET['categoria']) : null;

// Establecer título de página
$page_title = $categoria_seleccionada ? 'Base de conocimientos: ' . $categoria_seleccionada : 'Base de conocimientos';

// Incluir header
include 'includes/header.php';
?>

<section>
    <div class="search-box">
        <form action="buscar.php" method="GET">
            <input type="text" name="q" placeholder="Aquí encontrará algunos artículos..." aria-label="Buscar en la base de conocimientos">
            <button type="submit" aria-label="Buscar">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>
        </form>
        <div class="advanced-search-link">
            <a href="buscar_avanzado.php">
                <i class="fas fa-sliders-h"></i> Búsqueda avanzada
            </a>
        </div>
    </div>
</section>

<h1 class="form-title">Base de conocimientos</h1>

<?php if ($categoria_seleccionada): ?>
    <a href="conocimientos.php" class="btn mb-3">
        <i class="fas fa-arrow-left"></i> Volver a todas las categorías
    </a>
    
    <h2><?php echo htmlspecialchars($categoria_seleccionada); ?></h2>
    
    <?php
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM conocimientos WHERE categoria = ? ORDER BY titulo');
        $stmt->execute([$categoria_seleccionada]);
        $articulos = $stmt->fetchAll();
        
        if (count($articulos) > 0):
    ?>
    <div class="knowledge-list">
        <?php foreach ($articulos as $articulo): ?>
            <div class="knowledge-article">
                <h3><a href="ver_articulo.php?id=<?php echo $articulo['id_conocimiento']; ?>"><?php echo htmlspecialchars($articulo['titulo']); ?></a></h3>
                <div class="knowledge-meta">
                    <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($articulo['categoria']); ?></span>
                    <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($articulo['fecha_creacion'])); ?></span>
                    <?php if (isset($articulo['visitas'])): ?>
                    <span><i class="fas fa-eye"></i> <?php echo $articulo['visitas']; ?> visitas</span>
                    <?php endif; ?>
                </div>
                <div class="knowledge-summary">
                    <?php 
                    // Mostrar el resumen si existe, si no, mostrar un extracto del contenido
                    if (!empty($articulo['resumen'])) {
                        echo nl2br(htmlspecialchars($articulo['resumen']));
                    } else {
                        // Mostrar solo los primeros 200 caracteres del contenido
                        $extracto = substr($articulo['contenido'], 0, 200);
                        if (strlen($articulo['contenido']) > 200) {
                            $extracto .= '...';
                        }
                        echo nl2br(htmlspecialchars($extracto));
                    }
                    ?>
                </div>
                <?php if (!empty($articulo['etiquetas'])): ?>
                <div class="article-tags">
                    <?php 
                    $etiquetas = explode(',', $articulo['etiquetas']);
                    foreach ($etiquetas as $etiqueta) {
                        $etiqueta = trim($etiqueta);
                        echo '<a href="buscar.php?q=' . urlencode($etiqueta) . '&tipo=conocimientos" class="tag">';
                        echo '<i class="fas fa-tag"></i> ' . htmlspecialchars($etiqueta);
                        echo '</a>';
                    }
                    ?>
                </div>
                <?php endif; ?>
                <div class="knowledge-actions">
                    <a href="ver_articulo.php?id=<?php echo $articulo['id_conocimiento']; ?>" class="btn">Leer artículo completo</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">No se encontraron artículos en esta categoría.</div>
    <?php endif;
    } catch (PDOException $e) {
        echo '<div class="alert alert-error">Error al cargar los artículos: ' . $e->getMessage() . '</div>';
    }
    ?>
    
<?php else: ?>
    <!-- Mostrar todas las categorías -->
    <div class="knowledge-categories">
        <?php
        try {
            $db = getDB();
            $query = "SELECT DISTINCT categoria, 
                             (SELECT contenido FROM conocimientos WHERE categoria = k.categoria LIMIT 1) as descripcion,
                             COUNT(*) as num_articulos
                      FROM conocimientos k
                      GROUP BY categoria
                      ORDER BY categoria";
            $stmt = $db->query($query);
            
            while ($categoria = $stmt->fetch()) {
                echo '<div class="category-card">';
                echo '<h3>' . htmlspecialchars($categoria['categoria']) . ' (' . $categoria['num_articulos'] . ')</h3>';
                echo '<p>' . htmlspecialchars($categoria['descripcion']) . '</p>';
                echo '<a href="conocimientos.php?categoria=' . urlencode($categoria['categoria']) . '" class="btn">Ver artículos</a>';
                echo '</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-error">Error al cargar las categorías: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
<?php endif; ?>

<?php
// Si el usuario es administrador, mostrar botón para agregar artículo
if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte')):
?>
<div class="mt-4">
    <a href="nuevo_articulo.php" class="btn">
        <i class="fas fa-plus"></i> Agregar nuevo artículo
    </a>
</div>
<?php endif; ?>

<?php
// Incluir footer
include 'includes/footer.php';
?>
