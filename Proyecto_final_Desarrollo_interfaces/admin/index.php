<?php
/**
 * Panel de administración - Página principal
 */

// Verificar rutas duplicadas
require_once '../includes/verificar_rutas.php';

// Incluir archivo de conexión
require_once '../includes/conexion.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    $_SESSION['mensaje'] = 'Acceso denegado. Debes ser administrador para acceder a esta página.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ../index.php');
    exit;
}

// Obtener estadísticas generales
try {
    $db = getDB();
    
    // Total de tickets
    $stmt = $db->query('SELECT COUNT(*) FROM tickets');
    $total_tickets = $stmt->fetchColumn();
    
    // Tickets por estado
    $stmt = $db->query('SELECT estado, COUNT(*) as total FROM tickets GROUP BY estado');
    $tickets_por_estado = $stmt->fetchAll();
    
    // Tickets por categoría
    $stmt = $db->query('SELECT categoria, COUNT(*) as total FROM tickets GROUP BY categoria');
    $tickets_por_categoria = $stmt->fetchAll();
    
    // Tickets por prioridad
    $stmt = $db->query('SELECT prioridad, COUNT(*) as total FROM tickets GROUP BY prioridad');
    $tickets_por_prioridad = $stmt->fetchAll();
    
    // Usuarios registrados
    $stmt = $db->query('SELECT COUNT(*) FROM usuarios');
    $total_usuarios = $stmt->fetchColumn();
    
    // Usuarios por rol
    $stmt = $db->query('SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol');
    $usuarios_por_rol = $stmt->fetchAll();
    
    // Tickets pendientes de asignar
    $stmt = $db->query('SELECT COUNT(*) FROM tickets WHERE id_asignado IS NULL AND estado != "cerrado"');
    $tickets_sin_asignar = $stmt->fetchColumn();
    
    // Tickets creados en los últimos 7 días
    $stmt = $db->query('SELECT COUNT(*) FROM tickets WHERE fecha_creacion >= date("now", "-7 days")');
    $tickets_recientes = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al obtener estadísticas: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
}

// Establecer título de página
$page_title = 'Panel de Administración';

// Incluir header personalizado para administración
include '../includes/header.php';
?>

<div class="admin-header">
    <h1>Panel de Administración</h1>
    <div class="admin-actions">
        <a href="../index.php" class="btn btn-secondary">
            <i class="fas fa-home"></i> Volver al inicio
        </a>
        <a href="usuarios.php" class="btn">
            <i class="fas fa-users"></i> Gestionar usuarios
        </a>
        <a href="reportes.php" class="btn">
            <i class="fas fa-chart-bar"></i> Reportes detallados
        </a>
    </div>
</div>

