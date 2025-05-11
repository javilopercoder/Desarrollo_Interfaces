<?php
/**
 * Panel de administración - Tickets sin asignar
 */

// Verificar rutas duplicadas
require_once '../includes/verificar_rutas.php';

// Incluir archivo de conexión
require_once '../includes/conexion.php';

// Verificar si el usuario está logueado y tiene permisos adecuados
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte')) {
    $_SESSION['mensaje'] = 'Acceso denegado. Debes ser administrador o agente de soporte para acceder a esta página.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ../index.php');
    exit;
}

// Procesar asignación automática si se solicita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignacion_automatica'])) {
    try {
        $db = getDB();
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Obtener todos los tickets sin asignar
        $stmt = $db->query('SELECT id_ticket FROM tickets WHERE id_asignado IS NULL AND estado != "cerrado"');
        $tickets_sin_asignar = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener los agentes disponibles (administradores y soporte)
        $stmt = $db->query('SELECT id_usuario FROM usuarios WHERE rol IN ("administrador", "soporte")');
        $agentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($agentes) === 0) {
            throw new Exception('No hay agentes disponibles para asignar tickets.');
        }
        
        // Contador para distribuir tickets entre agentes
        $agente_index = 0;
        $total_asignados = 0;
        
        // Asignar tickets de forma equilibrada
        foreach ($tickets_sin_asignar as $id_ticket) {
            $id_agente = $agentes[$agente_index];
            
            // Actualizar el ticket con el agente asignado
            $stmt = $db->prepare('UPDATE tickets SET id_asignado = ? WHERE id_ticket = ?');
            $stmt->execute([$id_agente, $id_ticket]);
            
            // Registrar la acción en el historial
            $stmt = $db->prepare('
                INSERT INTO acciones (id_ticket, id_usuario, descripcion) 
                VALUES (?, ?, ?)
            ');
            $stmt->execute([
                $id_ticket, 
                $_SESSION['usuario_id'], 
                'Asignación automática del ticket a un agente'
            ]);
            
            // Avanzar al siguiente agente (distribución cíclica)
            $agente_index = ($agente_index + 1) % count($agentes);
            $total_asignados++;
        }
        
        // Confirmar transacción
        $db->commit();
        
        $_SESSION['mensaje'] = "Se han asignado $total_asignados tickets automáticamente.";
        $_SESSION['mensaje_tipo'] = 'success';
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        $_SESSION['mensaje'] = 'Error al realizar la asignación automática: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'error';
    }
    
    // Redireccionar para evitar reenvío del formulario
    header('Location: tickets_sin_asignar.php');
    exit;
}

// Filtros
$prioridad = isset($_GET['prioridad']) ? sanitizar($_GET['prioridad']) : '';
$categoria = isset($_GET['categoria']) ? sanitizar($_GET['categoria']) : '';
$orden = isset($_GET['orden']) ? sanitizar($_GET['orden']) : 'fecha_desc';

