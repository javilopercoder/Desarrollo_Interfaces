<?php
/**
 * Página para asignar un ticket a un agente de soporte
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Verificar si el usuario está logueado y tiene los permisos adecuados
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte')) {
    $_SESSION['mensaje'] = 'No tienes permiso para asignar tickets.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: tickets.php');
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
    $stmt = $db->prepare('SELECT * FROM tickets WHERE id_ticket = ?');
    $stmt->execute([$id_ticket]);
    $ticket = $stmt->fetch();
    
    // Verificar si el ticket existe
    if (!$ticket) {
        $_SESSION['mensaje'] = 'El ticket solicitado no existe.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: tickets.php');
        exit;
    }
    
    // Obtener lista de agentes de soporte
    $stmt = $db->prepare("SELECT id_usuario, nombre FROM usuarios WHERE rol IN ('administrador', 'soporte') ORDER BY nombre");
    $stmt->execute();
    $agentes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar el ticket: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: tickets.php');
    exit;
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_asignado = isset($_POST['id_asignado']) ? (int)$_POST['id_asignado'] : null;
    $comentario = sanitizar($_POST['comentario'] ?? '');
    
    try {
        $db = getDB();
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Actualizar la asignación del ticket
        $stmt = $db->prepare('UPDATE tickets SET id_asignado = ? WHERE id_ticket = ?');
        $stmt->execute([$id_asignado ?: null, $id_ticket]);
        
        // Si se cambió a "En proceso" el estado del ticket
        if (isset($_POST['actualizar_estado']) && $_POST['actualizar_estado'] === '1') {
            $stmt = $db->prepare('UPDATE tickets SET estado = ? WHERE id_ticket = ?');
            $stmt->execute(['en proceso', $id_ticket]);
        }
        
        // Obtener el nombre del agente asignado
        $nombre_agente = 'Nadie';
        if ($id_asignado) {
            $stmt = $db->prepare('SELECT nombre FROM usuarios WHERE id_usuario = ?');
            $stmt->execute([$id_asignado]);
            $agente = $stmt->fetch();
            if ($agente) {
                $nombre_agente = $agente['nombre'];
            }
        }
        
        // Registrar la acción en el historial
        $descripcion = "Ticket asignado a: $nombre_agente";
        if (!empty($comentario)) {
            $descripcion .= ". Comentario: $comentario";
        }
        
        $stmt = $db->prepare('INSERT INTO acciones (id_ticket, id_usuario, descripcion) VALUES (?, ?, ?)');
        $stmt->execute([$id_ticket, $_SESSION['usuario_id'], $descripcion]);
        
        // Confirmar transacción
        $db->commit();
        
        // Mensaje de éxito
        $_SESSION['mensaje'] = 'Ticket asignado correctamente.';
        $_SESSION['mensaje_tipo'] = 'success';
        
        // Redirigir a la página de ver ticket
        header('Location: ver_ticket.php?id=' . $id_ticket);
        exit;
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        $db->rollBack();
        $_SESSION['mensaje'] = 'Error al asignar el ticket: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: asignar_ticket.php?id=' . $id_ticket);
        exit;
    }
}

// Establecer título de página
$page_title = 'Asignar Ticket #' . $id_ticket;

// Incluir header
include 'includes/header.php';
?>

<section>
    <div class="form-container">
        <h2 class="form-title">Asignar Ticket #<?php echo $id_ticket; ?></h2>
        
        <div class="ticket-summary">
            <h3><?php echo htmlspecialchars($ticket['titulo']); ?></h3>
            <div class="ticket-meta">
                <div>
                    <strong>Estado:</strong> <?php echo htmlspecialchars($ticket['estado']); ?>
                </div>
                <div>
                    <strong>Prioridad:</strong> <?php echo htmlspecialchars($ticket['prioridad']); ?>
                </div>
                <div>
                    <strong>Categoría:</strong> <?php echo htmlspecialchars($ticket['categoria']); ?>
                </div>
            </div>
        </div>
        
        <form method="POST" action="asignar_ticket.php?id=<?php echo $id_ticket; ?>">
            <div class="form-group">
                <label for="id_asignado">Asignar a:</label>
                <select id="id_asignado" name="id_asignado">
                    <option value="">Sin asignar</option>
                    <?php foreach ($agentes as $agente): ?>
                        <option value="<?php echo $agente['id_usuario']; ?>" <?php echo $ticket['id_asignado'] == $agente['id_usuario'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($agente['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="actualizar_estado" value="1" <?php echo $ticket['estado'] === 'abierto' ? 'checked' : ''; ?>>
                    Cambiar estado a "En proceso"
                </label>
            </div>
            
            <div class="form-group">
                <label for="comentario">Comentario (opcional):</label>
                <textarea id="comentario" name="comentario" rows="3"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Guardar asignación</button>
                <a href="ver_ticket.php?id=<?php echo $id_ticket; ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</section>

<style>
.ticket-summary {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 4px solid #0099ff;
}

.ticket-summary h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.ticket-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 0.9rem;
    color: #666;
}
</style>

<?php
// Incluir footer
include 'includes/footer.php';
?>
