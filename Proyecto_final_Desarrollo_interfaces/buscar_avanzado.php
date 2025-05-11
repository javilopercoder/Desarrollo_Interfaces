<?php
/**
 * Página de búsqueda avanzada para la base de conocimientos
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Obtener términos de búsqueda y filtros
$query = isset($_GET['q']) ? sanitizar($_GET['q']) : '';
$categoria = isset($_GET['categoria']) ? sanitizar($_GET['categoria']) : '';
$etiquetas = isset($_GET['etiquetas']) ? $_GET['etiquetas'] : [];
$orden = isset($_GET['orden']) ? sanitizar($_GET['orden']) : 'relevancia';
$fecha_desde = isset($_GET['fecha_desde']) ? sanitizar($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? sanitizar($_GET['fecha_hasta']) : '';

// Establecer título de página
$page_title = 'Búsqueda avanzada: Base de conocimientos';

// Incluir header
include 'includes/header.php';

// Obtener todas las categorías y etiquetas para el formulario de filtro
try {
    $db = getDB();
    
    // Obtener categorías disponibles
    $stmt_cat = $db->query('SELECT DISTINCT categoria FROM conocimientos ORDER BY categoria');
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener etiquetas disponibles
    try {
        $stmt_tag = $db->query('SELECT etiquetas FROM conocimientos WHERE etiquetas IS NOT NULL AND etiquetas != ""');
        $todas_etiquetas = [];
        
        while ($row = $stmt_tag->fetch()) {
            if (isset($row['etiquetas']) && !empty($row['etiquetas'])) {
                $tags = explode(',', $row['etiquetas']);
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag) && !in_array($tag, $todas_etiquetas)) {
                        $todas_etiquetas[] = $tag;
                    }
                }
            }
        }
        
        // Si no hay etiquetas, inicializar como array vacío para evitar warnings
        if (empty($todas_etiquetas)) {
            $todas_etiquetas = [];
        } else {
            sort($todas_etiquetas);
        }
    } catch (PDOException $e) {
        // Si hay un error, inicializar como array vacío
        $todas_etiquetas = [];
    }
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar filtros: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
}
?>

<section class="advanced-search">
    <div class="breadcrumbs">
        <a href="index.php"><i class="fas fa-home"></i> Inicio</a> <span class="separator">&raquo;</span>
        <a href="conocimientos.php"><i class="fas fa-book"></i> Base de conocimientos</a> <span class="separator">&raquo;</span>
        <span><i class="fas fa-search"></i> Búsqueda avanzada</span>
    </div>
    
    <h1 class="form-title">Búsqueda avanzada</h1>
    
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?>">
            <?php 
                echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']);
                unset($_SESSION['mensaje_tipo']);
            ?>
        </div>
    <?php endif; ?>

    <div class="search-container">
        <div class="search-filters">
            <form action="buscar_avanzado.php" method="GET" id="search-form">
                <div class="filter-section">
                    <h4><i class="fas fa-search"></i> Buscar por palabras clave</h4>
                    <div class="form-group">
                        <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Buscar en títulos, contenido..." class="form-control">
                    </div>
                    
                    <div class="keyword-list">
                        <?php
                        // Palabras clave comunes para sugerir
                        $keywords = ['error', 'problema', 'instalación', 'configuración', 'usuario', 'contraseña', 'sistema', 'acceso', 'ayuda', 'tutorial'];
                        foreach ($keywords as $keyword): ?>
                            <span class="keyword-tag" onclick="addKeyword('<?php echo $keyword; ?>')"><?php echo $keyword; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h4><i class="fas fa-folder"></i> Filtrar por categoría</h4>
                    <div class="form-group">
                        <select id="categoria" name="categoria" class="form-control">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $cat === $categoria ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h4><i class="fas fa-tags"></i> Filtrar por etiquetas</h4>
                    <div class="tag-select">
                        <?php if (count($todas_etiquetas) > 0): ?>
                            <?php foreach ($todas_etiquetas as $tag): ?>
                            <div class="tag-checkbox">
                                <input type="checkbox" id="tag_<?php echo md5($tag); ?>" name="etiquetas[]" 
                                    value="<?php echo htmlspecialchars($tag); ?>"
                                    <?php echo in_array($tag, $etiquetas) ? 'checked' : ''; ?>>
                                <label for="tag_<?php echo md5($tag); ?>"><?php echo htmlspecialchars($tag); ?></label>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No hay etiquetas disponibles.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h4><i class="fas fa-calendar"></i> Filtrar por fecha</h4>
                    <div class="date-range">
                        <div class="date-inputs">
                            <div>
                                <label for="fecha_desde">Desde:</label>
                                <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>" class="form-control">
                            </div>
                            <div>
                                <label for="fecha_hasta">Hasta:</label>
                                <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h4><i class="fas fa-sort"></i> Opciones de visualización</h4>
                    <div class="form-group">
                        <label for="orden">Ordenar por:</label>
                        <select id="orden" name="orden" class="form-control">
                            <option value="relevancia" <?php echo $orden === 'relevancia' ? 'selected' : ''; ?>>Relevancia</option>
                            <option value="fecha_desc" <?php echo $orden === 'fecha_desc' ? 'selected' : ''; ?>>Fecha (más reciente)</option>
                            <option value="fecha_asc" <?php echo $orden === 'fecha_asc' ? 'selected' : ''; ?>>Fecha (más antigua)</option>
                            <option value="visitas" <?php echo $orden === 'visitas' ? 'selected' : ''; ?>>Más visitados</option>
                            <option value="valoracion" <?php echo $orden === 'valoracion' ? 'selected' : ''; ?>>Mejor valorados</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" id="reset-btn" class="btn">
                        <i class="fas fa-redo"></i> Restablecer filtros
                    </button>
                </div>
                
                <!-- Etiquetas activas -->
                <div id="active-filters" class="filter-active-tags" style="display: none;">
                    <h4 style="width: 100%; margin-bottom: 10px;"><i class="fas fa-filter"></i> Filtros activos:</h4>
                    <div id="active-tags-container"></div>
                </div>
            </form>
        </div>
        
        <?php
        // Procesar búsqueda solo si se ha enviado algún criterio
        if (!empty($query) || !empty($categoria) || !empty($etiquetas) || !empty($fecha_desde) || !empty($fecha_hasta)) {
            try {
                $db = getDB();
                
                // Construir la consulta SQL base
                $sql = "
                    SELECT c.*, 
                           u.nombre as autor_nombre,
                           (SELECT AVG(v.valoracion) FROM valoraciones v WHERE v.id_conocimiento = c.id_conocimiento) as valoracion_media,
                           (SELECT COUNT(v.id_valoracion) FROM valoraciones v WHERE v.id_conocimiento = c.id_conocimiento) as total_valoraciones
                    FROM conocimientos c
                    LEFT JOIN usuarios u ON c.id_autor = u.id_usuario
                    WHERE 1=1
                ";
                
                $params = [];
                
                // Filtrar por texto de búsqueda
                if (!empty($query)) {
                    $sql .= " AND (
                        c.titulo LIKE ? OR
                        c.contenido LIKE ? OR
                        c.resumen LIKE ?
                    )";
                    $search_term = "%$query%";
                    $params[] = $search_term;
                    $params[] = $search_term;
                    $params[] = $search_term;
                }
                
                // Filtrar por categoría
                if (!empty($categoria)) {
                    $sql .= " AND c.categoria = ?";
                    $params[] = $categoria;
                }
                
                // Filtrar por etiquetas
                if (!empty($etiquetas)) {
                    $sql .= " AND (";
                    foreach ($etiquetas as $index => $etiqueta) {
                        if ($index > 0) {
                            $sql .= " OR ";
                        }
                        $sql .= "c.etiquetas LIKE ?";
                        $params[] = '%' . $etiqueta . '%';
                    }
                    $sql .= ")";
                }
                
                // Filtrar por fecha
                if (!empty($fecha_desde)) {
                    $sql .= " AND c.fecha_creacion >= ?";
                    $params[] = $fecha_desde . ' 00:00:00';
                }
                
                if (!empty($fecha_hasta)) {
                    $sql .= " AND c.fecha_creacion <= ?";
                    $params[] = $fecha_hasta . ' 23:59:59';
                }
                
                // Ordenar resultados según el criterio seleccionado
                switch ($orden) {
                    case 'fecha_desc':
                        $sql .= " ORDER BY c.fecha_creacion DESC";
                        break;
                    case 'fecha_asc':
                        $sql .= " ORDER BY c.fecha_creacion ASC";
                        break;
                    case 'visitas':
                        $sql .= " ORDER BY c.visitas DESC";
                        break;
                    case 'valoracion':
                        $sql .= " ORDER BY valoracion_media DESC";
                        break;
                    default: // relevancia o cualquier otro valor
                        if (!empty($query)) {
                            // Si hay query, dar prioridad a coincidencias en título
                            $sql .= " ORDER BY 
                                CASE WHEN c.titulo LIKE ? THEN 1
                                     WHEN c.resumen LIKE ? THEN 2
                                     ELSE 3
                                END,
                                c.visitas DESC,
                                valoracion_media DESC";
                            $params[] = "%$query%";
                            $params[] = "%$query%";
                        } else {
                            // Si no hay query, ordenar por fecha de actualización
                            $sql .= " ORDER BY c.fecha_actualizacion DESC";
                        }
                }
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $articulos = $stmt->fetchAll();
                
                echo '<div class="search-results">';
                echo '<h3>Resultados (' . count($articulos) . ')</h3>';
                
                if (count($articulos) > 0) {
                    foreach ($articulos as $articulo) {
                        echo '<div class="result-item">';
                        
                        // Título con enlace
                        echo '<h4><a href="ver_articulo.php?id=' . $articulo['id_conocimiento'] . '">' . 
                              htmlspecialchars($articulo['titulo']) . '</a></h4>';
                        
                        // Metadatos
                        echo '<div class="result-meta">';
                        
                        // Categoría
                        echo '<span class="category"><i class="fas fa-folder"></i> ';
                        echo '<a href="conocimientos.php?categoria=' . urlencode($articulo['categoria']) . '">';
                        echo htmlspecialchars($articulo['categoria']) . '</a></span>';
                        
                        // Fechas
                        echo '<span class="date"><i class="fas fa-calendar"></i> ';
                        echo date('d/m/Y', strtotime($articulo['fecha_creacion']));
                        if ($articulo['fecha_actualizacion'] != $articulo['fecha_creacion']) {
                            echo ' <span title="Actualizado el ' . date('d/m/Y', strtotime($articulo['fecha_actualizacion'])) . '">';
                            echo '<i class="fas fa-edit" style="margin-left:5px;"></i></span>';
                        }
                        echo '</span>';
                        
                        // Valoración con estrellas visuales
                        echo '<span class="rating">';
                        if ($articulo['valoracion_media']) {
                            $valoracion_redondeada = round($articulo['valoracion_media']);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $valoracion_redondeada) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            echo ' <span class="rating-text">' . number_format($articulo['valoracion_media'], 1) . '/5';
                            echo ' (' . $articulo['total_valoraciones'] . ')</span>';
                        } else {
                            echo '<span class="no-rating">Sin valoraciones</span>';
                        }
                        echo '</span>';
                        
                        // Visitas
                        echo '<span class="views"><i class="fas fa-eye"></i> ' . $articulo['visitas'] . ' visitas</span>';
                        
                        echo '</div>';
                        
                        // Resumen o extracto con resaltado de términos de búsqueda si existen
                        echo '<div class="result-summary">';
                        $contenido = '';
                        
                        if (!empty($articulo['resumen'])) {
                            $contenido = $articulo['resumen'];
                        } else {
                            $contenido = substr($articulo['contenido'], 0, 200);
                            if (strlen($articulo['contenido']) > 200) {
                                $contenido .= '...';
                            }
                        }
                        
                        // Resaltar términos de búsqueda si existen
                        if (!empty($query)) {
                            $terminos = explode(' ', $query);
                            foreach ($terminos as $termino) {
                                if (strlen($termino) > 3) { // Solo resaltar términos significativos
                                    $contenido = preg_replace('/(' . preg_quote($termino, '/') . ')/i', '<mark>$1</mark>', $contenido);
                                }
                            }
                            echo nl2br($contenido);
                        } else {
                            echo nl2br(htmlspecialchars($contenido));
                        }
                        echo '</div>';
                        
                        // Etiquetas
                        if (!empty($articulo['etiquetas'])) {
                            echo '<div class="result-tags">';
                            $etiquetas_arr = explode(',', $articulo['etiquetas']);
                            foreach ($etiquetas_arr as $etiqueta) {
                                $etiqueta = trim($etiqueta);
                                echo '<a href="buscar_avanzado.php?etiquetas[]=' . urlencode($etiqueta) . '" class="tag">';
                                echo '<i class="fas fa-tag"></i> ' . htmlspecialchars($etiqueta) . '</a>';
                            }
                            echo '</div>';
                        }
                        
                        echo '<div class="result-actions">';
                        echo '<a href="ver_articulo.php?id=' . $articulo['id_conocimiento'] . '" class="btn">Leer artículo completo</a>';
                        echo '</div>';
                        
                        echo '</div>'; // .result-item
                    }
                } else {
                    echo '<div class="alert alert-info">No se encontraron artículos que coincidan con tus criterios de búsqueda.</div>';
                }
                echo '</div>'; // .search-results
                
            } catch (PDOException $e) {
                echo '<div class="alert alert-error">Error al realizar la búsqueda: ' . $e->getMessage() . '</div>';
            }
        } else {
            echo '<div class="search-intro">';
            echo '<p>Utiliza los filtros para encontrar artículos específicos en nuestra base de conocimientos.</p>';
            echo '</div>';
        }
        ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botón para restablecer filtros
    document.getElementById('reset-btn').addEventListener('click', function() {
        document.getElementById('q').value = '';
        document.getElementById('categoria').selectedIndex = 0;
        document.getElementById('fecha_desde').value = '';
        document.getElementById('fecha_hasta').value = '';
        document.getElementById('orden').selectedIndex = 0;
        
        // Desmarcar todas las etiquetas
        const checkboxes = document.querySelectorAll('input[name="etiquetas[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Ocultar filtros activos
        document.getElementById('active-filters').style.display = 'none';
        document.getElementById('active-tags-container').innerHTML = '';
    });
    
    // Actualizar filtros activos al cargar la página
    updateActiveFilters();
    
    // Añadir eventos para actualizar filtros activos
    document.getElementById('q').addEventListener('input', updateActiveFilters);
    document.getElementById('categoria').addEventListener('change', updateActiveFilters);
    document.querySelectorAll('input[name="etiquetas[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateActiveFilters);
    });
    document.getElementById('fecha_desde').addEventListener('change', updateActiveFilters);
    document.getElementById('fecha_hasta').addEventListener('change', updateActiveFilters);
});

// Función para agregar una palabra clave al campo de búsqueda
function addKeyword(keyword) {
    const searchInput = document.getElementById('q');
    const currentValue = searchInput.value.trim();
    
    // Si ya existe la palabra clave, no la agregamos de nuevo
    if (currentValue.includes(keyword)) {
        return;
    }
    
    // Agregar la palabra clave, separándola con espacio si ya hay texto
    searchInput.value = currentValue ? currentValue + ' ' + keyword : keyword;
    
    // Resaltar la etiqueta de palabra clave seleccionada
    document.querySelectorAll('.keyword-tag').forEach(tag => {
        if (tag.innerText === keyword) {
            tag.classList.add('active');
        }
    });
    
    // Actualizar filtros activos
    updateActiveFilters();
}

// Función para actualizar la visualización de filtros activos
function updateActiveFilters() {
    const activeFiltersContainer = document.getElementById('active-filters');
    const tagsContainer = document.getElementById('active-tags-container');
    tagsContainer.innerHTML = '';
    
    let hasActiveFilters = false;
    
    // Palabra clave
    const query = document.getElementById('q').value.trim();
    if (query) {
        addActiveTag('Buscar: ' + query, function() {
            document.getElementById('q').value = '';
            updateActiveFilters();
        });
        hasActiveFilters = true;
    }
    
    // Categoría
    const categoriaSelect = document.getElementById('categoria');
    const categoriaValue = categoriaSelect.options[categoriaSelect.selectedIndex].text;
    if (categoriaSelect.selectedIndex > 0) {
        addActiveTag('Categoría: ' + categoriaValue, function() {
            categoriaSelect.selectedIndex = 0;
            updateActiveFilters();
        });
        hasActiveFilters = true;
    }
    
    // Etiquetas
    const selectedTags = document.querySelectorAll('input[name="etiquetas[]"]:checked');
    selectedTags.forEach(tag => {
        const tagLabel = document.querySelector('label[for="' + tag.id + '"]').innerText;
        addActiveTag('Etiqueta: ' + tagLabel, function() {
            tag.checked = false;
            updateActiveFilters();
        });
        hasActiveFilters = true;
    });
    
    // Fechas
    const fechaDesde = document.getElementById('fecha_desde').value;
    if (fechaDesde) {
        addActiveTag('Desde: ' + formatDate(fechaDesde), function() {
            document.getElementById('fecha_desde').value = '';
            updateActiveFilters();
        });
        hasActiveFilters = true;
    }
    
    const fechaHasta = document.getElementById('fecha_hasta').value;
    if (fechaHasta) {
        addActiveTag('Hasta: ' + formatDate(fechaHasta), function() {
            document.getElementById('fecha_hasta').value = '';
            updateActiveFilters();
        });
        hasActiveFilters = true;
    }
    
    // Mostrar u ocultar el contenedor de filtros activos
    activeFiltersContainer.style.display = hasActiveFilters ? 'flex' : 'none';
    
    // Función auxiliar para agregar una etiqueta de filtro activo
    function addActiveTag(text, removeCallback) {
        const tag = document.createElement('div');
        tag.className = 'active-tag';
        tag.innerHTML = text + ' <span class="remove-tag"><i class="fas fa-times"></i></span>';
        tag.querySelector('.remove-tag').addEventListener('click', removeCallback);
        tagsContainer.appendChild(tag);
    }
    
    // Función para formatear fechas
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES');
    }
}
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