try {
    $db = getDB();
    
    // Construir consulta SQL base
    $sql = '
        SELECT t.*, u.nombre as nombre_usuario
        FROM tickets t
        JOIN usuarios u ON t.id_usuario = u.id_usuario
        WHERE t.id_asignado IS NULL AND t.estado != "cerrado"
    ';
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($prioridad)) {
        $sql .= ' AND t.prioridad = ?';
        $params[] = $prioridad;
    }
    
    if (!empty($categoria)) {
        $sql .= ' AND t.categoria = ?';
        $params[] = $categoria;
    }
    
    // Aplicar orden
    switch ($orden) {
        case 'fecha_asc':
            $sql .= ' ORDER BY t.fecha_creacion ASC';
            break;
        case 'prioridad_desc':
            $sql .= ' ORDER BY 
                CASE t.prioridad 
                    WHEN "alta" THEN 1 
                    WHEN "media" THEN 2 
                    WHEN "baja" THEN 3 
                END, t.fecha_creacion ASC';
            break;
        case 'prioridad_asc':
            $sql .= ' ORDER BY 
                CASE t.prioridad 
                    WHEN "baja" THEN 1 
                    WHEN "media" THEN 2 
                    WHEN "alta" THEN 3 
                END, t.fecha_creacion ASC';
            break;
        default: // fecha_desc
            $sql .= ' ORDER BY t.fecha_creacion DESC';
            break;
    }
    
    // Ejecutar consulta
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
    
    // Contar tickets por prioridad
    $stmt = $db->query('
        SELECT prioridad, COUNT(*) as total 
        FROM tickets 
        WHERE id_asignado IS NULL AND estado != "cerrado"
        GROUP BY prioridad
    ');
    $tickets_por_prioridad = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Obtener estadísticas
    $total_tickets_sin_asignar = count($tickets);
    $tickets_alta_prioridad = $tickets_por_prioridad['alta'] ?? 0;
    
    // Obtener lista de agentes para asignación manual
    $stmt = $db->query("
        SELECT id_usuario, nombre, 
               (SELECT COUNT(*) FROM tickets WHERE id_asignado = u.id_usuario AND estado != 'cerrado') as tickets_activos
        FROM usuarios u
        WHERE rol IN ('administrador', 'soporte')
        ORDER BY tickets_activos ASC, nombre
    ");
    $agentes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar los tickets sin asignar: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    $tickets = [];
    $total_tickets_sin_asignar = 0;
    $tickets_alta_prioridad = 0;
    $agentes = [];
}

// Establecer título de página
$page_title = 'Tickets Sin Asignar';

// Incluir header
include '../includes/header.php';
?>

<div class="admin-header">
    <h1>Tickets Sin Asignar</h1>
    <div class="admin-actions">
        <?php if ($_SESSION['rol'] === 'administrador'): ?>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al panel
        </a>
        <?php else: ?>
        <a href="../index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al inicio
        </a>
        <?php endif; ?>
        
        <?php if ($total_tickets_sin_asignar > 0): ?>
        <form method="POST" action="tickets_sin_asignar.php" class="inline-form" onsubmit="return confirm('¿Estás seguro de que deseas asignar automáticamente todos los tickets sin asignar?');">
            <button type="submit" name="asignacion_automatica" value="1" class="btn">
                <i class="fas fa-magic"></i> Asignación automática
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-section">
    <div class="ticket-summary">
        <div class="summary-item">
            <div class="summary-value"><?php echo $total_tickets_sin_asignar; ?></div>
            <div class="summary-label">Total sin asignar</div>
        </div>
        
        <div class="summary-item priority-high">
            <div class="summary-value"><?php echo $tickets_alta_prioridad; ?></div>
            <div class="summary-label">Alta prioridad</div>
        </div>
        
        <div class="summary-item priority-medium">
            <div class="summary-value"><?php echo $tickets_por_prioridad['media'] ?? 0; ?></div>
            <div class="summary-label">Media prioridad</div>
        </div>
        
        <div class="summary-item priority-low">
            <div class="summary-value"><?php echo $tickets_por_prioridad['baja'] ?? 0; ?></div>
            <div class="summary-label">Baja prioridad</div>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <div class="filter-bar">
        <div class="filter-group">
            <label>Filtrar por:</label>
            
            <div class="filter-options">
                <select id="filtro-prioridad" onchange="aplicarFiltros()">
                    <option value="">Todas las prioridades</option>
                    <option value="alta" <?php echo $prioridad === 'alta' ? 'selected' : ''; ?>>Alta</option>
                    <option value="media" <?php echo $prioridad === 'media' ? 'selected' : ''; ?>>Media</option>
                    <option value="baja" <?php echo $prioridad === 'baja' ? 'selected' : ''; ?>>Baja</option>
                </select>
                
                <select id="filtro-categoria" onchange="aplicarFiltros()">
                    <option value="">Todas las categorías</option>
                    <option value="software" <?php echo $categoria === 'software' ? 'selected' : ''; ?>>Software</option>
                    <option value="hardware" <?php echo $categoria === 'hardware' ? 'selected' : ''; ?>>Hardware</option>
                    <option value="conexion" <?php echo $categoria === 'conexion' ? 'selected' : ''; ?>>Conexión</option>
                    <option value="otro" <?php echo $categoria === 'otro' ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>
        </div>
        
        <div class="filter-group">
            <label>Ordenar por:</label>
            
            <div class="filter-options">
                <select id="filtro-orden" onchange="aplicarFiltros()">
                    <option value="fecha_desc" <?php echo $orden === 'fecha_desc' ? 'selected' : ''; ?>>Más recientes primero</option>
                    <option value="fecha_asc" <?php echo $orden === 'fecha_asc' ? 'selected' : ''; ?>>Más antiguos primero</option>
                    <option value="prioridad_desc" <?php echo $orden === 'prioridad_desc' ? 'selected' : ''; ?>>Mayor prioridad primero</option>
                    <option value="prioridad_asc" <?php echo $orden === 'prioridad_asc' ? 'selected' : ''; ?>>Menor prioridad primero</option>
                </select>
            </div>
        </div>
    </div>
    
    <?php if (count($tickets) > 0): ?>
        <div class="tickets-list">
            <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-card priority-<?php echo $ticket['prioridad']; ?>">
                    <div class="ticket-header">
                        <h3 class="ticket-title">
                            <a href="../ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>">
                                <?php echo htmlspecialchars($ticket['titulo']); ?>
                            </a>
                        </h3>
                        <div class="ticket-id">#<?php echo $ticket['id_ticket']; ?></div>
                    </div>
                    
                    <div class="ticket-meta">
                        <div class="ticket-info">
                            <div class="ticket-user">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($ticket['nombre_usuario']); ?>
                            </div>
                            <div class="ticket-category">
                                <i class="fas fa-folder"></i> <?php echo ucfirst($ticket['categoria']); ?>
                            </div>
                            <div class="ticket-date">
                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                            </div>
                        </div>
                        
                        <div class="ticket-priority">
                            <span class="priority-badge priority-<?php echo $ticket['prioridad']; ?>">
                                <?php echo ucfirst($ticket['prioridad']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="ticket-description">
                        <?php 
                        // Mostrar un extracto de la descripción
                        $extracto = substr($ticket['descripcion'], 0, 150);
                        echo nl2br(htmlspecialchars($extracto)) . (strlen($ticket['descripcion']) > 150 ? '...' : '');
                        ?>
                    </div>
                    
                    <div class="ticket-actions">
                        <a href="../ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="btn btn-sm">
                            <i class="fas fa-eye"></i> Ver detalles
                        </a>
                        
                        <div class="dropdown">
                            <button class="btn btn-sm dropdown-toggle">
                                <i class="fas fa-user-plus"></i> Asignar a...
                            </button>
                            <div class="dropdown-content">
                                <?php foreach ($agentes as $agente): ?>
                                    <a href="../asignar_ticket.php?id=<?php echo $ticket['id_ticket']; ?>&agente=<?php echo $agente['id_usuario']; ?>">
                                        <?php echo htmlspecialchars($agente['nombre']); ?>
                                        <span class="badge"><?php echo $agente['tickets_activos']; ?> tickets activos</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>¡No hay tickets sin asignar!</h3>
            <p>Todos los tickets han sido asignados a un agente de soporte.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.ticket-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.summary-item {
    flex: 1;
    min-width: 120px;
    background-color: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
    border-top: 4px solid #0099ff;
}

.summary-item.priority-high {
    border-top-color: #f44336;
}

.summary-item.priority-medium {
    border-top-color: #ff9800;
}

.summary-item.priority-low {
    border-top-color: #4caf50;
}

.summary-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.summary-label {
    color: #666;
    font-size: 0.9rem;
}

.filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-options {
    display: flex;
    gap: 10px;
}

.filter-options select {
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.tickets-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.ticket-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 20px;
    border-left: 5px solid #0099ff;
}

.ticket-card.priority-alta {
    border-left-color: #f44336;
}

.ticket-card.priority-media {
    border-left-color: #ff9800;
}

.ticket-card.priority-baja {
    border-left-color: #4caf50;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.ticket-title {
    margin: 0;
    font-size: 1.2rem;
}

.ticket-title a {
    color: #333;
    text-decoration: none;
}

.ticket-title a:hover {
    color: #0099ff;
}

.ticket-id {
    font-size: 0.9rem;
    color: #666;
    font-weight: bold;
}

.ticket-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 0.9rem;
    color: #666;
}

.ticket-info {
    display: flex;
    gap: 15px;
}

.priority-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 0.75rem;
}

