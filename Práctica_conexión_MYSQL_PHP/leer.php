<link rel="stylesheet" href="styles.css">

<?php
include 'config.php';

$sql = "SELECT productos.id_producto, productos.nombre, productos.precio, productos.cantidad, categorias.nombre as categoria 
        FROM productos 
        JOIN categorias ON productos.id_categoria = categorias.id_categoria";

$result = $conn->query($sql);

echo "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Cantidad</th><th>Categoría</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id_producto']}</td>
            <td>{$row['nombre']}</td>
            <td>{$row['precio']}</td>
            <td>{$row['cantidad']}</td>
            <td>{$row['categoria']}</td>
          </tr>";
}
echo "</table>";

$conn->close();
?>

<!-- Botón de Volver a la página principal -->
<a href="index.html" class="btn-volver">Volver a la página principal</a>