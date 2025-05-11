<?php
/**
 * Clase para manejar el envío de correos electrónicos
 * Utiliza la clase SimpleMimeMail para envío básico de correos
 */

// Incluir el archivo de SimpleMimeMail
require_once __DIR__ . '/SimpleMimeMail.php';

// Definir excepciones básicas si no existen
if (!class_exists('EmailException')) {
    class EmailException extends Exception {}
}

class EmailSender {
    private $config;
    private $mailer;
    private $last_error = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Intentamos cargar la configuración, si falla usamos valores por defecto
        try {
            $this->config = @include(__DIR__ . '/../config/email_config.php');
            
            // Si no se pudo incluir, establecer valores por defecto
            if (!is_array($this->config)) {
                $this->config = $this->getDefaultConfig();
            }
        } catch (Exception $e) {
            $this->config = $this->getDefaultConfig();
        }
        
        // Inicializar SimpleMimeMail
        $this->mailer = new SimpleMimeMail();
        
        // Configurar el mailer
        $this->setupMailer();
    }
    
    /**
     * Establece la configuración predeterminada
     */
    private function getDefaultConfig() {
        return [
            'from_email' => 'soporte@sistema-ticketing.com',
            'from_name' => 'Sistema de Tickets',
            'admin_email' => 'javilopercoder@gmail.com',
            'admin_subject_prefix' => '[COPIA] ',
            'use_smtp' => false
        ];
    }
    
    /**
     * Configurar el objeto mailer
     */
    private function setupMailer() {
        try {
            // Configuración básica de cabeceras
            $this->mailer->setHeader('From', $this->config['from_email']);
            $this->mailer->setHeader('Reply-To', $this->config['from_email']);
            
            // Otras configuraciones específicas de SMTP se ignoran porque 
            // SimpleMimeMail siempre usa la función mail() de PHP
            return true;
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
        }
    }
    
    /**
     * Enviar un correo electrónico
     * 
     * @param string $to Dirección del destinatario
     * @param string $subject Asunto del correo
     * @param string $message Mensaje HTML
     * @param boolean $send_admin_copy Enviar una copia al administrador
     * @return boolean True si se envió correctamente
     */
    public function send($to, $subject, $message, $send_admin_copy = true) {
        try {
            // Configurar destinatario, asunto y mensaje
            $this->mailer->setTo($to);
            $this->mailer->setSubject($subject);
            $this->mailer->setMessage($message);
            
            // Enviar el correo
            $result = $this->mailer->send();
            
            // Si se solicita, enviar copia al administrador
            if ($send_admin_copy && $result && isset($this->config['admin_email'])) {
                $this->sendAdminCopy($subject, $message);
            }
            
            return $result;
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Enviar una copia al administrador
     * 
     * @param string $original_subject Asunto original
     * @param string $message Mensaje HTML
     * @return boolean True si se envió correctamente
     */
    private function sendAdminCopy($original_subject, $message) {
        try {
            // Crear una nueva instancia para el correo al admin
            $admin_mailer = new SimpleMimeMail();
            
            // Configurar cabeceras básicas
            $admin_mailer->setHeader('From', $this->config['from_email']);
            $admin_mailer->setHeader('Reply-To', $this->config['from_email']);
            
            // Configurar destinatario y asunto
            $admin_mailer->setTo($this->config['admin_email']);
            $admin_mailer->setSubject($this->config['admin_subject_prefix'] . $original_subject);
            $admin_mailer->setMessage($message);
            
            // Enviar el correo
            return $admin_mailer->send();
        } catch (Exception $e) {
            // No queremos que un error aquí afecte al proceso principal
            return false;
        }
    }
    
    /**
     * Obtener el último error
     * 
     * @return string Mensaje de error
     */
    public function getLastError() {
        return $this->last_error;
    }
    
    /**
     * Añadir archivos adjuntos
     * 
     * @param string $path Ruta al archivo
     * @param string $name Nombre del archivo (opcional)
     * @return boolean True si se añadió correctamente
     */
    public function addAttachment($path, $name = '') {
        // SimpleMimeMail no soporta adjuntos, esta función existe solo por compatibilidad
        $this->last_error = "La clase SimpleMimeMail no soporta archivos adjuntos";
        return false;
    }
}
?>
