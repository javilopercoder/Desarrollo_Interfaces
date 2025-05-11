<?php
/**
 * Panel de administración - Sistema de reportes
 */

// Verificar rutas duplicadas
require_once '../includes/verificar_rutas.php';

// Incluir archivo de conexión
require_once '../includes/conexion.php';

// Verificar si el usuario está logueado y tiene permisos
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte')) {
    $_SESSION['mensaje'] = 'Acceso denegado. Debes ser administrador o agente de soporte para acceder a esta página.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ../index.php');
    exit;
}

// Parámetros de filtrado
$periodo = isset($_GET['periodo']) ? sanitizar($_GET['periodo']) : 'mes';
$categoria = isset($_GET['categoria']) ? sanitizar($_GET['categoria']) : '';
$agente = isset($_GET['agente']) ? (int)$_GET['agente'] : 0;
$fecha_inicio = isset($_GET['fecha_inicio']) ? sanitizar($_GET['fecha_inicio']) : '';
$fecha_fin = isset($_GET['fecha_fin']) ? sanitizar($_GET['fecha_fin']) : '';

// Si no se especifican fechas, establecer por defecto según el periodo
if (empty($fecha_inicio) || empty($fecha_fin)) {
    $hoy = date('Y-m-d');
    
    switch ($periodo) {
        case '7dias':
            $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
            $fecha_fin = $hoy;
            break;
        case '30dias':
            $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
            $fecha_fin = $hoy;
            break;
        case 'mes':
            $fecha_inicio = date('Y-m-01'); // Primer día del mes actual
            $fecha_fin = date('Y-m-t'); // Último día del mes actual
            break;
        case 'trimestre':
            $mes_actual = date('n');
            $trimestre_inicio = floor(($mes_actual - 1) / 3) * 3 + 1;
            $fecha_inicio = date('Y-' . str_pad($trimestre_inicio, 2, '0', STR_PAD_LEFT) . '-01');
            $fecha_fin = date('Y-m-t', strtotime(date('Y-' . str_pad($trimestre_inicio + 2, 2, '0', STR_PAD_LEFT) . '-01')));
            break;
        case 'ano':
            $fecha_inicio = date('Y-01-01'); // Primer día del año actual
            $fecha_fin = date('Y-12-31'); // Último día del año actual
            break;
    }
}

