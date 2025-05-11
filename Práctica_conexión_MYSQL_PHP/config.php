<link rel="stylesheet" href="styles.css">

<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "inventario_tienda";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>