<?php
/**
 * Diagnóstico del Sistema de Correo
 * Esta herramienta permite a los administradores verificar el estado del sistema de correo
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

// Incluir las clases de correo
require_once '../includes/classes/EmailSender.php';
require_once '../includes/classes/SimpleMimeMail.php';

// Establecer título de página
$page_title = 'Diagnóstico del Sistema de Correo';

// Determinar el entorno
function is_local_environment() {
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '';
    
    return (
        stripos($server_name, 'localhost') !== false || 
        $server_name == '127.0.0.1' ||
        substr($server_addr, 0, 3) == '127' ||
        $server_name == '::1'
    );
}

// Verificar los archivos de log
function check_logs() {
    $log_files = [
        '../logs/contactos.txt',
        '../logs/contactos_errores.txt',
        '../logs/mail_simulation.log'
    ];
    
    $log_status = [];
    
    foreach ($log_files as $file) {
        $status = [
            'path' => $file,
            'exists' => file_exists($file),
            'writable' => is_writable($file) || (is_writable(dirname($file)) && !file_exists($file)),
            'size' => file_exists($file) ? filesize($file) : 0,
            'last_modified' => file_exists($file) ? filemtime($file) : 0,
            'sample' => ''
        ];
        
        // Obtener una muestra del contenido si existe
        if ($status['exists'] && $status['size'] > 0) {
            $content = file_get_contents($file, false, null, 0, 500);
            $status['sample'] = substr($content, 0, 500);
        }
        
        $log_status[] = $status;
    }
    
    return $log_status;
}

// Verificar la configuración de correo
function check_mail_config() {
    // Intentar cargar la configuración
    $config = @include('../includes/config/email_config.php');
    
    if (!is_array($config)) {
        return [
            'status' => 'error',
            'message' => 'No se pudo cargar la configuración de correo.'
        ];
    }
    
    // Verificar campos mínimos
    $required_fields = ['from_email', 'admin_email'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($config[$field]) || empty($config[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        return [
            'status' => 'warning',
            'message' => 'Faltan algunos campos en la configuración: ' . implode(', ', $missing_fields),
            'config' => $config
        ];
    }
    
    return [
        'status' => 'success',
        'message' => 'Configuración de correo cargada correctamente.',
        'config' => $config
    ];
}

// Probar el envío de correo
function test_mail_send() {
    // Crear instancia del mailer
    $emailSender = new EmailSender();
    
    // Configurar mensaje de prueba
    $to = 'test@example.com'; // Dirección ficticia para pruebas
    $subject = 'Prueba de Diagnóstico - ' . date('Y-m-d H:i:s');
    $message = '<html><body>
        <h1>Prueba de diagnóstico del sistema de correo</h1>
        <p>Este es un mensaje de prueba generado por la herramienta de diagnóstico.</p>
        <p>Fecha y hora: ' . date('Y-m-d H:i:s') . '</p>
        <p>Servidor: ' . $_SERVER['SERVER_NAME'] . '</p>
    </body></html>';
    
    // Enviar el correo (solo simulación)
    $result = $emailSender->send($to, $subject, $message, false);
    
    return [
        'status' => $result ? 'success' : 'error',
        'message' => $result ? 'Prueba de correo enviada/simulada correctamente.' : 'Error al enviar correo: ' . $emailSender->getLastError(),
        'to' => $to,
        'subject' => $subject
    ];
}

// Realizar diagnóstico
$is_local = is_local_environment();
$logs_status = check_logs();
$config_status = check_mail_config();
$mail_test = test_mail_send();

// Incluir header
include '../includes/header.php';
?>

<section>
    <div class="breadcrumbs">
        <a href="../index.php"><i class="fas fa-home"></i> Inicio</a> <span class="separator">&raquo;</span>
        <a href="index.php"><i class="fas fa-user-shield"></i> Admin</a> <span class="separator">&raquo;</span>
        <span><i class="fas fa-envelope"></i> Diagnóstico de Correo</span>
    </div>
    
    <h1 class="page-title">Diagnóstico del Sistema de Correo</h1>
    
    <div class="diagnostic-container">
        <!-- Información del Entorno -->
        <div class="diagnostic-section">
            <h2><i class="fas fa-server"></i> Entorno Detectado</h2>
            <div class="diagnostic-content">
                <p>Tipo de entorno: <strong><?php echo $is_local ? 'Local (Desarrollo)' : 'Producción'; ?></strong></p>
                <p>Servidor: <strong><?php echo htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'Desconocido'); ?></strong></p>
                <p>IP: <strong><?php echo htmlspecialchars($_SERVER['SERVER_ADDR'] ?? 'Desconocida'); ?></strong></p>
                <p>Versión de PHP: <strong><?php echo phpversion(); ?></strong></p>
                <p>Función mail() disponible: <strong><?php echo function_exists('mail') ? 'Sí' : 'No'; ?></strong></p>
            </div>
        </div>
        
        <!-- Estado de la Configuración -->
        <div class="diagnostic-section">
            <h2><i class="fas fa-cog"></i> Configuración de Correo</h2>
            <div class="diagnostic-content">
                <div class="status-indicator <?php echo $config_status['status']; ?>">
                    <?php if ($config_status['status'] === 'success'): ?>
                        <i class="fas fa-check-circle"></i> Configuración OK
                    <?php elseif ($config_status['status'] === 'warning'): ?>
                        <i class="fas fa-exclamation-triangle"></i> Advertencia
                    <?php else: ?>
                        <i class="fas fa-times-circle"></i> Error
                    <?php endif; ?>
                </div>
                
                <p><?php echo htmlspecialchars($config_status['message']); ?></p>
                
                <?php if (isset($config_status['config'])): ?>
                <div class="config-details">
                    <h3>Valores de Configuración:</h3>
                    <ul>
                        <?php foreach ($config_status['config'] as $key => $value): ?>
                            <?php if (!is_array($value)): ?>
                                <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Estado de los Archivos de Log -->
        <div class="diagnostic-section">
            <h2><i class="fas fa-file-alt"></i> Archivos de Log</h2>
            <div class="diagnostic-content">
                <?php foreach ($logs_status as $log): ?>
                <div class="log-item">
                    <h3><?php echo htmlspecialchars(basename($log['path'])); ?></h3>
                    <div class="status-indicator <?php echo $log['exists'] && $log['writable'] ? 'success' : 'error'; ?>">
                        <?php if ($log['exists'] && $log['writable']): ?>
                            <i class="fas fa-check-circle"></i> OK
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Problema
                        <?php endif; ?>
                    </div>
                    
                    <p>
                        Existe: <strong><?php echo $log['exists'] ? 'Sí' : 'No'; ?></strong> |
                        Escritura: <strong><?php echo $log['writable'] ? 'Permitida' : 'Denegada'; ?></strong> |
                        Tamaño: <strong><?php echo number_format($log['size'] / 1024, 2); ?> KB</strong>
                        <?php if ($log['last_modified'] > 0): ?>
                            | Última modificación: <strong><?php echo date('Y-m-d H:i:s', $log['last_modified']); ?></strong>
                        <?php endif; ?>
                    </p>
                    
                    <?php if (!empty($log['sample'])): ?>
                    <div class="log-sample">
                        <h4>Muestra del contenido:</h4>
                        <pre><?php echo htmlspecialchars($log['sample']); ?><?php if (strlen($log['sample']) >= 500): ?>...</<?php endif; ?></pre>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Prueba de Envío -->
        <div class="diagnostic-section">
            <h2><i class="fas fa-paper-plane"></i> Prueba de Envío de Correo</h2>
            <div class="diagnostic-content">
                <div class="status-indicator <?php echo $mail_test['status']; ?>">
                    <?php if ($mail_test['status'] === 'success'): ?>
                        <i class="fas fa-check-circle"></i> Prueba Exitosa
                    <?php else: ?>
                        <i class="fas fa-times-circle"></i> Error
                    <?php endif; ?>
                </div>
                
                <p><?php echo htmlspecialchars($mail_test['message']); ?></p>
                
                <div class="mail-test-details">
                    <p><strong>Destinatario:</strong> <?php echo htmlspecialchars($mail_test['to']); ?></p>
                    <p><strong>Asunto:</strong> <?php echo htmlspecialchars($mail_test['subject']); ?></p>
                    
                    <?php if ($is_local): ?>
                    <p class="note">
                        <i class="fas fa-info-circle"></i> En entorno local, el correo se simula y se registra en
                        <code>logs/mail_simulation.log</code>. No se realiza un envío real.
                    </p>
                    <?php else: ?>
                    <p class="note">
                        <i class="fas fa-info-circle"></i> En entorno de producción, se ha intentado enviar un correo real
                        utilizando la función mail() de PHP.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="actions">
        <a href="../test_email.php" class="btn"><i class="fas fa-envelope"></i> Ir a Prueba de Correo</a>
        <a href="../contacto.php" class="btn"><i class="fas fa-comment"></i> Probar Formulario de Contacto</a>
        <a href="../docs/SISTEMA_CORREO.md" class="btn" download><i class="fas fa-file-alt"></i> Descargar Documentación</a>
    </div>
</section>

<style>
.diagnostic-container {
    margin: 20px 0;
}

.diagnostic-section {
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.diagnostic-section h2 {
    background-color: #f8f9fa;
    margin: 0;
    padding: 15px 20px;
    font-size: 1.2em;
    color: #343a40;
    border-bottom: 1px solid #dee2e6;
}

.diagnostic-content {
    padding: 20px;
}

.status-indicator {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 3px;
    margin-bottom: 15px;
    font-weight: bold;
}

.status-indicator.success {
    background-color: #d4edda;
    color: #155724;
}

.status-indicator.warning {
    background-color: #fff3cd;
    color: #856404;
}

.status-indicator.error {
    background-color: #f8d7da;
    color: #721c24;
}

.log-item {
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.log-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.log-sample {
    margin-top: 10px;
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    overflow: auto;
    max-height: 200px;
}

.log-sample pre {
    margin: 0;
    white-space: pre-wrap;
    font-size: 0.9em;
}

.config-details {
    margin-top: 15px;
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.config-details h3 {
    margin-top: 0;
    font-size: 1em;
}

.config-details ul {
    margin: 0;
    padding-left: 20px;
}

.note {
    background-color: #e2f3fc;
    padding: 10px;
    border-radius: 5px;
    color: #0c5460;
    margin-top: 15px;
}

.actions {
    margin-top: 30px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.btn {
    display: inline-block;
    padding: 10px 15px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    border: none;
}

.btn:hover {
    background-color: #0069d9;
}

@media (max-width: 768px) {
    .actions {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
