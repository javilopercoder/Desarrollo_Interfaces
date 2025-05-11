<?php
/**
 * Configuración para el envío de correos electrónicos
 * Este archivo define la configuración básica para el envío de correos
 */

// Configuración de correo
$email_config = [
    // Email del soporte (remitente)
    'from_email' => 'soporte@sistema-ticketing.com',
    'from_name' => 'Soporte Técnico',
    
    // Email del administrador (para recibir copias)
    'admin_email' => 'javilopercoder@gmail.com',
    
    // Asunto del correo de copia para el administrador
    'admin_subject_prefix' => '[COPIA] ',
];

// En producción, es mejor usar variables de entorno para las credenciales
// Ejemplo:
// $email_config['from_email'] = getenv('MAIL_FROM') ?: 'soporte@sistema-ticketing.com';
// $email_config['admin_email'] = getenv('ADMIN_EMAIL') ?: 'admin@sistema-ticketing.com';

return $email_config;
?>
