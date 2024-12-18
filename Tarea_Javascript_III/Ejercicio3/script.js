// Ejercicio 3: Crear una tabla con las dimensiones especificadas
function crearTabla() {
    let filas = document.getElementById("filas").value;
    let celdas = document.getElementById("celdas").value;
    let resultado3 = document.getElementById("resultado3");  // Obtiene el contenedor donde se mostrará la tabla

    filas = parseInt(filas);
    celdas = parseInt(celdas);

    let tabla = "<table border='1' cellpadding='5'>";  // Empezamos a construir la tabla

    // Usamos un bucle 'do...while' para crear las filas
    let i = 0;
    do {
        tabla += "<tr>";  // Inicia una nueva fila
        for (let j = 0; j < celdas; j++) {
            tabla += `<td>Celda ${j + 1}</td>`;  // Agrega las celdas
        }
        tabla += "</tr>";  // Cierra la fila
        i++;
    } while (i < filas);

    tabla += "</table>";  // Cierra la tabla
    resultado3.innerHTML = tabla;  // Muestra la tabla en la página
}
