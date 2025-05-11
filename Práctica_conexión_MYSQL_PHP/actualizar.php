<link rel="stylesheet" href="styles.css">

<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = $_POST['id_producto'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];

    $sql = "UPDATE productos SET precio='$precio', cantidad='$cantidad' WHERE id_producto='$id_producto'";

    if ($conn->query($sql) === TRUE) {
        echo "Producto actualizado correctamente.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>

<form method="POST">
    ID Producto: <input type="number" name="id_producto" required><br>
    Nuevo Precio: <input type="number" step="0.01" name="precio" required><br>
    Nueva Cantidad: <input type="number" name="cantidad" required><br>
    <input type="submit" value="Actualizar Producto">
</form>

<!-- Botón de Volver atrás -->
<a href="index.php" class="btn-volver">Volver atrás</a>