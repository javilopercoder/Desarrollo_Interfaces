<?php
/**
 * Página de inicio del sistema de ticketing
 */

// Verificar rutas duplicadas
require_once 'includes/verificar_rutas.php';

// Incluir archivo de conexión y contador
require_once 'includes/conexion.php';
require_once 'includes/contador_visitas.php';

// Establecer título de página
$page_title = 'Inicio';
$show_visit_counter = true;

// Verificar si la estructura de la base de datos está actualizada
$db_actualizada = true;
$problemas_detectados = [];

try {
    $db = getDB();
    
    // Verificamos primero que existan las tablas necesarias
    $tablas_requeridas = ['conocimientos', 'valoraciones', 'articulos_relacionados'];
    $tablas_faltantes = [];
    
    foreach ($tablas_requeridas as $tabla) {
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tabla'");
        if ($stmt->fetch() === false) {
            $tablas_faltantes[] = $tabla;
            $db_actualizada = false;
        }
    }
    
    if (!empty($tablas_faltantes)) {
        $problemas_detectados[] = "Faltan las siguientes tablas: " . implode(", ", $tablas_faltantes);
    }
    
    // Si al menos existe la tabla conocimientos, verificamos sus columnas
    if (!in_array('conocimientos', $tablas_faltantes)) {
        $stmt = $db->query("PRAGMA table_info(conocimientos)");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnas_requeridas = ['visitas', 'etiquetas', 'resumen', 'imagen', 'fecha_actualizacion'];
        $columnas_existentes = [];
        
        foreach ($columnas as $col) {
            $columnas_existentes[] = $col['name'];
        }
        
        $columnas_faltantes = array_diff($columnas_requeridas, $columnas_existentes);
        
        if (!empty($columnas_faltantes)) {
            $problemas_detectados[] = "Faltan las siguientes columnas en la tabla 'conocimientos': " . implode(", ", $columnas_faltantes);
            $db_actualizada = false;
        }
    }
} catch (PDOException $e) {
    // En caso de error, asumir que puede necesitar actualización
    $db_actualizada = false;
    $problemas_detectados[] = "Error al acceder a la base de datos: " . $e->getMessage();
}

// Si la base de datos no está actualizada, redirigir a la página de actualización con información
if (!$db_actualizada) {
    if (!empty($problemas_detectados)) {
        $_SESSION['db_problemas'] = $problemas_detectados;
    }
    header('Location: actualizar.php');
    exit;
}

// Obtener el contador de visitas (contar solo visitantes únicos)
$visitas = getContadorVisitas(true, 3600);

// Incluir header
include 'includes/header.php';
?>

<section class="hero">
    <h1>Hola, ¿cómo podemos ayudarle?</h1>
    
    <div class="search-box">
        <form action="buscar.php" method="GET">
            <input type="text" name="q" placeholder="Introduzca aquí su término de búsqueda..." aria-label="Buscar">
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

<section class="features">
    <div class="feature-card">
        <i class="fas fa-book"></i>
        <h3>Buscar artículos</h3>
        <p>Explore los artículos y descubra las prácticas recomendadas de nuestros expertos</p>
        <a href="conocimientos.php" class="btn">Ver artículos</a>
    </div>
    
    <div class="feature-card">
        <i class="fas fa-list-alt"></i>
        <h3>Ver todos los tickets</h3>
        <p>Lleve un seguimiento del progreso de su ticket y su interacción con los equipos de soporte</p>
        <a href="<?php echo isset($_SESSION['usuario_id']) ? 'tickets.php' : 'login.php'; ?>" class="btn">
            <?php echo isset($_SESSION['usuario_id']) ? 'Ver tickets' : 'Iniciar sesión'; ?>
        </a>
    </div>
    
    <div class="feature-card">
        <i class="fas fa-ticket-alt"></i>
        <h3>Enviar un ticket</h3>
        <p>Describa su problema rellenando el formulario de ticket de soporte</p>
        <a href="<?php echo isset($_SESSION['usuario_id']) ? 'nuevo_ticket.php' : 'login.php'; ?>" class="btn">
            <?php echo isset($_SESSION['usuario_id']) ? 'Crear ticket' : 'Iniciar sesión'; ?>
        </a>
    </div>
</section>

