<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    $id_categoria = $_POST['id_categoria'];

    $sql = "INSERT INTO productos (nombre, descripcion, precio, cantidad, id_categoria) 
            VALUES ('$nombre', '$descripcion', '$precio', '$cantidad', '$id_categoria')";

    if ($conn->query($sql) === TRUE) {
        echo "Producto agregado correctamente.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$sql_categorias = "SELECT * FROM categorias";
$result_categorias = $conn->query($sql_categorias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Agregar Producto</h1>
    
    <form method="POST">
        Nombre: <input type="text" name="nombre" required><br>
        Descripción: <textarea name="descripcion"></textarea><br>
        Precio: <input type="number" step="0.01" name="precio" required><br>
        Cantidad: <input type="number" name="cantidad" required><br>
        Categoría: 
        <select name="id_categoria" required>
            <option value="">Selecciona una categoría</option>
            <?php
            // Mostrar las categorías disponibles
            if ($result_categorias->num_rows > 0) {
                while($row = $result_categorias->fetch_assoc()) {
                    echo "<option value='" . $row['id_categoria'] . "'>" . $row['nombre'] . "</option>";
                }
            }
            ?>
        </select><br>
        <input type="submit" value="Agregar Producto">
    </form>
</body>
</html>

<?php
$conn->close();
?>

<!-- Botón de Volver atrás -->
<a href="index.php" class="btn-volver">Volver atrás</a>