try {
    $db = getDB();
    
    // Obtener lista de agentes para el filtro
    $stmt = $db->query("SELECT id_usuario, nombre FROM usuarios WHERE rol IN ('administrador', 'soporte') ORDER BY nombre");
    $agentes = $stmt->fetchAll();
    
    // Consulta base para tickets
    $sql = 'SELECT t.*, u.nombre as nombre_usuario, a.nombre as nombre_asignado 
            FROM tickets t
            LEFT JOIN usuarios u ON t.id_usuario = u.id_usuario
            LEFT JOIN usuarios a ON t.id_asignado = a.id_usuario
            WHERE fecha_creacion BETWEEN ? AND ?';
            
    $params = [$fecha_inicio, $fecha_fin];
    
    // Aplicar filtros adicionales
    if (!empty($categoria)) {
        $sql .= ' AND categoria = ?';
        $params[] = $categoria;
    }
    
    if ($agente > 0) {
        $sql .= ' AND id_asignado = ?';
        $params[] = $agente;
    }
    
    $sql .= ' ORDER BY fecha_creacion DESC';
    
    // Ejecutar consulta
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
    
    // Estadísticas generales para el periodo seleccionado
    
    // Total de tickets en el periodo
    $total_tickets = count($tickets);
    
    // Tickets por estado
    $tickets_por_estado = ['abierto' => 0, 'en proceso' => 0, 'cerrado' => 0];
    foreach ($tickets as $ticket) {
        $tickets_por_estado[$ticket['estado']]++;
    }
    
    // Tickets por categoría
    $tickets_por_categoria = [];
    foreach ($tickets as $ticket) {
        $cat = $ticket['categoria'];
        if (!isset($tickets_por_categoria[$cat])) {
            $tickets_por_categoria[$cat] = 0;
        }
        $tickets_por_categoria[$cat]++;
    }
    
    // Tickets por prioridad
    $tickets_por_prioridad = ['baja' => 0, 'media' => 0, 'alta' => 0];
    foreach ($tickets as $ticket) {
        $tickets_por_prioridad[$ticket['prioridad']]++;
    }
    
    // Tickets por agente asignado
    $tickets_por_agente = [];
    foreach ($tickets as $ticket) {
        $nombre_agente = $ticket['nombre_asignado'] ?: 'Sin asignar';
        if (!isset($tickets_por_agente[$nombre_agente])) {
            $tickets_por_agente[$nombre_agente] = 0;
        }
        $tickets_por_agente[$nombre_agente]++;
    }
    
    // Tiempo promedio de resolución
    $tiempo_resolucion = [];
    $total_tiempo = 0;
    $tickets_cerrados = 0;
    
    foreach ($tickets as $ticket) {
        if ($ticket['estado'] === 'cerrado' && !empty($ticket['fecha_cierre'])) {
            $fecha_inicio = new DateTime($ticket['fecha_creacion']);
            $fecha_fin = new DateTime($ticket['fecha_cierre']);
            $diferencia = $fecha_inicio->diff($fecha_fin);
            $horas = $diferencia->days * 24 + $diferencia->h;
            
            $total_tiempo += $horas;
            $tickets_cerrados++;
            
            $tiempo_resolucion[] = [
                'id_ticket' => $ticket['id_ticket'],
                'titulo' => $ticket['titulo'],
                'horas' => $horas
            ];
        }
    }
    
    $tiempo_promedio = $tickets_cerrados > 0 ? $total_tiempo / $tickets_cerrados : 0;
    
    // Distribución por día de la semana
    $tickets_por_dia = [0, 0, 0, 0, 0, 0, 0]; // Domingo a Sábado
    
    foreach ($tickets as $ticket) {
        $dia_semana = date('w', strtotime($ticket['fecha_creacion']));
        $tickets_por_dia[$dia_semana]++;
    }
    
    // Preparar datos para exportación
    $export_data = [];
    foreach ($tickets as $ticket) {
        $export_data[] = [
            'ID' => $ticket['id_ticket'],
            'Título' => $ticket['titulo'],
            'Usuario' => $ticket['nombre_usuario'],
            'Categoría' => $ticket['categoria'],
            'Prioridad' => $ticket['prioridad'],
            'Estado' => $ticket['estado'],
            'Asignado a' => $ticket['nombre_asignado'] ?: 'Sin asignar',
            'Fecha Creación' => $ticket['fecha_creacion'],
            'Fecha Cierre' => $ticket['fecha_cierre'] ?: '-'
        ];
    }
    
    // Generar CSV si se solicita
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_tickets_' . $fecha_inicio . '_a_' . $fecha_fin . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        fputcsv($output, array_keys($export_data[0] ?? []));
        
        // Datos
        foreach ($export_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    // Generar PDF si se solicita
    if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
        // Código para generar PDF (requiere librería como FPDF o TCPDF)
        // Por simplicidad, este ejemplo no incluye la implementación completa
        
        require_once '../vendor/autoload.php'; // Requiere Composer y TCPDF
        
        class MYPDF extends TCPDF {
            // Encabezado de página
            public function Header() {
                $this->SetFont('helvetica', 'B', 15);
                $this->Cell(0, 10, 'Reporte de Tickets', 0, false, 'C');
                $this->Ln(15);
            }
            
            // Pie de página
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
            }
        }
        
        // Crear nuevo documento PDF
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Establecer información del documento
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Sistema de Tickets');
        $pdf->SetTitle('Reporte de Tickets');
        $pdf->SetSubject('Reporte de Tickets');
        
        // Establecer márgenes
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Establecer saltos de página automáticos
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Añadir una página
        $pdf->AddPage();
        
        // Establecer fuente
        $pdf->SetFont('helvetica', '', 10);
        
        // Añadir información del filtro
        $pdf->Write(0, 'Periodo: ' . date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)), '', 0, 'L', true);
        $pdf->Ln(5);
        
        // Crear tabla
        $pdf->SetFont('helvetica', 'B', 9);
        
        // Encabezados de la tabla
        $header = ['ID', 'Título', 'Usuario', 'Categoría', 'Prioridad', 'Estado', 'Asignado a', 'Fecha Creación'];
        
        $w = [10, 50, 30, 20, 20, 20, 30, 25];
        
        // Colores y fuentes para encabezados
        $pdf->SetFillColor(200, 220, 255);
        
        // Imprimir encabezado
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Datos de la tabla
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
        
        $fill = false;
        foreach($export_data as $row) {
            $pdf->Cell($w[0], 6, $row['ID'], 'LR', 0, 'C', $fill);
            $pdf->Cell($w[1], 6, $row['Título'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[2], 6, $row['Usuario'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[3], 6, $row['Categoría'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[4], 6, $row['Prioridad'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[5], 6, $row['Estado'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[6], 6, $row['Asignado a'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[7], 6, date('d/m/Y H:i', strtotime($row['Fecha Creación'])), 'LR', 0, 'L', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }
        
        // Línea de cierre
        $pdf->Cell(array_sum($w), 0, '', 'T');
        
        // Cerrar y generar PDF
        $pdf->Output('reporte_tickets_' . $fecha_inicio . '_a_' . $fecha_fin . '.pdf', 'D');
        exit;
    }
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al generar el reporte: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
}

// Establecer título de página
$page_title = 'Reportes y Estadísticas';

// Incluir header
include '../includes/header.php';
?>

<div class="admin-header">
    <h1>Reportes y Estadísticas</h1>
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
        
        <div class="dropdown">
            <button class="btn dropdown-toggle">
                <i class="fas fa-download"></i> Exportar
                <i class="fas fa-caret-down"></i>
            </button>
            <div class="dropdown-content">
                <a href="reportes.php?<?php echo http_build_query($_GET + ['export' => 'csv']); ?>">Exportar a CSV</a>
                <a href="reportes.php?<?php echo http_build_query($_GET + ['export' => 'pdf']); ?>">Exportar a PDF</a>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-section filter-section">
    <h2>Filtros de reporte</h2>
    <form action="reportes.php" method="GET" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label for="periodo">Periodo predefinido</label>
                <select id="periodo" name="periodo" onchange="this.form.submit()">
                    <option value="7dias" <?php echo $periodo === '7dias' ? 'selected' : ''; ?>>Últimos 7 días</option>
                    <option value="30dias" <?php echo $periodo === '30dias' ? 'selected' : ''; ?>>Últimos 30 días</option>
                    <option value="mes" <?php echo $periodo === 'mes' ? 'selected' : ''; ?>>Este mes</option>
                    <option value="trimestre" <?php echo $periodo === 'trimestre' ? 'selected' : ''; ?>>Este trimestre</option>
                    <option value="ano" <?php echo $periodo === 'ano' ? 'selected' : ''; ?>>Este año</option>
                    <option value="personalizado" <?php echo $periodo === 'personalizado' ? 'selected' : ''; ?>>Personalizado</option>
                </select>
            </div>
            
            <div class="form-group date-group" id="date-range-group" style="<?php echo $periodo === 'personalizado' ? '' : 'display: none;'; ?>">
                <label>Rango de fechas</label>
                <div class="date-inputs">
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" placeholder="Fecha inicio">
                    <span>a</span>
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" placeholder="Fecha fin">
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas las categorías</option>
                    <option value="software" <?php echo $categoria === 'software' ? 'selected' : ''; ?>>Software</option>
                    <option value="hardware" <?php echo $categoria === 'hardware' ? 'selected' : ''; ?>>Hardware</option>
                    <option value="conexion" <?php echo $categoria === 'conexion' ? 'selected' : ''; ?>>Conexión</option>
                    <option value="otro" <?php echo $categoria === 'otro' ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="agente">Agente asignado</label>
                <select id="agente" name="agente">
                    <option value="0">Todos los agentes</option>
                    <?php foreach ($agentes as $ag): ?>
                        <option value="<?php echo $ag['id_usuario']; ?>" <?php echo $agente == $ag['id_usuario'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ag['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group button-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </div>
        </div>
    </form>
</div>

<div class="report-summary">
    <div class="summary-item">
        <div class="summary-title">Total de tickets</div>
        <div class="summary-value"><?php echo $total_tickets; ?></div>
    </div>
    
    <div class="summary-item">
        <div class="summary-title">Tickets abiertos</div>
        <div class="summary-value"><?php echo $tickets_por_estado['abierto']; ?></div>
    </div>
    
    <div class="summary-item">
        <div class="summary-title">Tickets en proceso</div>
        <div class="summary-value"><?php echo $tickets_por_estado['en proceso']; ?></div>
    </div>
    
    <div class="summary-item">
        <div class="summary-title">Tickets cerrados</div>
        <div class="summary-value"><?php echo $tickets_por_estado['cerrado']; ?></div>
    </div>
    
    <div class="summary-item">
        <div class="summary-title">Tiempo promedio resolución</div>
        <div class="summary-value">
            <?php echo number_format($tiempo_promedio, 1); ?> 
            <small>horas</small>
        </div>
    </div>
</div>

<div class="dashboard-row">
    <div class="dashboard-column">
        <div class="dashboard-section">
            <h2>Tickets por estado</h2>
            <div class="chart-container">
                <canvas id="estadoChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="dashboard-column">
        <div class="dashboard-section">
            <h2>Tickets por categoría</h2>
            <div class="chart-container">
                <canvas id="categoriaChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-row">
    <div class="dashboard-column">
        <div class="dashboard-section">
            <h2>Tickets por prioridad</h2>
            <div class="chart-container">
                <canvas id="prioridadChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="dashboard-column">
        <div class="dashboard-section">
            <h2>Distribución por día de la semana</h2>
            <div class="chart-container">
                <canvas id="diaChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h2>Agentes con más tickets asignados</h2>
    <div class="chart-container horizontal-chart">
        <canvas id="agenteChart"></canvas>
    </div>
</div>

<div class="dashboard-section">
    <h2>Listado de tickets (<?php echo $total_tickets; ?>)</h2>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Usuario</th>
                    <th>Categoría</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Asignado a</th>
                    <th>Fecha Creación</th>
                    <th>Fecha Cierre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($tickets) > 0): ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo $ticket['id_ticket']; ?></td>
                            <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['nombre_usuario']); ?></td>
                            <td><?php echo ucfirst($ticket['categoria']); ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo $ticket['prioridad']; ?>">
                                    <?php echo ucfirst($ticket['prioridad']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo str_replace(' ', '-', $ticket['estado']); ?>">
                                    <?php echo ucfirst($ticket['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo $ticket['nombre_asignado'] ?: 'Sin asignar'; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></td>
                            <td><?php echo $ticket['fecha_cierre'] ? date('d/m/Y H:i', strtotime($ticket['fecha_cierre'])) : '-'; ?></td>
                            <td>
                                <a href="../ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="btn-icon" title="Ver ticket">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="no-results">No se encontraron tickets para el periodo seleccionado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.filter-section {
    margin-bottom: 30px;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.form-group {
    flex: 1;
    min-width: 200px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group select,
.form-group input {
    width: 100%;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.button-group {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.date-inputs {
    display: flex;
    align-items: center;
    gap: 10px;
}

.date-inputs input {
    flex: 1;
}

.report-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

.summary-item {
    flex: 1;
    min-width: 150px;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.summary-title {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 10px;
}

.summary-value {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
}

.summary-value small {
    font-size: 1rem;
    font-weight: normal;
}

.chart-container {
    height: 300px;
}

.horizontal-chart {
    height: 400px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background-color: #f5f5f5;
    font-weight: bold;
    color: #333;
}

.data-table tbody tr:hover {
    background-color: #f9f9f9;
}

.priority-badge, .status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: bold;
}

.priority-alta {
    background-color: rgba(244, 67, 54, 0.2);
    color: #e53935;
}

.priority-media {
    background-color: rgba(255, 152, 0, 0.2);
    color: #fb8c00;
}

.priority-baja {
    background-color: rgba(76, 175, 80, 0.2);
    color: #43a047;
}

.status-abierto {
    background-color: rgba(244, 67, 54, 0.2);
    color: #e53935;
}

.status-en-proceso {
    background-color: rgba(33, 150, 243, 0.2);
    color: #1e88e5;
}

.status-cerrado {
    background-color: rgba(76, 175, 80, 0.2);
    color: #43a047;
}

.no-results {
    text-align: center;
    padding: 30px 0;
    color: #666;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #fff;
    min-width: 160px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    z-index: 1;
    border-radius: 4px;
}

.dropdown-content a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

.dropdown-content a:hover {
    background-color: #f1f1f1;
}

.dropdown:hover .dropdown-content {
    display: block;
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
    // Mostrar/ocultar selector de fechas personalizado
    const periodoSelect = document.getElementById('periodo');
    const dateRangeGroup = document.getElementById('date-range-group');
    
    periodoSelect.addEventListener('change', function() {
        if (this.value === 'personalizado') {
            dateRangeGroup.style.display = 'block';
        } else {
            dateRangeGroup.style.display = 'none';
        }
    });
    
    // Gráfico de tickets por estado
    const estadoCtx = document.getElementById('estadoChart').getContext('2d');
    new Chart(estadoCtx, {
        type: 'pie',
        data: {
            labels: ['Abierto', 'En proceso', 'Cerrado'],
            datasets: [{
                data: [
                    <?php echo $tickets_por_estado['abierto']; ?>,
                    <?php echo $tickets_por_estado['en proceso']; ?>,
                    <?php echo $tickets_por_estado['cerrado']; ?>
                ],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(75, 192, 192, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Gráfico de tickets por categoría
    const categoriaCtx = document.getElementById('categoriaChart').getContext('2d');
    new Chart(categoriaCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php 
                    foreach ($tickets_por_categoria as $cat => $count) {
                        echo "'" . ucfirst($cat) . "',";
                    }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                        foreach ($tickets_por_categoria as $cat => $count) {
                            echo $count . ",";
                        }
                    ?>
                ],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Gráfico de tickets por prioridad
    const prioridadCtx = document.getElementById('prioridadChart').getContext('2d');
    new Chart(prioridadCtx, {
        type: 'pie',
        data: {
            labels: ['Baja', 'Media', 'Alta'],
            datasets: [{
                data: [
                    <?php echo $tickets_por_prioridad['baja']; ?>,
                    <?php echo $tickets_por_prioridad['media']; ?>,
                    <?php echo $tickets_por_prioridad['alta']; ?>
                ],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(255, 99, 132, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Gráfico de distribución por día de la semana
    const diaCtx = document.getElementById('diaChart').getContext('2d');
    new Chart(diaCtx, {
        type: 'bar',
        data: {
            labels: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
            datasets: [{
                label: 'Tickets creados',
                data: [
                    <?php 
                        foreach ($tickets_por_dia as $count) {
                            echo $count . ",";
                        }
                    ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de tickets por agente
    const agenteCtx = document.getElementById('agenteChart').getContext('2d');
    new Chart(agenteCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                    foreach ($tickets_por_agente as $agente => $count) {
                        echo "'" . $agente . "',";
                    }
                ?>
            ],
            datasets: [{
                label: 'Tickets asignados',
                data: [
                    <?php 
                        foreach ($tickets_por_agente as $agente => $count) {
                            echo $count . ",";
                        }
                    ?>
                ],
                backgroundColor: 'rgba(153, 102, 255, 0.7)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php
// Incluir footer
include '../includes/footer.php';
?>
