<?php
// Variables de diferentes tipos
$nombre = "Javier"; // String
$edad = 37; // Entero
$altura = 1.70; // Float
$esEstudiante = true; // Booleano
$amigos = ["Antonio", "José", "Mireia"]; // Array

// Mostrar valores por pantalla
echo "<h2>Variables en PHP</h2>";
echo "Nombre: $nombre <br>";
echo "Edad: $edad <br>";
echo "Altura: $altura metros<br>";
echo "¿Es estudiante?: " . ($esEstudiante ? "Sí" : "No") . "<br>";

// Mostrar array
echo "Amigos: " . implode(", ", $amigos);
?>