<div class="dashboard">
    <div class="dashboard-section">
        <h2>Resumen de tickets</h2>
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-value"><?php echo $total_tickets; ?></div>
                <div class="stat-label">Total de tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value">
                    <?php 
                        foreach ($tickets_por_estado as $estado) {
                            if ($estado['estado'] === 'abierto') {
                                echo $estado['total'];
                                break;
                            }
                        }
                    ?>
                </div>
                <div class="stat-label">Tickets abiertos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-value">
                    <?php 
                        foreach ($tickets_por_estado as $estado) {
                            if ($estado['estado'] === 'en proceso') {
                                echo $estado['total'];
                                break;
                            }
                        }
                    ?>
                </div>
                <div class="stat-label">Tickets en proceso</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value">
                    <?php 
                        foreach ($tickets_por_estado as $estado) {
                            if ($estado['estado'] === 'cerrado') {
                                echo $estado['total'];
                                break;
                            }
                        }
                    ?>
                </div>
                <div class="stat-label">Tickets cerrados</div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-row">
        <div class="dashboard-column">
            <div class="dashboard-section">
                <h2>Tickets por categoría</h2>
                <div class="chart-container">
                    <canvas id="categoriaChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="dashboard-column">
            <div class="dashboard-section">
                <h2>Tickets por prioridad</h2>
                <div class="chart-container">
                    <canvas id="prioridadChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h2>Información de usuarios</h2>
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $total_usuarios; ?></div>
                <div class="stat-label">Total de usuarios</div>
            </div>
            <?php foreach ($usuarios_por_rol as $rol): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tag"></i>
                </div>
                <div class="stat-value"><?php echo $rol['total']; ?></div>
                <div class="stat-label">
                    <?php
                        switch ($rol['rol']) {
                            case 'administrador':
                                echo 'Administradores';
                                break;
                            case 'soporte':
                                echo 'Agentes de soporte';
                                break;
                            case 'usuario':
                                echo 'Usuarios regulares';
                                break;
                            default:
                                echo ucfirst($rol['rol']) . 's';
                        }
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h2>Acciones rápidas</h2>
        <div class="quick-actions">
            <a href="../tickets.php?estado=abierto" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="quick-action-info">
                    <h3>Ver tickets abiertos</h3>
                    <p>Revisa las solicitudes pendientes</p>
                </div>
            </a>
            
            <a href="tickets_sin_asignar.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="quick-action-info">
                    <h3>Tickets sin asignar</h3>
                    <p><strong><?php echo $tickets_sin_asignar; ?></strong> tickets pendientes de asignación</p>
                </div>
            </a>
            
            <a href="reportes.php?periodo=7dias" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="quick-action-info">
                    <h3>Actividad reciente</h3>
                    <p><strong><?php echo $tickets_recientes; ?></strong> tickets en los últimos 7 días</p>
                </div>
            </a>
            
            <a href="nuevo_usuario.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="quick-action-info">
                    <h3>Crear usuario</h3>
                    <p>Añadir un nuevo usuario al sistema</p>
                </div>
            </a>
            
            <a href="estadisticas.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="quick-action-info">
                    <h3>Estadísticas de visitas</h3>
                    <p>Ver datos detallados de las visitas al sitio</p>
                </div>
            </a>
            
            <a href="mail_diagnostico.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div class="quick-action-info">
                    <h3>Diagnóstico de correo</h3>
                    <p>Verificar el estado del sistema de correo electrónico</p>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.admin-header h1 {
    margin: 0;
}

.admin-actions {
    display: flex;
    gap: 10px;
}

.dashboard-section {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 30px;
}

.dashboard-section h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 20px;
    color: #333;
}

.dashboard-row {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
}

.dashboard-column {
    flex: 1;
}

.dashboard-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.stat-card {
    flex: 1;
    min-width: 200px;
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2.5rem;
    color: #0099ff;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.stat-label {
    color: #666;
}

.chart-container {
    height: 300px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.quick-action-card {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.3s;
}

.quick-action-card:hover {
    background-color: #f0f0f0;
    transform: translateY(-5px);
}

.quick-action-icon {
    font-size: 2rem;
    color: #0099ff;
    margin-right: 20px;
}

.quick-action-info h3 {
    margin: 0 0 5px;
    color: #333;
}

.quick-action-info p {
    margin: 0;
    color: #666;
}

@media (max-width: 768px) {
    .dashboard-row {
        flex-direction: column;
    }
    
    .admin-header {
        flex-direction: column;
        text-align: center;
    }
    
    .admin-actions {
        margin-top: 15px;
        justify-content: center;
    }
    
    .dashboard-stats {
        flex-direction: column;
    }
}
</style>

<!-- Incluir Chart.js para gráficos -->
<script>
// Desactivar source maps para Chart.js
window.process = { env: { NODE_ENV: 'production' } };
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos para gráfico de categorías
    const categoriaCtx = document.getElementById('categoriaChart').getContext('2d');
    const categoriaChart = new Chart(categoriaCtx, {
        type: 'pie',
        data: {
            labels: [
                <?php 
                    foreach ($tickets_por_categoria as $categoria) {
                        echo "'" . $categoria['categoria'] . "',";
                    }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                        foreach ($tickets_por_categoria as $categoria) {
                            echo $categoria['total'] . ",";
                        }
                    ?>
                ],
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Datos para gráfico de prioridades
    const prioridadCtx = document.getElementById('prioridadChart').getContext('2d');
    const prioridadChart = new Chart(prioridadCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php 
                    foreach ($tickets_por_prioridad as $prioridad) {
                        echo "'" . ucfirst($prioridad['prioridad']) . "',";
                    }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                        foreach ($tickets_por_prioridad as $prioridad) {
                            echo $prioridad['total'] . ",";
                        }
                    ?>
                ],
                backgroundColor: [
                    '#FF6384',
                    '#FFCE56',
                    '#36A2EB'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>

<?php
// Incluir footer
include '../includes/footer.php';
?>
