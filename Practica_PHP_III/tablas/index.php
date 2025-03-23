<?php
$mensaje = "";
$resultado = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["numero"]) && is_numeric($_POST["numero"])) {
        $numero = (int) $_POST["numero"];

        // Validar que sea un entero positivo
        if ($numero <= 0) {
            $mensaje = "Error: Ingresa un número entero positivo.";
        } else {
            // Generar la tabla de multiplicación
            $resultado = "<h3>Tabla de multiplicar del $numero</h3><ul>";
            for ($i = 1; $i <= 10; $i++) {
                $multiplicacion = $numero * $i;
                
                // Resaltar múltiplos de 5
                if ($multiplicacion % 5 == 0) {
                    $resultado .= "<li><strong>$numero x $i = $multiplicacion (Múltiplo de 5)</strong></li>";
                } else {
                    $resultado .= "<li>$numero x $i = $multiplicacion</li>";
                }
            }
            $resultado .= "</ul>";
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
    <title>Tabla de Multiplicar</title>
</head>
<body>
    <h2>Generador de Tablas de Multiplicar</h2>
    <form method="post">
        <label for="numero">Introduce un número entero positivo:</label>
        <input type="number" name="numero" id="numero" min="1" required>
        <button type="submit">Generar Tabla</button>
    </form>

    <?php 
    if (!empty($mensaje)) {
        echo "<p><strong>$mensaje</strong></p>";
    }
    if (!empty($resultado)) {
        echo $resultado;
    }
    ?>
</body>
</html>
