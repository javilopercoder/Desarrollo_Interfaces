<?php
/**
 * Estadísticas del contador de visitas
 * Este script muestra información detallada sobre las visitas al sitio web
 */

// Requerir archivo de sesión
require_once '../includes/sesion.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    // Redirigir si no es admin
    header('Location: ../index.php');
    exit;
}

// Establecer título de página
$page_title = 'Estadísticas de Visitas';

// Rutas a los archivos del contador
$contador_file = __DIR__ . '/../data/contador.txt';
$contador_log = __DIR__ . '/../data/visitas.log';

// Funciones de utilidad
function getContadorTotal() {
    global $contador_file;
    return file_exists($contador_file) ? (int) file_get_contents($contador_file) : 0;
}

function getVisitasLog($limit = 100) {
    global $contador_log;
    
    $visitas = [];
    
    if (file_exists($contador_log)) {
        $log_lines = file($contador_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $log_lines = array_slice($log_lines, -$limit); // Obtener solo las últimas entradas
        
        foreach ($log_lines as $line) {
            $visita = json_decode($line, true);
            if ($visita) {
                $visitas[] = $visita;
            }
        }
    }
    
    return $visitas;
}

function getVisitasPorDia($visitas) {
    $por_dia = [];
    
    foreach ($visitas as $visita) {
        $fecha = substr($visita['date'], 0, 10); // obtener solo el YYYY-MM-DD
        
        if (!isset($por_dia[$fecha])) {
            $por_dia[$fecha] = 0;
        }
        
        if ($visita['status'] == 'NEW') {
            $por_dia[$fecha]++;
        }
    }
    
    return $por_dia;
}

function getNavegadores($visitas) {
    $navegadores = [];
    
    foreach ($visitas as $visita) {
        $user_agent = $visita['user_agent'];
        $browser = 'Desconocido';
        
        if (strpos($user_agent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($user_agent, 'Chrome') !== false && strpos($user_agent, 'Edg') === false && strpos($user_agent, 'Safari') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($user_agent, 'Edg') !== false) {
            $browser = 'Edge';
        } elseif (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Chrome') === false) {
            $browser = 'Safari';
        } elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident/') !== false) {
            $browser = 'Internet Explorer';
        } elseif (strpos($user_agent, 'Opera') !== false || strpos($user_agent, 'OPR/') !== false) {
            $browser = 'Opera';
        }
        
        if (!isset($navegadores[$browser])) {
            $navegadores[$browser] = 0;
        }
        
        $navegadores[$browser]++;
    }
    
    arsort($navegadores);
    return $navegadores;
}

function getDispositivos($visitas) {
    $dispositivos = [];
    
    foreach ($visitas as $visita) {
        $user_agent = $visita['user_agent'];
        $device = 'Escritorio';
        
        if (strpos($user_agent, 'Mobile') !== false || strpos($user_agent, 'Android') !== false) {
            $device = 'Móvil';
        } elseif (strpos($user_agent, 'Tablet') !== false || strpos($user_agent, 'iPad') !== false) {
            $device = 'Tablet';
        }
        
        if (!isset($dispositivos[$device])) {
            $dispositivos[$device] = 0;
        }
        
        $dispositivos[$device]++;
    }
    
    arsort($dispositivos);
    return $dispositivos;
}

// Obtener datos para las estadísticas
$total_visitas = getContadorTotal();
$visitas = getVisitasLog(1000); // Obtener las últimas 1000 visitas para estadísticas
$visitas_por_dia = getVisitasPorDia($visitas);
$navegadores = getNavegadores($visitas);
$dispositivos = getDispositivos($visitas);

// Incluir header
include '../includes/header.php';
?>

<section>
    <div class="breadcrumbs">
        <a href="../index.php"><i class="fas fa-home"></i> Inicio</a> <span class="separator">&raquo;</span>
        <a href="index.php"><i class="fas fa-user-shield"></i> Admin</a> <span class="separator">&raquo;</span>
        <span><i class="fas fa-chart-line"></i> Estadísticas de Visitas</span>
    </div>
    
    <h1 class="page-title">Estadísticas de Visitas</h1>
    
    <div class="stats-container">
        <div class="stats-card">
            <h2><i class="fas fa-eye"></i> Total de Visitas</h2>
            <div class="big-number"><?php echo number_format($total_visitas); ?></div>
            <p>Número total de visitantes únicos</p>
        </div>
        
        <div class="stats-card">
            <h2><i class="fas fa-calendar-alt"></i> Visitas por Día</h2>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $fechas = array_keys($visitas_por_dia);
                    rsort($fechas); // Ordenar por fecha descendente
                    
                    foreach (array_slice($fechas, 0, 7) as $fecha): 
                        $count = $visitas_por_dia[$fecha];
                    ?>
                    <tr>
                        <td><?php echo $fecha; ?></td>
                        <td><?php echo $count; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="stats-card">
            <h2><i class="fas fa-globe"></i> Navegadores</h2>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Navegador</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($navegadores as $navegador => $count): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($navegador); ?></td>
                        <td><?php echo $count; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="stats-card">
            <h2><i class="fas fa-mobile-alt"></i> Dispositivos</h2>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dispositivos as $dispositivo => $count): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($dispositivo); ?></td>
                        <td><?php echo $count; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="stats-card full-width">
            <h2><i class="fas fa-list"></i> Últimas Visitas</h2>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>IP</th>
                        <th>Página</th>
                        <th>Navegador</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Mostrar las últimas 20 visitas
                    $reversed_visitas = array_reverse(array_slice($visitas, -20));
                    foreach ($reversed_visitas as $visita): 
                        $user_agent = $visita['user_agent'];
                        $browser = 'Desconocido';
                        
                        if (strpos($user_agent, 'Firefox') !== false) {
                            $browser = 'Firefox';
                        } elseif (strpos($user_agent, 'Chrome') !== false && strpos($user_agent, 'Edge') === false) {
                            $browser = 'Chrome';
                        } elseif (strpos($user_agent, 'Edg') !== false) {
                            $browser = 'Edge';
                        } elseif (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Chrome') === false) {
                            $browser = 'Safari';
                        }
                        
                        $device = 'Escritorio';
                        if (strpos($user_agent, 'Mobile') !== false) {
                            $device = 'Móvil';
                        } elseif (strpos($user_agent, 'Tablet') !== false || strpos($user_agent, 'iPad') !== false) {
                            $device = 'Tablet';
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($visita['date']); ?></td>
                        <td><?php echo htmlspecialchars($visita['ip']); ?></td>
                        <td><?php echo htmlspecialchars($visita['page']); ?></td>
                        <td><?php echo htmlspecialchars($browser); ?></td>
                        <td><?php echo htmlspecialchars($device); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="table-note">Mostrando las 20 visitas más recientes</p>
        </div>
    </div>
</section>

<style>
.stats-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin: 20px 0;
}

.stats-card {
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    padding: 20px;
    flex: 1 1 calc(50% - 20px);
    min-width: 300px;
}

.stats-card.full-width {
    flex: 1 1 100%;
}

.stats-card h2 {
    color: #2c3e50;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-top: 0;
    font-size: 1.2em;
}

.big-number {
    font-size: 3em;
    font-weight: bold;
    color: #3498db;
    text-align: center;
    margin: 20px 0;
}

.stats-table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
}

.stats-table th,
.stats-table td {
    padding: 8px 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.stats-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.table-note {
    text-align: center;
    font-style: italic;
    color: #7f8c8d;
    margin-top: 10px;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .stats-card {
        flex: 1 1 100%;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