.priority-badge.priority-alta {
    background-color: rgba(244, 67, 54, 0.1);
    color: #f44336;
}

.priority-badge.priority-media {
    background-color: rgba(255, 152, 0, 0.1);
    color: #ff9800;
}

.priority-badge.priority-baja {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4caf50;
}

.ticket-description {
    margin-bottom: 15px;
    color: #333;
    font-size: 0.95rem;
    line-height: 1.5;
}

.ticket-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
}

.btn-sm {
    font-size: 0.85rem;
    padding: 6px 12px;
}

.inline-form {
    display: inline;
}

.dropdown {
    position: relative;
}

.dropdown-toggle {
    cursor: pointer;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    min-width: 200px;
    z-index: 1;
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    max-height: 300px;
    overflow-y: auto;
}

.dropdown-content a {
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #333;
    text-decoration: none;
    border-bottom: 1px solid #f0f0f0;
}

.dropdown-content a:last-child {
    border-bottom: none;
}

.dropdown-content a:hover {
    background-color: #f5f5f5;
}

.dropdown:hover .dropdown-content {
    display: block;
}

.badge {
    background-color: #f0f0f0;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.75rem;
    color: #666;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 50px 0;
    text-align: center;
}

.empty-icon {
    font-size: 4rem;
    color: #4caf50;
    margin-bottom: 20px;
}

.empty-state h3 {
    margin: 0 0 10px;
    color: #333;
}

.empty-state p {
    color: #666;
    max-width: 400px;
}

@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-options {
        flex-direction: column;
    }
    
    .tickets-list {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function aplicarFiltros() {
    const prioridad = document.getElementById('filtro-prioridad').value;
    const categoria = document.getElementById('filtro-categoria').value;
    const orden = document.getElementById('filtro-orden').value;
    
    let url = 'tickets_sin_asignar.php?';
    
    if (prioridad) url += `prioridad=${prioridad}&`;
    if (categoria) url += `categoria=${categoria}&`;
    if (orden) url += `orden=${orden}&`;
    
    // Eliminar el último & si existe
    if (url.endsWith('&')) {
        url = url.slice(0, -1);
    }
    
    window.location.href = url;
}
</script>

<?php
// Incluir footer
include '../includes/footer.php';
?>
