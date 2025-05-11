<?php
/**
 * Página para ver un ticket específico
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'Debes iniciar sesión para ver tickets.';
    $_SESSION['mensaje_tipo'] = 'warning';
    header('Location: login.php');
    exit;
}

// Verificar si se proporcionó un ID de ticket
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de ticket no válido.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: tickets.php');
    exit;
}

$id_ticket = (int)$_GET['id'];

try {
    $db = getDB();
    
    // Obtener información del ticket
    $stmt = $db->prepare('
        SELECT t.*, u.nombre as nombre_usuario, u.correo as correo_usuario,
               (SELECT nombre FROM usuarios WHERE id_usuario = t.id_asignado) as asignado_a
        FROM tickets t
        JOIN usuarios u ON t.id_usuario = u.id_usuario
        WHERE t.id_ticket = ?
    ');
    $stmt->execute([$id_ticket]);
    $ticket = $stmt->fetch();
    
    // Verificar si el ticket existe
    if (!$ticket) {
        $_SESSION['mensaje'] = 'El ticket solicitado no existe.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: tickets.php');
        exit;
    }
    
    // Verificar si el usuario tiene permiso para ver este ticket
    // (debe ser el propietario, un agente de soporte o un administrador)
    if ($ticket['id_usuario'] != $_SESSION['usuario_id'] && 
        $_SESSION['rol'] !== 'administrador' && 
        $_SESSION['rol'] !== 'soporte') {
        $_SESSION['mensaje'] = 'No tienes permiso para ver este ticket.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: tickets.php');
        exit;
    }
    
    // Obtener historial de acciones
    $stmt = $db->prepare('
        SELECT a.*, u.nombre
        FROM acciones a
        JOIN usuarios u ON a.id_usuario = u.id_usuario
        WHERE a.id_ticket = ?
        ORDER BY a.fecha_accion ASC
    ');
    $stmt->execute([$id_ticket]);
    $acciones = $stmt->fetchAll();
    
    // Obtener archivos adjuntos
    $stmt = $db->prepare('
        SELECT *
        FROM archivos
        WHERE id_ticket = ?
        ORDER BY fecha_subida ASC
    ');
    $stmt->execute([$id_ticket]);
    $archivos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar el ticket: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: tickets.php');
    exit;
}

// Procesar el formulario de actualización cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si es una actualización de estado
    if (isset($_POST['nuevo_estado']) && !empty($_POST['nuevo_estado'])) {
        $nuevo_estado = sanitizar($_POST['nuevo_estado']);
        $comentario = sanitizar($_POST['comentario'] ?? '');
        
        try {
            $db->beginTransaction();
            
            // Actualizar estado del ticket
            $stmt = $db->prepare('UPDATE tickets SET estado = ? WHERE id_ticket = ?');
            $stmt->execute([$nuevo_estado, $id_ticket]);
            
            // Registrar la acción
            $mensaje = "Estado cambiado a '$nuevo_estado'";
            if (!empty($comentario)) {
                $mensaje .= ". Comentario: $comentario";
            }
            
            $stmt = $db->prepare('INSERT INTO acciones (id_ticket, id_usuario, descripcion) VALUES (?, ?, ?)');
            $stmt->execute([$id_ticket, $_SESSION['usuario_id'], $mensaje]);
            
            $db->commit();
            
            $_SESSION['mensaje'] = 'Estado del ticket actualizado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Actualizar la página para mostrar los cambios
            header('Location: ver_ticket.php?id=' . $id_ticket);
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['mensaje'] = 'Error al actualizar el ticket: ' . $e->getMessage();
            $_SESSION['mensaje_tipo'] = 'error';
        }
    }
    
    // Verificar si es una respuesta al ticket
    if (isset($_POST['respuesta']) && !empty($_POST['respuesta'])) {
        $respuesta = sanitizar($_POST['respuesta']);
        
        try {
            $db->beginTransaction();
            
            // Registrar la respuesta como una acción
            $stmt = $db->prepare('INSERT INTO acciones (id_ticket, id_usuario, descripcion) VALUES (?, ?, ?)');
            $stmt->execute([$id_ticket, $_SESSION['usuario_id'], 'Respuesta: ' . $respuesta]);
            
            // Si el ticket está cerrado, reabrirlo
            if ($ticket['estado'] === 'cerrado') {
                $stmt = $db->prepare('UPDATE tickets SET estado = ? WHERE id_ticket = ?');
                $stmt->execute(['abierto', $id_ticket]);
            }
            
            // Procesar archivo adjunto si existe
            if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
                $archivo_nombre = $_FILES['adjunto']['name'];
                $archivo_tmp = $_FILES['adjunto']['tmp_name'];
                $archivo_tipo = $_FILES['adjunto']['type'];
                
                // Crear directorio si no existe
                $dir_adjuntos = 'uploads/tickets/' . $id_ticket;
                if (!file_exists($dir_adjuntos)) {
                    mkdir($dir_adjuntos, 0777, true);
                }
                
                // Generar nombre único para el archivo
                $archivo_extension = pathinfo($archivo_nombre, PATHINFO_EXTENSION);
                $archivo_nuevo_nombre = uniqid() . '.' . $archivo_extension;
                $archivo_ruta = $dir_adjuntos . '/' . $archivo_nuevo_nombre;
                
                // Mover el archivo
                if (move_uploaded_file($archivo_tmp, $archivo_ruta)) {
                    // Guardar información del archivo en la base de datos
                    $stmt = $db->prepare('INSERT INTO archivos (id_ticket, nombre_archivo, ruta_archivo, tipo_archivo) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$id_ticket, $archivo_nombre, $archivo_ruta, $archivo_tipo]);
                } else {
                    throw new Exception('Error al subir el archivo adjunto.');
                }
            }
            
            $db->commit();
            
            $_SESSION['mensaje'] = 'Respuesta enviada correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Actualizar la página para mostrar los cambios
            header('Location: ver_ticket.php?id=' . $id_ticket);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['mensaje'] = 'Error al enviar la respuesta: ' . $e->getMessage();
            $_SESSION['mensaje_tipo'] = 'error';
        }
    }
}

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

// Establecer título de página
$page_title = 'Ticket #' . $id_ticket;

// Incluir header
include 'includes/header.php';
?>

<div class="ticket-container">
    <div class="ticket-header">
        <div class="ticket-title">
            <h1><?php echo htmlspecialchars($ticket['titulo']); ?></h1>
            <span class="ticket-id">Ticket #<?php echo $ticket['id_ticket']; ?></span>
        </div>
        
        <div class="ticket-meta">
            <div class="ticket-meta-item">
                <span class="label">Estado:</span>
                <span class="ticket-status <?php echo $estado_class; ?>"><?php echo htmlspecialchars($ticket['estado']); ?></span>
            </div>
            <div class="ticket-meta-item">
                <span class="label">Prioridad:</span>
                <span class="<?php echo $prioridad_class; ?>"><?php echo htmlspecialchars($ticket['prioridad']); ?></span>
            </div>
            <div class="ticket-meta-item">
                <span class="label">Categoría:</span>
                <?php echo htmlspecialchars($ticket['categoria']); ?>
            </div>
            <div class="ticket-meta-item">
                <span class="label">Creado:</span>
                <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
            </div>
            <div class="ticket-meta-item">
                <span class="label">Por:</span>
                <?php echo htmlspecialchars($ticket['nombre_usuario']); ?> (<?php echo htmlspecialchars($ticket['correo_usuario']); ?>)
            </div>
            <div class="ticket-meta-item">
                <span class="label">Asignado a:</span>
                <?php echo $ticket['asignado_a'] ? htmlspecialchars($ticket['asignado_a']) : 'Sin asignar'; ?>
            </div>
        </div>
    </div>
    
    <div class="ticket-body">
        <div class="ticket-section">
            <h3>Descripción</h3>
            <div class="ticket-description">
                <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?>
            </div>
        </div>
        
        <?php if (!empty($archivos)): ?>
        <div class="ticket-section">
            <h3>Archivos adjuntos</h3>
            <ul class="attachments-list">
                <?php foreach ($archivos as $archivo): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($archivo['ruta_archivo']); ?>" target="_blank">
                        <?php echo htmlspecialchars($archivo['nombre_archivo']); ?>
                    </a>
                    <span class="file-info">
                        (<?php echo date('d/m/Y H:i', strtotime($archivo['fecha_subida'])); ?>)
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="ticket-section">
            <h3>Historial</h3>
            <div class="ticket-history">
                <?php if (!empty($acciones)): ?>
                <ul class="history-list">
                    <?php foreach ($acciones as $accion): ?>
                    <li>
                        <div class="history-meta">
                            <span class="history-date"><?php echo date('d/m/Y H:i', strtotime($accion['fecha_accion'])); ?></span>
                            <span class="history-user"><?php echo htmlspecialchars($accion['nombre']); ?></span>
                        </div>
                        <div class="history-content">
                            <?php echo nl2br(htmlspecialchars($accion['descripcion'])); ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p>No hay actividad registrada para este ticket.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($ticket['estado'] !== 'cerrado' || $_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte'): ?>
        <div class="ticket-section">
            <h3>Responder</h3>
            <form method="POST" action="ver_ticket.php?id=<?php echo $id_ticket; ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <textarea name="respuesta" placeholder="Escribe tu respuesta aquí..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="adjunto">Adjuntar archivo (opcional)</label>
                    <input type="file" id="adjunto" name="adjunto">
                </div>
                
                <button type="submit" class="btn">Enviar respuesta</button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte' || $ticket['id_usuario'] === $_SESSION['usuario_id']): ?>
        <div class="ticket-section">
            <h3>Cambiar estado</h3>
            <form method="POST" action="ver_ticket.php?id=<?php echo $id_ticket; ?>" class="status-form">
                <div class="form-group">
                    <label for="nuevo_estado">Nuevo estado:</label>
                    <select id="nuevo_estado" name="nuevo_estado" required>
                        <option value="">Seleccionar...</option>
                        <option value="abierto" <?php echo $ticket['estado'] === 'abierto' ? 'selected' : ''; ?>>Abierto</option>
                        <option value="en proceso" <?php echo $ticket['estado'] === 'en proceso' ? 'selected' : ''; ?>>En proceso</option>
                        <option value="cerrado" <?php echo $ticket['estado'] === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="comentario">Comentario (opcional):</label>
                    <textarea id="comentario" name="comentario"></textarea>
                </div>
                
                <button type="submit" class="btn">Actualizar estado</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="ticket-actions">
        <a href="tickets.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a la lista
        </a>
        
        <?php if ($ticket['id_usuario'] === $_SESSION['usuario_id'] && $ticket['estado'] !== 'cerrado'): ?>
        <a href="editar_ticket.php?id=<?php echo $id_ticket; ?>" class="btn">
            <i class="fas fa-edit"></i> Editar ticket
        </a>
        <?php endif; ?>
        
        <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte'): ?>
        <a href="asignar_ticket.php?id=<?php echo $id_ticket; ?>" class="btn">
            <i class="fas fa-user-tag"></i> Asignar ticket
        </a>
        <?php endif; ?>
        
        <?php if ($ticket['id_usuario'] === $_SESSION['usuario_id'] || $_SESSION['rol'] === 'administrador'): ?>
        <a href="eliminar_ticket.php?id=<?php echo $id_ticket; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este ticket?');">
            <i class="fas fa-trash"></i> Eliminar ticket
        </a>
        <?php endif; ?>
    </div>
</div>

<style>
/* Estilos específicos para la página de ver ticket */
.ticket-container {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.ticket-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.ticket-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.ticket-title h1 {
    font-size: 1.8rem;
    margin: 0;
}

.ticket-id {
    font-size: 1.2rem;
    color: #888;
}

.ticket-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.ticket-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.ticket-meta-item .label {
    font-weight: bold;
    color: #555;
}

.ticket-body {
    padding: 20px;
}

.ticket-section {
    margin-bottom: 30px;
}

.ticket-section h3 {
    font-size: 1.2rem;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.ticket-description {
    line-height: 1.6;
    white-space: pre-wrap;
}

.attachments-list {
    list-style: none;
    padding: 0;
}

.attachments-list li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.attachments-list a {
    color: #0099ff;
    text-decoration: none;
    margin-right: 10px;
}

.attachments-list a:hover {
    text-decoration: underline;
}

.file-info {
    color: #888;
    font-size: 0.9rem;
}

.history-list {
    list-style: none;
    padding: 0;
}

.history-list li {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.history-list li:last-child {
    border-bottom: none;
}

.history-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 0.9rem;
    color: #888;
}

.history-content {
    line-height: 1.5;
    white-space: pre-wrap;
}

.ticket-actions {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.status-form {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-end;
}
</style>

<?php
// Incluir footer
include 'includes/footer.php';
?>
