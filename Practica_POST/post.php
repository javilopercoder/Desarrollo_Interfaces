<?php
// Verificamos si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recogemos y sanitizamos los datos del formulario
    $nombre  = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $correo  = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
    $mensaje = filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validación básica: se requiere que todos los campos estén completos
    if (empty($nombre) || empty($correo) || empty($mensaje)) {
        echo "Todos los campos son obligatorios.";
    } else {
        // Configuración del correo
        $destinatario = "destinatario@example.com";  // Reemplaza con tu dirección de correo
        $asunto = "Mensaje de: " . $nombre;
        $cuerpo = "Nombre: $nombre\n";
        $cuerpo .= "Correo: $correo\n\n";
        $cuerpo .= "Mensaje:\n$mensaje";
        $cabeceras = "From: $correo\r\n";

        // Envío del correo
        if (mail($destinatario, $asunto, $cuerpo, $cabeceras)) {
            echo "El correo se ha enviado correctamente.";
        } else {
            echo "Hubo un error al enviar el correo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario de Contacto</title>
    <style>
        /* Estilos básicos para el formulario */
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { max-width: 500px; margin: 0 auto; }
        label { display: block; margin-top: 10px; }
        input, textarea { width: 100%; padding: 8px; margin-top: 5px; }
        input[type="submit"] { width: auto; background-color: #4CAF50; color: white; border: none; cursor: pointer; margin-top: 15px; }
        input[type="submit"]:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h1>Contacto</h1>
    <!-- Formulario que envía datos mediante POST -->
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" required>

        <label for="correo">Correo Electrónico:</label>
        <input type="email" name="correo" id="correo" required>

        <label for="mensaje">Mensaje:</label>
        <textarea name="mensaje" id="mensaje" rows="5" required></textarea>

        <input type="submit" value="Enviar">
    </form>
</body>
</html>