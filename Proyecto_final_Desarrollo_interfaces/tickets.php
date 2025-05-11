<?php
/**
 * Página para ver tickets del usuario
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'Debes iniciar sesión para ver tus tickets.';
    $_SESSION['mensaje_tipo'] = 'warning';
    header('Location: login.php');
    exit;
}

// Obtener filtros
$estado = isset($_GET['estado']) ? sanitizar($_GET['estado']) : '';
$prioridad = isset($_GET['prioridad']) ? sanitizar($_GET['prioridad']) : '';
$categoria = isset($_GET['categoria']) ? sanitizar($_GET['categoria']) : '';

// Consulta base
$sql = 'SELECT t.*, u.nombre as nombre_usuario, 
               (SELECT nombre FROM usuarios WHERE id_usuario = t.id_asignado) as asignado_a
        FROM tickets t
        JOIN usuarios u ON t.id_usuario = u.id_usuario
        WHERE 1=1';
$params = [];

// Si no es administrador o soporte, solo mostrar los tickets del usuario
if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte') {
    $sql .= ' AND t.id_usuario = ?';
    $params[] = $_SESSION['usuario_id'];
}

// Aplicar filtros
if (!empty($estado)) {
    $sql .= ' AND t.estado = ?';
    $params[] = $estado;
}

if (!empty($prioridad)) {
    $sql .= ' AND t.prioridad = ?';
    $params[] = $prioridad;
}

if (!empty($categoria)) {
    $sql .= ' AND t.categoria = ?';
    $params[] = $categoria;
}

// Ordenar por fecha de creación (más recientes primero)
$sql .= ' ORDER BY t.fecha_creacion DESC';

// Establecer título de página
$page_title = 'Mis Tickets';

// Incluir header
include 'includes/header.php';
?>

<h1 class="form-title">
    <?php echo ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte') ? 'Todos los tickets' : 'Mis tickets'; ?>
</h1>

<div class="filters">
    <form action="tickets.php" method="GET" class="filter-form">
        <div class="filter-group">
            <label for="estado">Estado:</label>
            <select id="estado" name="estado" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="abierto" <?php echo $estado === 'abierto' ? 'selected' : ''; ?>>Abiertos</option>
                <option value="en proceso" <?php echo $estado === 'en proceso' ? 'selected' : ''; ?>>En proceso</option>
                <option value="cerrado" <?php echo $estado === 'cerrado' ? 'selected' : ''; ?>>Cerrados</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="prioridad">Prioridad:</label>
            <select id="prioridad" name="prioridad" onchange="this.form.submit()">
                <option value="">Todas</option>
                <option value="alta" <?php echo $prioridad === 'alta' ? 'selected' : ''; ?>>Alta</option>
                <option value="media" <?php echo $prioridad === 'media' ? 'selected' : ''; ?>>Media</option>
                <option value="baja" <?php echo $prioridad === 'baja' ? 'selected' : ''; ?>>Baja</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="categoria">Categoría:</label>
            <select id="categoria" name="categoria" onchange="this.form.submit()">
                <option value="">Todas</option>
                <option value="software" <?php echo $categoria === 'software' ? 'selected' : ''; ?>>Software</option>
                <option value="hardware" <?php echo $categoria === 'hardware' ? 'selected' : ''; ?>>Hardware</option>
                <option value="conexion" <?php echo $categoria === 'conexion' ? 'selected' : ''; ?>>Conexión</option>
            </select>
        </div>
        
        <?php if (!empty($estado) || !empty($prioridad) || !empty($categoria)): ?>
        <div class="filter-group">
            <a href="tickets.php" class="btn btn-secondary">Limpiar filtros</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="actions mb-3">
    <a href="nuevo_ticket.php" class="btn">
        <i class="fas fa-plus"></i> Nuevo ticket
    </a>
    
    <?php if ($_SESSION['rol'] === 'administrador'): ?>
    <a href="admin/reportes.php" class="btn btn-secondary">
        <i class="fas fa-chart-bar"></i> Ver reportes
    </a>
    
    <a href="#" class="btn btn-secondary" id="exportar-csv">
        <i class="fas fa-file-csv"></i> Exportar a CSV
    </a>
    <?php endif; ?>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Categoría</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th>Fecha</th>
                <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte'): ?>
                <th>Creado por</th>
                <th>Asignado a</th>
                <?php endif; ?>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            try {
                $db = getDB();
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $tickets = $stmt->fetchAll();
                
                if (count($tickets) > 0):
                    foreach ($tickets as $ticket):
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
            ?>
            <tr>
                <td><?php echo $ticket['id_ticket']; ?></td>
                <td>
                    <a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>">
                        <?php echo htmlspecialchars($ticket['titulo']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($ticket['categoria']); ?></td>
                <td class="<?php echo $prioridad_class; ?>"><?php echo htmlspecialchars($ticket['prioridad']); ?></td>
                <td><span class="ticket-status <?php echo $estado_class; ?>"><?php echo htmlspecialchars($ticket['estado']); ?></span></td>
                <td><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></td>
                
                <?php if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte'): ?>
                <td><?php echo htmlspecialchars($ticket['nombre_usuario']); ?></td>
                <td><?php echo $ticket['asignado_a'] ? htmlspecialchars($ticket['asignado_a']) : 'Sin asignar'; ?></td>
                <?php endif; ?>
                
                <td>
                    <a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </a>
                    
                    <?php if ($ticket['id_usuario'] == $_SESSION['usuario_id'] && $ticket['estado'] !== 'cerrado'): ?>
                    <a href="editar_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" title="Editar ticket">
                        <i class="fas fa-edit"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['rol'] === 'administrador' || ($_SESSION['rol'] === 'soporte' && $ticket['estado'] !== 'cerrado')): ?>
                    <a href="asignar_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" title="Asignar ticket">
                        <i class="fas fa-user-tag"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($ticket['id_usuario'] == $_SESSION['usuario_id'] || $_SESSION['rol'] === 'administrador'): ?>
                    <a href="eliminar_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" title="Eliminar ticket" 
                       onclick="return confirm('¿Estás seguro de que deseas eliminar este ticket?');">
                        <i class="fas fa-trash"></i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
                    endforeach;
                else:
            ?>
            <tr>
                <td colspan="<?php echo ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'soporte') ? '9' : '7'; ?>" class="text-center">
                    No tiene tickets <?php echo !empty($estado) ? 'en estado ' . $estado : ''; ?>
                </td>
            </tr>
            <?php
                endif;
            } catch (PDOException $e) {
                echo '<tr><td colspan="9" class="text-center">Error al cargar los tickets: ' . $e->getMessage() . '</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<script>
// Función para exportar a CSV (para administradores)
document.addEventListener('DOMContentLoaded', function() {
    const exportarBtn = document.getElementById('exportar-csv');
    if (exportarBtn) {
        exportarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Obtener datos de la tabla
            const table = document.querySelector('table');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length - 1; j++) { // Excluir la columna de acciones
                    let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ').trim();
                    text = text.replace(/"/g, '""'); // Escapar comillas dobles
                    row.push('"' + text + '"');
                }
                
                csv.push(row.join(','));
            }
            
            // Descargar CSV
            const csvString = csv.join('\n');
            const filename = 'tickets_' + new Date().toISOString().slice(0, 10) + '.csv';
            
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            
            if (navigator.msSaveBlob) { // IE 10+
                navigator.msSaveBlob(blob, filename);
            } else {
                const link = document.createElement('a');
                
                if (link.download !== undefined) {
                    // Navegadores modernos
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', filename);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }
        });
    }
});
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
