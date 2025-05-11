<link rel="stylesheet" href="styles.css">

<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = $_POST['id_producto'];

    $sql = "DELETE FROM productos WHERE id_producto='$id_producto'";

    if ($conn->query($sql) === TRUE) {
        echo "Producto eliminado correctamente.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>

<form method="POST">
    ID Producto a eliminar: <input type="number" name="id_producto" required><br>
    <input type="submit" value="Eliminar Producto">
</form>

<!-- Botón de Volver atrás -->
<a href="index.php" class="btn-volver">Volver atrás</a>