<?php
/**
 * Archivo para gestionar el contador de visitas
 */

// Ruta al archivo que almacenará el contador
$contador_file = __DIR__ . '/../data/contador.txt';

// Función para incrementar y obtener el contador de visitas
function getContadorVisitas() {
    global $contador_file;
    
    // Asegurarse que el directorio existe
    $dir = dirname($contador_file);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Si el archivo no existe, crearlo con valor inicial 0
    if (!file_exists($contador_file)) {
        file_put_contents($contador_file, '0');
        return 1;
    }
    
    // Leer el valor actual
    $contador = (int) file_get_contents($contador_file);
    
    // Incrementar el contador
    $contador++;
    
    // Guardar el nuevo valor
    file_put_contents($contador_file, (string) $contador);
    
    return $contador;
}
?>
