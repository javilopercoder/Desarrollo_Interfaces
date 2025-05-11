<?php
/**
 * Script de prueba para el envío de correos electrónicos
 * Este archivo permite verificar la correcta configuración del sistema de correos
 */

// Mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la clase de envío de correos
require_once 'includes/classes/EmailSender.php';

// Verificar si se envió el formulario de prueba
$email_sent = false;
$error = false;
$error_message = '';

// Mostrar información del servidor para debug
$server_info = [
    'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'No disponible',
    'SERVER_ADDR' => $_SERVER['SERVER_ADDR'] ?? 'No disponible',
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'No disponible',
    'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'No disponible',
    'PHP_VERSION' => phpversion()
];

// Obtener la configuración de correo para mostrar información
$email_config = @include('includes/config/email_config.php');

// Función para detectar entorno local
function is_localhost() {
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '';
    
    return (
        stripos($server_name, 'localhost') !== false || 
        $server_name == '127.0.0.1' ||
        substr($server_addr, 0, 3) == '127' ||
        $server_name == '::1'
    );
}

$is_local = is_localhost();

if (!empty($_POST)) {
    $to = filter_input(INPUT_POST, 'to', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_UNSAFE_RAW);
    $send_copy = isset($_POST['send_copy']);
    
    if ($to && $subject && $message) {
        try {
            // Crear instancia del mailer
            $emailSender = new EmailSender();
            
            // Enviar el correo
            if ($emailSender->send($to, $subject, $message, $send_copy)) {
                $email_sent = true;
            } else {
                $error = true;
                $error_message = "Error al enviar el correo: " . $emailSender->getLastError();
            }
        } catch (Exception $e) {
            $error = true;
            $error_message = "Excepción: " . $e->getMessage();
        }
    } else {
        $error = true;
        $error_message = "Por favor, complete todos los campos requeridos.";
    }
}

// Obtener la lista de archivos en el directorio vendor
$phpmailer_files = glob('vendor/phpmailer/*');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba del Sistema de Correo</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/contact.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #343a40;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info-section {
            background-color: #e2e3e5;
            border: 1px solid #d6d8db;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-section h2 {
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"], 
        input[type="email"], 
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 150px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #0069d9;
        }
        .checkbox-group {
            margin-top: 10px;
        }
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 4px;
            color: #e83e8c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #dee2e6;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Prueba del Sistema de Correo</h1>
        
        <?php if ($email_sent): ?>
            <div class="result success">
                <h3>✅ Correo enviado correctamente</h3>
                <p>El correo ha sido enviado a <strong><?php echo htmlspecialchars($to); ?></strong>.</p>
                <p>Por favor, verifica la bandeja de entrada del destinatario (y posiblemente la carpeta de spam).</p>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="result error">
                <h3>❌ Error al enviar el correo</h3>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="info-section">
            <h2>Información del Servidor</h2>
            <table>
                <tr>
                    <th>Propiedad</th>
                    <th>Valor</th>
                </tr>
                <?php foreach ($server_info as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td>Entorno detectado</td>
                    <td><?php echo $is_local ? 'Local (desarrollo)' : 'Producción'; ?></td>
                </tr>
                <tr>
                    <td>Método de envío</td>
                    <td><?php echo $is_local ? 'Simulación (log de correo)' : 'mail() nativo'; ?></td>
                </tr>
                <tr>
                    <td>Clase utilizada</td>
                    <td>SimpleMimeMail</td>
                </tr>
            </table>
            
            <h2>Archivos de log</h2>
            <?php 
            $log_files = [
                'logs/contactos.txt' => 'Correos enviados correctamente',
                'logs/contactos_errores.txt' => 'Errores al enviar correos',
                'logs/mail_simulation.log' => 'Simulación de correos (entorno local)'
            ];
            ?>
            <ul>
                <?php foreach ($log_files as $file => $description): ?>
                    <li>
                        <strong><?php echo htmlspecialchars(basename($file)); ?></strong>: 
                        <?php echo htmlspecialchars($description); ?>
                        <?php if (file_exists($file) && filesize($file) > 0): ?>
                            - <small>Tamaño: <?php echo number_format(filesize($file) / 1024, 2); ?> KB</small>
                        <?php else: ?>
                            - <small>Archivo vacío o no existente</small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <h2>Enviar correo de prueba</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="to">Destinatario:</label>
                <input type="email" id="to" name="to" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Asunto:</label>
                <input type="text" id="subject" name="subject" value="Prueba del sistema de correo" required>
            </div>
            
            <div class="form-group">
                <label for="message">Mensaje:</label>
                <textarea id="message" name="message" required>Este es un mensaje de prueba para verificar el funcionamiento del sistema de correo.</textarea>
            </div>
            
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="send_copy" checked> Enviar copia al administrador
                </label>
            </div>
            
            <button type="submit">Enviar correo de prueba</button>
        </form>
        
        <div class="info-section" style="margin-top: 20px;">
            <h2>Instrucciones</h2>
            <p>Este script permite probar la configuración del sistema de correo:</p>
            <ol>
                <li>Complete el formulario con una dirección de correo válida</li>
                <li>Haga clic en "Enviar correo de prueba"</li>
                <li>Verifique si recibe el correo en la dirección proporcionada</li>
            </ol>
            
            <p>Si está en un entorno de desarrollo local, es posible que el correo no se envíe correctamente. 
               Para usar un servidor SMTP real, modifique la configuración en <code>includes/config/email_config.php</code>.</p>
            
            <p>Para entornos de producción, se recomienda:</p>
            <ol>
                <li>Actualizar los datos SMTP con los proporcionados por su proveedor de hosting</li>
                <li>Considerar el uso de servicios como SendGrid, Mailgun, o similares para mayor fiabilidad</li>
                <li>Proteger este archivo con contraseña o eliminarlo después de las pruebas</li>
            </ol>
        </div>
    </div>
</body>
</html>
