# Sistema de Correo Electrónico

Este sistema ha sido diseñado para funcionar tanto en entornos de desarrollo local como en servidores de producción.

## Características

- Detección automática de entorno (local vs. producción)
- En local: simulación de envío de correos con registro en archivo log
- En producción: uso de la función nativa `mail()` de PHP
- Soporte para HTML en los correos
- Opción para enviar copias al administrador
- Registro de actividades y errores

## Archivos Principales

- `includes/config/email_config.php`: Configuración del sistema de correo
- `includes/classes/EmailSender.php`: Clase principal para envío de correos
- `includes/classes/SimpleMimeMail.php`: Clase para envío de correos con formato HTML
- `test_email.php`: Script para probar la configuración

## Configuración para Producción

Para configurar el sistema en un entorno de producción, edite el archivo `includes/config/email_config.php`:

```php
$email_config = [
    // Email del soporte (remitente)
    'from_email' => 'soporte@tudominio.com',
    'from_name' => 'Soporte Técnico',
    
    // Email del administrador (para recibir copias)
    'admin_email' => 'tu_email@tudominio.com',
    
    // Asunto del correo de copia para el administrador
    'admin_subject_prefix' => '[COPIA] ',
];

// Uso de variables de entorno (recomendado)
$email_config['from_email'] = getenv('MAIL_FROM') ?: 'soporte@tudominio.com';
$email_config['admin_email'] = getenv('ADMIN_EMAIL') ?: 'admin@tudominio.com';
```

## Funcionamiento en Entorno Local

En un entorno de desarrollo local, el sistema:

1. Detecta automáticamente que está en un entorno local (localhost, 127.0.0.1, etc.)
2. En lugar de intentar enviar correos reales, simula el envío
3. Registra todos los detalles del correo (destinatario, asunto, cuerpo) en el archivo `logs/mail_simulation.log`
4. Devuelve "éxito" a la aplicación para que el flujo continúe normalmente

Esta simulación permite probar toda la funcionalidad sin necesidad de configurar un servidor de correo real en el entorno local.

## Funcionamiento en Producción

En un servidor de producción:

1. El sistema utiliza la función nativa `mail()` de PHP para enviar correos reales
2. Requiere que el servidor web tenga correctamente configurado un servicio de correo
3. Envía tanto el correo al usuario como la copia al administrador
4. Registra todos los envíos y errores en los archivos de log correspondientes

## Seguridad

En producción, es recomendable:

1. Usar variables de entorno para almacenar direcciones de correo
2. Asegurar que los archivos de configuración no sean accesibles públicamente
3. Eliminar el archivo `test_email.php` después de verificar el funcionamiento
4. Verificar los permisos de los archivos de log para que no sean accesibles desde la web

## Problemas Comunes

- **El correo no se envía en producción**: Verifique que la función `mail()` esté habilitada en su configuración de PHP y que el servidor esté correctamente configurado para enviar correos.
- **Correos marcados como spam**: Asegúrese de que el dominio del remitente coincida con el dominio desde donde se envía el correo.
- **No aparecen registros en los logs**: Verifique los permisos de escritura en la carpeta `logs/`.

## Verificación

1. Acceda a `test_email.php` en su navegador
2. Complete el formulario de prueba
3. En entorno local: verifique el archivo `logs/mail_simulation.log` para ver los detalles del correo simulado
4. En producción: verifique si el correo llega correctamente a la bandeja de entrada

## Archivos de Log

Los logs se guardan en:

- `logs/contactos.txt`: Registro de correos enviados correctamente
- `logs/contactos_errores.txt`: Registro de errores en el envío de correos
- `logs/mail_simulation.log`: Simulación de correos en entorno local
