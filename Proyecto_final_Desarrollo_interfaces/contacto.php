<?php
/**
 * Página de contacto del sistema de ticketing
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Inicializar variables
// Incluir la clase de envío de correos
require_once 'includes/classes/EmailSender.php';

$mensaje_enviado = false;
$error = false;
$ticket_id = '';
$errores = [];

// Establecer título de página
$page_title = 'Contacto';

// Función para validar dirección de correo (verificación MX)
function validarCorreoMX($email) {
    // Obtener el dominio del correo
    $dominio = explode('@', $email)[1];
    
    // Verificar si existen registros MX para el dominio
    if (checkdnsrr($dominio, 'MX')) {
        return true;
    } else {
        return false;
    }
}

// Función para generar ID de seguimiento único
function generarIdSeguimiento() {
    $prefijo = 'CONT';
    $fecha = date('Ymd');
    $aleatorio = substr(md5(uniqid(rand(), true)), 0, 6);
    
    return $prefijo . '-' . $fecha . '-' . strtoupper($aleatorio);
}

// Procesar el formulario de contacto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = isset($_POST['nombre']) ? sanitizar($_POST['nombre']) : '';
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    $asunto = isset($_POST['asunto']) ? sanitizar($_POST['asunto']) : '';
    $mensaje = isset($_POST['mensaje']) ? sanitizar($_POST['mensaje']) : '';
    
    // Validar campos
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio';
    }
    
    if (empty($email)) {
        $errores[] = 'El correo electrónico es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El formato del correo electrónico no es válido';
    } elseif (!validarCorreoMX($email)) {
        $errores[] = 'El dominio del correo electrónico no es válido (verificación MX)';
    }
    
    if (empty($asunto)) {
        $errores[] = 'El asunto es obligatorio';
    }
    
    if (empty($mensaje)) {
        $errores[] = 'El mensaje es obligatorio';
    }
    
    // Si no hay errores, procesar el formulario
    if (empty($errores)) {
        try {
            // Generar ID único para seguimiento
            $ticket_id = generarIdSeguimiento();
            
            // Guardar en la base de datos
            $db = getDB();
            $stmt = $db->prepare('
                INSERT INTO contactos (id_seguimiento, nombre, email, asunto, mensaje, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ');
            
            // Si la tabla no existe, crearla
            try {
                $db->exec('
                    CREATE TABLE IF NOT EXISTS contactos (
                        id_contacto INTEGER PRIMARY KEY AUTOINCREMENT,
                        id_seguimiento TEXT NOT NULL,
                        nombre TEXT NOT NULL,
                        email TEXT NOT NULL,
                        asunto TEXT NOT NULL,
                        mensaje TEXT NOT NULL,
                        fecha_creacion DATETIME NOT NULL,
                        estado TEXT DEFAULT "pendiente",
                        respuesta TEXT,
                        fecha_respuesta DATETIME
                    )
                ');
            } catch (PDOException $e) {
                // Ignorar error si la tabla ya existe
            }
            
            $stmt->execute([$ticket_id, $nombre, $email, $asunto, $mensaje]);
            
            // Preparar correo de confirmación
            $asunto_mail = "Confirmación de recepción de su mensaje - " . $ticket_id;
            $mensaje_mail = "
                <html>
                <head>
                    <title>Confirmación de recepción</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                        h2 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                        h3 { color: #3498db; }
                        .ticket-id { background-color: #f8f9fa; padding: 10px; border-left: 4px solid #3498db; margin: 15px 0; }
                        hr { border: 0; height: 1px; background: #eee; margin: 20px 0; }
                        .footer { font-size: 12px; color: #7f8c8d; margin-top: 30px; padding-top: 10px; border-top: 1px solid #eee; }
                    </style>
                </head>
                <body>
                    <h2>Gracias por contactar con nosotros</h2>
                    <p>Estimado/a {$nombre},</p>
                    <p>Hemos recibido su mensaje con el siguiente ID de seguimiento:</p>
                    <p class=\"ticket-id\"><strong>{$ticket_id}</strong></p>
                    <p>Por favor, conserve este ID para futuras referencias.</p>
                    <hr>
                    <h3>Resumen de su mensaje:</h3>
                    <p><strong>Asunto:</strong> {$asunto}</p>
                    <p><strong>Mensaje:</strong></p>
                    <p>{$mensaje}</p>
                    <hr>
                    <p>Responderemos a su consulta lo antes posible.</p>
                    <p>Atentamente,<br>
                    El equipo de soporte</p>
                    <div class=\"footer\">
                        Este es un mensaje automático, por favor no responda directamente a este correo.
                    </div>
                </body>
                </html>
            ";
            
            // Crear instancia de la clase EmailSender
            $emailSender = new EmailSender();
            
            // Enviar el correo al usuario con copia al administrador
            $email_enviado = $emailSender->send($email, $asunto_mail, $mensaje_mail);
            
            // Verificar si hubo errores en el envío
            if (!$email_enviado) {
                $log_mensaje = date('Y-m-d H:i:s') . " - ERROR al enviar correo a {$email} con ID {$ticket_id}: " . $emailSender->getLastError() . "\n";
                file_put_contents('logs/contactos_errores.txt', $log_mensaje, FILE_APPEND);
            } else {
                // Log del correo exitoso
                $log_mensaje = date('Y-m-d H:i:s') . " - Correo enviado a {$email} con ID {$ticket_id}\n";
                file_put_contents('logs/contactos.txt', $log_mensaje, FILE_APPEND);
            }
            
            $mensaje_enviado = true;
            
        } catch (PDOException $e) {
            $error = true;
            $errores[] = 'Error al procesar su solicitud: ' . $e->getMessage();
        }
    }
}

// Incluir header
include 'includes/header.php';
?>

<section>
    <div class="breadcrumbs">
        <a href="index.php"><i class="fas fa-home"></i> Inicio</a> <span class="separator">&raquo;</span>
        <span><i class="fas fa-envelope"></i> Contacto</span>
    </div>
    
    <h1 class="form-title">Contacto</h1>
    
    <?php if ($mensaje_enviado): ?>
        <div class="alert alert-success">
            <h3><i class="fas fa-check-circle"></i> Mensaje enviado correctamente</h3>
            <p>Gracias por contactar con nosotros. Hemos recibido su mensaje y le responderemos lo antes posible.</p>
            <p>Su ID de seguimiento es: <strong><?php echo $ticket_id; ?></strong></p>
            <p>Hemos enviado una copia de su mensaje a la dirección de correo proporcionada.</p>
            <p><a href="index.php" class="btn">Volver al inicio</a></p>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-error">
            <h3><i class="fas fa-exclamation-circle"></i> Error al procesar su solicitud</h3>
            <ul>
                <?php foreach ($errores as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Por favor, inténtelo de nuevo más tarde o contacte con el administrador.</p>
        </div>
    <?php endif; ?>
    
    <?php if (!$mensaje_enviado): ?>
        <div class="contact-container">
            <div class="contact-info">
                <h3>Información de contacto</h3>
                <p><i class="fas fa-map-marker-alt"></i> Dirección: Calle Ejemplo, 123</p>
                <p><i class="fas fa-phone"></i> Teléfono: +34 912 345 678</p>
                <p><i class="fas fa-envelope"></i> Email: javilopercoder@gmail.com</p>
                <p><i class="fas fa-clock"></i> Horario: Lunes a Viernes, 9:00 - 18:00</p>
                
                <div class="social-links">
                    <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-facebook"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            
            <div class="contact-form">
                <h3>Envíenos un mensaje</h3>
                
                <?php if (!empty($errores) && !$error): ?>
                    <div class="alert alert-error">
                        <h4>Por favor, corrija los siguientes errores:</h4>
                        <ul>
                            <?php foreach ($errores as $err): ?>
                                <li><?php echo $err; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="contacto.php" method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre completo <span class="required">*</span></label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="asunto">Asunto <span class="required">*</span></label>
                        <input type="text" id="asunto" name="asunto" value="<?php echo isset($_POST['asunto']) ? htmlspecialchars($_POST['asunto']) : ''; ?>" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="mensaje">Mensaje <span class="required">*</span></label>
                        <textarea id="mensaje" name="mensaje" rows="6" required class="form-control"><?php echo isset($_POST['mensaje']) ? htmlspecialchars($_POST['mensaje']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar mensaje</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
