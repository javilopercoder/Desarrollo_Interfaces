<?php
/**
 * Página de detalle de artículo de conocimiento
 */

// Incluir archivo de conexión y verificación de rutas
require_once 'includes/verificar_rutas.php';
require_once 'includes/conexion.php';

// Verificar si se proporcionó un ID de artículo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de artículo no válido.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: conocimientos.php');
    exit;
}

$id_articulo = (int)$_GET['id'];

try {
    $db = getDB();
    
    // Actualizar contador de visitas
    $stmt = $db->prepare('UPDATE conocimientos SET visitas = visitas + 1 WHERE id_conocimiento = ?');
    $stmt->execute([$id_articulo]);
    
    // Obtener detalles del artículo
    $stmt = $db->prepare('
        SELECT c.*, u.nombre as autor_nombre 
        FROM conocimientos c 
        LEFT JOIN usuarios u ON c.id_autor = u.id_usuario 
        WHERE c.id_conocimiento = ?
    ');
    $stmt->execute([$id_articulo]);
    $articulo = $stmt->fetch();
    
    if (!$articulo) {
        $_SESSION['mensaje'] = 'Artículo no encontrado.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: conocimientos.php');
        exit;
    }
    
    // Obtener valoración promedio
    $stmt = $db->prepare('
        SELECT AVG(valoracion) as valoracion_promedio, COUNT(*) as total_valoraciones 
        FROM valoraciones 
        WHERE id_conocimiento = ?
    ');
    $stmt->execute([$id_articulo]);
    $valoracion = $stmt->fetch();
    
    // Verificar si el usuario actual ha valorado este artículo
    $usuario_ha_valorado = false;
    $valoracion_usuario = null;
    
    if (isset($_SESSION['usuario_id'])) {
        $stmt = $db->prepare('
            SELECT valoracion, comentario 
            FROM valoraciones 
            WHERE id_conocimiento = ? AND id_usuario = ?
        ');
        $stmt->execute([$id_articulo, $_SESSION['usuario_id']]);
        $valoracion_usuario = $stmt->fetch();
        $usuario_ha_valorado = ($valoracion_usuario !== false);
    }
    
    // Obtener artículos relacionados
    $stmt = $db->prepare('
        SELECT c.* 
        FROM conocimientos c
        JOIN articulos_relacionados ar ON c.id_conocimiento = ar.id_articulo_relacionado
        WHERE ar.id_articulo = ?
        LIMIT 5
    ');
    $stmt->execute([$id_articulo]);
    $articulos_relacionados = $stmt->fetchAll();
    
    // Si no hay artículos relacionados manualmente, buscar por etiquetas similares
    if (count($articulos_relacionados) === 0 && !empty($articulo['etiquetas'])) {
        $etiquetas = explode(',', $articulo['etiquetas']);
        $placeholders = implode(',', array_fill(0, count($etiquetas), '?'));
        
        $sql = "
            SELECT c.* 
            FROM conocimientos c
            WHERE c.id_conocimiento != ?
            AND (";
        
        $params = [$id_articulo];
        foreach ($etiquetas as $etiqueta) {
            $sql .= " c.etiquetas LIKE ? OR";
            $params[] = '%' . trim($etiqueta) . '%';
        }
        $sql = rtrim($sql, 'OR') . ") LIMIT 5";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $articulos_relacionados = $stmt->fetchAll();
    }
    
    // Si aún no hay relacionados, simplemente buscar por la misma categoría
    if (count($articulos_relacionados) === 0) {
        $stmt = $db->prepare('
            SELECT * 
            FROM conocimientos 
            WHERE categoria = ? AND id_conocimiento != ? 
            LIMIT 5
        ');
        $stmt->execute([$articulo['categoria'], $id_articulo]);
        $articulos_relacionados = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar el artículo: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: conocimientos.php');
    exit;
}

// Procesar valoraciones si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valorar']) && isset($_SESSION['usuario_id'])) {
    $valoracion_numero = (int)$_POST['valoracion'];
    $comentario = sanitizar($_POST['comentario'] ?? '');
    
    // Validar valoración
    if ($valoracion_numero < 1 || $valoracion_numero > 5) {
        $_SESSION['mensaje'] = 'La valoración debe estar entre 1 y 5.';
        $_SESSION['mensaje_tipo'] = 'error';
    } else {
        try {
            // Si el usuario ya ha valorado, actualizar; de lo contrario, insertar
            if ($usuario_ha_valorado) {
                $stmt = $db->prepare('
                    UPDATE valoraciones 
                    SET valoracion = ?, comentario = ? 
                    WHERE id_conocimiento = ? AND id_usuario = ?
                ');
                $stmt->execute([$valoracion_numero, $comentario, $id_articulo, $_SESSION['usuario_id']]);
            } else {
                $stmt = $db->prepare('
                    INSERT INTO valoraciones (id_conocimiento, id_usuario, valoracion, comentario)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$id_articulo, $_SESSION['usuario_id'], $valoracion_numero, $comentario]);
            }
            
            $_SESSION['mensaje'] = '¡Gracias por tu valoración!';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Recargar la página para mostrar la valoración actualizada
            header('Location: ver_articulo.php?id=' . $id_articulo);
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['mensaje'] = 'Error al guardar la valoración: ' . $e->getMessage();
            $_SESSION['mensaje_tipo'] = 'error';
        }
    }
}

// Establecer título de página
$page_title = $articulo['titulo'] . ' - Base de conocimientos';

// Incluir header
include 'includes/header.php';
?>

<section class="article-detail">
    <!-- Sistema mejorado de breadcrumbs (migas de pan) para navegación -->
    <div class="breadcrumbs">
        <a href="index.php"><i class="fas fa-home"></i> Inicio</a> <span class="separator">&raquo;</span>
        <a href="conocimientos.php"><i class="fas fa-book"></i> Base de conocimientos</a> <span class="separator">&raquo;</span>
        <a href="conocimientos.php?categoria=<?php echo urlencode($articulo['categoria']); ?>"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($articulo['categoria']); ?></a> <span class="separator">&raquo;</span>
        <span><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($articulo['titulo']); ?></span>
    </div>
    
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?>">
            <?php 
                echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']);
                unset($_SESSION['mensaje_tipo']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="article-container">
        <div class="article-main">
            <h1 class="article-title"><?php echo htmlspecialchars($articulo['titulo']); ?></h1>
            
            <div class="article-meta">
                <div class="article-info">
                    <span class="category">
                        <i class="fas fa-folder"></i> 
                        <a href="conocimientos.php?categoria=<?php echo urlencode($articulo['categoria']); ?>">
                            <?php echo htmlspecialchars($articulo['categoria']); ?>
                        </a>
                    </span>
                    <span class="author">
                        <i class="fas fa-user"></i> Por <?php echo htmlspecialchars($articulo['autor_nombre']); ?>
                    </span>
                    <span class="date">
                        <i class="fas fa-calendar"></i> 
                        Publicado: <?php echo date('d/m/Y', strtotime($articulo['fecha_creacion'])); ?>
                    </span>
                    <?php if ($articulo['fecha_actualizacion'] != $articulo['fecha_creacion']): ?>
                    <span class="date">
                        <i class="fas fa-edit"></i> 
                        Actualizado: <?php echo date('d/m/Y', strtotime($articulo['fecha_actualizacion'])); ?>
                    </span>
                    <?php endif; ?>
                    <span class="views">
                        <i class="fas fa-eye"></i> <?php echo $articulo['visitas']; ?> visitas
                    </span>
                </div>
                
                <div class="article-rating">
                    <?php if ($valoracion['total_valoraciones'] > 0): ?>
                        <div class="rating">
                            <?php
                            $promedio = round($valoracion['valoracion_promedio']);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $promedio) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                            <span class="rating-text">
                                <?php echo number_format($valoracion['valoracion_promedio'], 1); ?>/5
                                (<?php echo $valoracion['total_valoraciones']; ?> valoraciones)
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="rating">Sin valoraciones aún</div>
                    <?php endif; ?>
                </div>
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
            
            <?php if (!empty($articulo['imagen'])): ?>
            <div class="article-image">
                <img src="uploads/conocimientos/<?php echo htmlspecialchars($articulo['imagen']); ?>" 
                     alt="<?php echo htmlspecialchars($articulo['titulo']); ?>">
            </div>
            <?php endif; ?>
            
            <div class="article-summary">
                <h4>Resumen</h4>
                <p><?php echo nl2br(htmlspecialchars($articulo['resumen'] ?? $articulo['contenido'])); ?></p>
            </div>
            
            <div class="article-content">
                <?php echo nl2br(htmlspecialchars($articulo['contenido'])); ?>
            </div>
            
            <!-- Sistema de valoración mejorado -->
            <div class="rating-form-container">
                <h3><i class="fas fa-star"></i> Valoraciones y comentarios</h3>
                
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <div class="article-rating-form">
                        <h4><?php echo $usuario_ha_valorado ? 'Actualiza tu valoración' : '¿Te ha sido útil este artículo?'; ?></h4>
                        <form method="POST" action="ver_articulo.php?id=<?php echo $id_articulo; ?>" class="rating-form">
                            <div class="form-group">
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="valoracion" value="<?php echo $i; ?>"
                                            <?php echo ($valoracion_usuario && $valoracion_usuario['valoracion'] == $i) ? 'checked' : ''; ?>>
                                        <label for="star<?php echo $i; ?>">
                                            <i class="fas fa-star" title="<?php echo $i; ?> estrella<?php echo $i > 1 ? 's' : ''; ?>"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <div class="rating-label">
                                    <small>Haz clic en las estrellas para puntuar</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comentario">Tu comentario (opcional):</label>
                                <textarea id="comentario" name="comentario" rows="3" placeholder="Comparte tu experiencia con este artículo..."><?php echo $valoracion_usuario ? htmlspecialchars($valoracion_usuario['comentario']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" name="valorar" class="btn btn-primary">
                                    <?php echo $usuario_ha_valorado ? 'Actualizar valoración' : 'Enviar valoración'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="login-prompt">
                        <p><i class="fas fa-info-circle"></i> <a href="login.php">Inicia sesión</a> para valorar este artículo.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Mostrar comentarios de otros usuarios -->
                <?php
                try {
                    $stmt = $db->prepare('
                        SELECT v.*, u.nombre as nombre_usuario 
                        FROM valoraciones v 
                        JOIN usuarios u ON v.id_usuario = u.id_usuario 
                        WHERE v.id_conocimiento = ? AND v.comentario IS NOT NULL AND v.comentario != ""
                        ORDER BY v.fecha_valoracion DESC
                    ');
                    $stmt->execute([$id_articulo]);
                    $comentarios = $stmt->fetchAll();
                    
                    if (count($comentarios) > 0):
                ?>
                    <div class="comment-list">
                        <h4>Comentarios de usuarios</h4>
                        
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="comment-item">
                                <div class="comment-rating">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $comentario['valoracion']) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="comment-content">
                                    <div class="comment-author"><?php echo htmlspecialchars($comentario['nombre_usuario']); ?></div>
                                    <div class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comentario['fecha_valoracion'])); ?></div>
                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php 
                    endif;
                } catch (PDOException $e) {
                    // Silenciar errores en producción
                }
                ?>
            </div>
        </div>
        
        <!-- Sidebar con artículos relacionados mejorada -->
        <div class="article-sidebar">
            <?php if (!empty($articulos_relacionados)): ?>
            <div class="related-articles">
                <h3><i class="fas fa-link"></i> Artículos relacionados</h3>
                <ul>
                    <?php foreach($articulos_relacionados as $rel): ?>
                        <li>
                            <a href="ver_articulo.php?id=<?php echo $rel['id_conocimiento']; ?>">
                                <?php echo htmlspecialchars($rel['titulo']); ?>
                            </a>
                            <div class="related-article-meta">
                                <span class="category"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($rel['categoria']); ?></span>
                                <?php if (isset($rel['visitas'])): ?>
                                <span class="views"><i class="fas fa-eye"></i> <?php echo $rel['visitas']; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($rel['resumen'])): ?>
                            <div class="related-article-summary">
                                <?php 
                                    $resumen = strlen($rel['resumen']) > 100 ? substr($rel['resumen'], 0, 100) . '...' : $rel['resumen'];
                                    echo htmlspecialchars($resumen); 
                                ?>
                            </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="other-categories">
                <h3>Otras categorías</h3>
                <?php
                try {
                    $stmt = $db->prepare('
                        SELECT categoria, COUNT(*) as num_articulos
                        FROM conocimientos
                        GROUP BY categoria
                        ORDER BY num_articulos DESC
                        LIMIT 10
                    ');
                    $stmt->execute();
                    $categorias = $stmt->fetchAll();
                    
                    echo '<ul class="category-list">';
                    foreach($categorias as $cat) {
                        echo '<li>';
                        echo '<a href="conocimientos.php?categoria=' . urlencode($cat['categoria']) . '">';
                        echo htmlspecialchars($cat['categoria']);
                        echo '</a>';
                        echo '<span class="article-count">(' . $cat['num_articulos'] . ')</span>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } catch (PDOException $e) {
                    echo '<p>Error al cargar categorías.</p>';
                }
                ?>
            </div>
            
            <div class="popular-tags">
                <h3>Etiquetas populares</h3>
                <?php
                try {
                    $stmt = $db->query('
                        SELECT etiquetas FROM conocimientos
                        WHERE etiquetas IS NOT NULL AND etiquetas != ""
                    ');
                    
                    $tags = [];
                    while ($row = $stmt->fetch()) {
                        if (!empty($row['etiquetas'])) {
                            $article_tags = explode(',', $row['etiquetas']);
                            foreach ($article_tags as $tag) {
                                $tag = trim($tag);
                                if (!empty($tag)) {
                                    if (isset($tags[$tag])) {
                                        $tags[$tag]++;
                                    } else {
                                        $tags[$tag] = 1;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Sort by count descending and take top 15
                    arsort($tags);
                    $tags = array_slice($tags, 0, 15, true);
                    
                    echo '<div class="tag-cloud">';
                    foreach ($tags as $tag => $count) {
                        echo '<a href="buscar.php?q=' . urlencode($tag) . '&tipo=conocimientos" class="tag" 
                              style="font-size: ' . (100 + ($count * 10)) . '%;">';
                        echo htmlspecialchars($tag) . ' (' . $count . ')';
                        echo '</a>';
                    }
                    echo '</div>';
                } catch (PDOException $e) {
                    echo '<p>Error al cargar etiquetas.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</section>

<?php
// Incluir footer
include 'includes/footer.php';
?>