<section>
    <h2 class="form-title">Base de conocimientos</h2>
    
    <div class="knowledge-categories">
        <?php
        try {
            $db = getDB();
            $query = "SELECT DISTINCT categoria, 
                             (SELECT contenido FROM conocimientos WHERE categoria = k.categoria LIMIT 1) as descripcion,
                             (SELECT COUNT(*) FROM conocimientos WHERE categoria = k.categoria) as num_articulos
                      FROM conocimientos k
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
</section>

<section class="featured-articles">
    <div class="container">
        <div class="featured-wrapper">
            <div class="featured-column">
                <h2 class="section-title"><i class="fas fa-star"></i> Artículos más valorados</h2>
                <div class="featured-list">
                    <?php
                    try {
                        // Consulta para obtener los artículos mejor valorados
                        $stmt = $db->query("
                            SELECT c.id_conocimiento, c.titulo, c.categoria, c.resumen, 
                                   AVG(v.valoracion) as valoracion_media,
                                   COUNT(v.id_valoracion) as total_valoraciones
                            FROM conocimientos c
                            JOIN valoraciones v ON c.id_conocimiento = v.id_conocimiento
                            GROUP BY c.id_conocimiento
                            HAVING COUNT(v.id_valoracion) >= 1
                            ORDER BY valoracion_media DESC, total_valoraciones DESC
                            LIMIT 4
                        ");
                        
                        $articulos_valorados = $stmt->fetchAll();
                        
                        if (count($articulos_valorados) > 0) {
                            foreach ($articulos_valorados as $articulo) {
                                echo '<div class="featured-item">';
                                
                                echo '<h4><a href="ver_articulo.php?id=' . $articulo['id_conocimiento'] . '">' . 
                                      htmlspecialchars($articulo['titulo']) . '</a></h4>';
                                      
                                echo '<div class="featured-meta">';
                                echo '<span class="featured-category"><i class="fas fa-folder"></i> ' . 
                                     htmlspecialchars($articulo['categoria']) . '</span>';
                                
                                // Mostrar valoración
                                echo '<span class="featured-rating">';
                                $valoracion = round($articulo['valoracion_media']);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $valoracion) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                echo ' (' . $articulo['total_valoraciones'] . ')';
                                echo '</span>';
                                echo '</div>'; // .featured-meta
                                
                                // Resumen breve
                                if (!empty($articulo['resumen'])) {
                                    $extracto = substr($articulo['resumen'], 0, 100);
                                    if (strlen($articulo['resumen']) > 100) {
                                        $extracto .= '...';
                                    }
                                    echo '<p>' . htmlspecialchars($extracto) . '</p>';
                                }
                                
                                echo '</div>'; // .featured-item
                            }
                        } else {
                            echo '<p class="no-results">No hay artículos valorados todavía.</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="error">Error al cargar artículos valorados: ' . $e->getMessage() . '</p>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="featured-column">
                <h2 class="section-title"><i class="fas fa-eye"></i> Artículos más visitados</h2>
                <div class="featured-list">
                    <?php
                    try {
                        // Consulta para obtener los artículos más visitados
                        $stmt = $db->query("
                            SELECT c.id_conocimiento, c.titulo, c.categoria, c.resumen, c.visitas
                            FROM conocimientos c
                            WHERE c.visitas > 0
                            ORDER BY c.visitas DESC
                            LIMIT 4
                        ");
                        
                        $articulos_visitados = $stmt->fetchAll();
                        
                        if (count($articulos_visitados) > 0) {
                            foreach ($articulos_visitados as $articulo) {
                                echo '<div class="featured-item">';
                                
                                echo '<h4><a href="ver_articulo.php?id=' . $articulo['id_conocimiento'] . '">' . 
                                      htmlspecialchars($articulo['titulo']) . '</a></h4>';
                                      
                                echo '<div class="featured-meta">';
                                echo '<span class="featured-category"><i class="fas fa-folder"></i> ' . 
                                     htmlspecialchars($articulo['categoria']) . '</span>';
                                
                                // Mostrar visitas
                                echo '<span class="featured-views"><i class="fas fa-eye"></i> ' . 
                                     $articulo['visitas'] . ' visitas</span>';
                                echo '</div>'; // .featured-meta
                                
                                // Resumen breve
                                if (!empty($articulo['resumen'])) {
                                    $extracto = substr($articulo['resumen'], 0, 100);
                                    if (strlen($articulo['resumen']) > 100) {
                                        $extracto .= '...';
                                    }
                                    echo '<p>' . htmlspecialchars($extracto) . '</p>';
                                }
                                
                                echo '</div>'; // .featured-item
                            }
                        } else {
                            echo '<p class="no-results">No hay artículos visitados todavía.</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="error">Error al cargar artículos visitados: ' . $e->getMessage() . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="view-all-link">
            <a href="conocimientos.php" class="btn btn-outline">
                <i class="fas fa-book"></i> Ver todos los artículos
            </a>
            <a href="buscar_avanzado.php" class="btn btn-outline">
                <i class="fas fa-search"></i> Búsqueda avanzada
            </a>
        </div>
    </div>
</section>

<?php
// Incluir footer
include 'includes/footer.php';
?>
