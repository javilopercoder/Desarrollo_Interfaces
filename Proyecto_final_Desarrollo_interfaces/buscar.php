<?php
/**
 * Página de búsqueda en el sistema de ticketing
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Obtener término de búsqueda
$query = isset($_GET['q']) ? sanitizar($_GET['q']) : '';

// Establecer título de página
$page_title = 'Resultados de búsqueda: ' . $query;

// Incluir header
include 'includes/header.php';
?>

<section>
    <h2 class="form-title">Resultados de búsqueda: "<?php echo htmlspecialchars($query); ?>"</h2>
    
    <div class="search-box mb-4">
        <form action="buscar.php" method="GET">
            <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Buscar en tickets y base de conocimientos..." aria-label="Buscar">
            <button type="submit" aria-label="Buscar">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>
        </form>
        <div class="advanced-search-link">
            <a href="buscar_avanzado.php<?php echo !empty($query) ? '?q=' . urlencode($query) : ''; ?>">
                <i class="fas fa-sliders-h"></i> Búsqueda avanzada
            </a>
        </div>
    </div>
    
    <?php
    // Si no hay consulta, mostrar mensaje
    if (empty($query)) {
        echo '<div class="alert alert-info">Por favor, introduce un término de búsqueda.</div>';
    } else {
        try {
            $db = getDB();
            
            // Resultados en la base de conocimientos
            echo '<h3>Base de conocimientos</h3>';
            
            $stmt = $db->prepare('
                SELECT *
                FROM conocimientos
                WHERE titulo LIKE ? OR contenido LIKE ? OR categoria LIKE ?
                ORDER BY categoria, titulo
            ');
            
            $search_term = "%$query%";
            $stmt->execute([$search_term, $search_term, $search_term]);
            $articulos = $stmt->fetchAll();
            
            if (count($articulos) > 0) {
                echo '<div class="search-results knowledge-results">';
                foreach ($articulos as $articulo) {
                    echo '<div class="search-item">';
                    echo '<h4><a href="conocimientos.php?categoria=' . urlencode($articulo['categoria']) . '">' . 
                          htmlspecialchars($articulo['titulo']) . '</a></h4>';
                    echo '<div class="search-meta">';
                    echo '<span class="search-category">Categoría: ' . htmlspecialchars($articulo['categoria']) . '</span>';
                    echo '<span class="search-date">Fecha: ' . date('d/m/Y', strtotime($articulo['fecha_creacion'])) . '</span>';
                    echo '</div>';
                    
                    // Mostrar extracto del contenido
                    $extracto = substr($articulo['contenido'], 0, 200) . (strlen($articulo['contenido']) > 200 ? '...' : '');
                    echo '<div class="search-excerpt">' . nl2br(htmlspecialchars($extracto)) . '</div>';
                    
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<div class="alert alert-info">No se encontraron resultados en la base de conocimientos.</div>';
            }
            
            // Resultados en tickets (solo para usuarios logueados)
            if (isset($_SESSION['usuario_id'])) {
                echo '<h3>Tickets</h3>';
                
                $sql = '
                    SELECT t.*, u.nombre as nombre_usuario
                    FROM tickets t
                    JOIN usuarios u ON t.id_usuario = u.id_usuario
                    WHERE t.titulo LIKE ? OR t.descripcion LIKE ? OR t.categoria LIKE ?
                ';
                
                // Si no es administrador o soporte, solo mostrar los tickets del usuario
                $params = [$search_term, $search_term, $search_term];
                if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte') {
                    $sql .= ' AND t.id_usuario = ?';
                    $params[] = $_SESSION['usuario_id'];
                }
                
                $sql .= ' ORDER BY t.fecha_creacion DESC';
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $tickets = $stmt->fetchAll();
                
                if (count($tickets) > 0) {
                    echo '<div class="search-results ticket-results">';
                    foreach ($tickets as $ticket) {
                        // Determinar clases CSS para prioridad y estado
                        $prioridad_class = '';
                        switch ($ticket['prioridad']) {
                            case 'alta': $prioridad_class = 'priority-high'; break;
                            case 'media': $prioridad_class = 'priority-medium'; break;
                            case 'baja': $prioridad_class = 'priority-low'; break;
                        }
                        
                        $estado_class = '';
                        switch ($ticket['estado']) {
                            case 'abierto': $estado_class = 'status-open'; break;
                            case 'en proceso': $estado_class = 'status-in-progress'; break;
                            case 'cerrado': $estado_class = 'status-closed'; break;
                        }
                        
                        echo '<div class="search-item">';
                        echo '<h4><a href="ver_ticket.php?id=' . $ticket['id_ticket'] . '">' . 
                              htmlspecialchars($ticket['titulo']) . '</a></h4>';
                        echo '<div class="search-meta">';
                        echo '<span class="ticket-id">Ticket #' . $ticket['id_ticket'] . '</span>';
                        echo '<span class="ticket-status ' . $estado_class . '">' . htmlspecialchars($ticket['estado']) . '</span>';
                        echo '<span class="' . $prioridad_class . '">Prioridad: ' . htmlspecialchars($ticket['prioridad']) . '</span>';
                        echo '<span>Categoría: ' . htmlspecialchars($ticket['categoria']) . '</span>';
                        echo '<span>Fecha: ' . date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])) . '</span>';
                        
                        if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte') {
                            echo '<span>Por: ' . htmlspecialchars($ticket['nombre_usuario']) . '</span>';
                        }
                        
                        echo '</div>';
                        
                        // Mostrar extracto de la descripción
                        $extracto = substr($ticket['descripcion'], 0, 200) . (strlen($ticket['descripcion']) > 200 ? '...' : '');
                        echo '<div class="search-excerpt">' . nl2br(htmlspecialchars($extracto)) . '</div>';
                        
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-info">No se encontraron tickets relacionados con tu búsqueda.</div>';
                }
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-error">Error al realizar la búsqueda: ' . $e->getMessage() . '</div>';
        }
    }
    ?>
</section>

<style>
.search-results {
    margin-bottom: 30px;
}

.search-item {
    background-color: #fff;
    padding: 15px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 15px;
    border-left: 4px solid #0099ff;
}

.search-item h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.search-item h4 a {
    color: #0099ff;
    text-decoration: none;
}

.search-item h4 a:hover {
    text-decoration: underline;
}

.search-meta {
    display: flex;
    flex-wrap: wrap;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 10px;
    gap: 15px;
}

.search-excerpt {
    line-height: 1.5;
    color: #333;
    margin-top: 10px;
    white-space: pre-wrap;
}
</style>

<?php
// Incluir footer
include 'includes/footer.php';
?>
