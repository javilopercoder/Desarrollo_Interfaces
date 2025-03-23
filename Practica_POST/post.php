<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre      = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $apellidos   = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $telefono    = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $mail        = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL);
    $comentarios = filter_input(INPUT_POST, 'comentarios', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (empty($nombre) || empty($apellidos) || empty($telefono) || empty($mail) || empty($comentarios)) {
        echo "Todos los campos son obligatorios.";
    } else {
        $destinatario = "javier.lopezramirezg@digitechfp.com";
        $asunto = "Mensaje de: $nombre $apellidos";
        $cuerpo = "Nombre: $nombre\nApellidos: $apellidos\nTeléfono: $telefono\nMail: $mail\nComentarios:\n$comentarios";
        $cabeceras = "From: $mail\r\n";

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
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" required>

        <label for="apellidos">Apellidos:</label>
        <input type="text" name="apellidos" id="apellidos" required>

        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" id="telefono" required>

        <label for="mail">Mail:</label>
        <input type="email" name="mail" id="mail" required>

        <label for="comentarios">Comentarios:</label>
        <textarea name="comentarios" id="comentarios" rows="5" required></textarea>

        <input type="submit" value="Enviar">
    </form>
</body>
</html>