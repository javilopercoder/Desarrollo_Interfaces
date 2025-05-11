<?php
/**
 * Clase MIME simple por si PHPMailer no está disponible
 * (versión de respaldo)
 */

class SimpleMimeMail {
    private $to;
    private $subject;
    private $message;
    private $headers = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->headers = [
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8',
            'From' => 'soporte@sistema-ticketing.com'
        ];
    }
    
    /**
     * Establecer destinatario
     * @param string $email Email del destinatario
     */
    public function setTo($email) {
        $this->to = $email;
        return $this;
    }
    
    /**
     * Establecer asunto
     * @param string $subject Asunto del correo
     */
    public function setSubject($subject) {
        $this->subject = $subject;
        return $this;
    }
    
    /**
     * Establecer mensaje
     * @param string $message Mensaje HTML
     */
    public function setMessage($message) {
        $this->message = $message;
        return $this;
    }
    
    /**
     * Añadir o modificar una cabecera
     * @param string $name Nombre de la cabecera
     * @param string $value Valor de la cabecera
     */
    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Enviar el correo
     * @return boolean True si se envió correctamente
     */
    public function send() {
        if (empty($this->to) || empty($this->subject) || empty($this->message)) {
            return false;
        }
        
        // Construir cabeceras
        $headers_str = '';
        foreach ($this->headers as $name => $value) {
            $headers_str .= "$name: $value\r\n";
        }
        
        // En entorno local, simulamos el envío de correo
        if ($this->isLocalhost()) {
            // Registrar el intento de envío en un log
            $log_entry = date('Y-m-d H:i:s') . " - SIMULACIÓN DE CORREO\n";
            $log_entry .= "Para: {$this->to}\n";
            $log_entry .= "Asunto: {$this->subject}\n";
            $log_entry .= "Cabeceras: " . $headers_str . "\n";
            $log_entry .= "Mensaje: " . substr($this->message, 0, 500) . "...\n\n";
            
            // Guardar en un archivo log
            $log_file = __DIR__ . '/../../logs/mail_simulation.log';
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            
            return true; // Simular éxito en entorno local
        }
        
        // En producción, enviar correo real con mail()
        return @mail($this->to, $this->subject, $this->message, $headers_str);
    }
    
    /**
     * Detectar si estamos en un entorno local
     * @return boolean
     */
    private function isLocalhost() {
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        $server_addr = $_SERVER['SERVER_ADDR'] ?? '';
        
        return (
            stripos($server_name, 'localhost') !== false || 
            $server_name == '127.0.0.1' ||
            substr($server_addr, 0, 3) == '127' ||
            $server_name == '::1'
        );
    }
}
?>
