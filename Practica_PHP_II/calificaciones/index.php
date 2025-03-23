<?php
// Verificar si se ha enviado una nota por GET o POST
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["nota"]) && is_numeric($_POST["nota"])) {
        $nota = (int) $_POST["nota"];

        // Validar que la nota esté en el rango correcto
        if ($nota < 0 || $nota > 100) {
            $mensaje = "Error: La nota debe estar entre 0 y 100.";
        } else {
            // Determinar la calificación
            if ($nota >= 90) {
                $calificacion = "A";
            } elseif ($nota >= 80) {
                $calificacion = "B";
            } elseif ($nota >= 70) {
                $calificacion = "C";
            } elseif ($nota >= 60) {
                $calificacion = "D";
            } else {
                $calificacion = "F";
            }

            // Mensaje adicional usando switch
            switch ($calificacion) {
                case "A":
                    $mensaje = "Calificación: A - ¡Excelente!";
                    break;
                case "B":
                    $mensaje = "Calificación: B - Buen trabajo.";
                    break;
                case "C":
                    $mensaje = "Calificación: C - Puedes mejorar.";
                    break;
                case "D":
                    $mensaje = "Calificación: D - Necesitas esforzarte más.";
                    break;
                case "F":
                    $mensaje = "Calificación: F - Has suspendido.";
                    break;
            }
        }
    } else {
        $mensaje = "Error: Ingresa un número válido.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificación del estudiante</title>
</head>
<body>
    <h2>Evaluación de Calificación</h2>
    <form method="post">
        <label for="nota">Introduce la nota (0-100): </label>
        <input type="number" name="nota" id="nota" min="0" max="100" required>
        <button type="submit">Evaluar</button>
    </form>

    <?php if (!empty($mensaje)) : ?>
        <p><strong><?php echo $mensaje; ?></strong></p>
    <?php endif; ?>
</body>
</html>
