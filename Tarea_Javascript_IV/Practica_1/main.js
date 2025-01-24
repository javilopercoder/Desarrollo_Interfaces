function arrayPractice1() {
    // Pedir número de celdas
    let numCeldas = parseInt(prompt("Introduce el número de celdas del array:"));

    if (isNaN(numCeldas) || numCeldas <= 0) {
        alert("Por favor, introduce un número válido mayor a 0.");
        return;
    }

    // Crear el array y llenarlo con su posición
    let array = [];
    for (let i = 0; i < numCeldas; i++) {
        array[i] = i;
    }

    // Obtener el contenedor de resultados
    let output = document.getElementById("output");
    output.innerHTML = "<h2>Resultados:</h2>"; // Limpiar y añadir título

    // Imprimir el array con un bucle for
    output.innerHTML += "<p><strong>Con bucle for:</strong> ";
    for (let i = 0; i < array.length; i++) {
        output.innerHTML += array[i] + " ";
    }
    output.innerHTML += "</p>";

    // Imprimir el array con un bucle while
    output.innerHTML += "<p><strong>Con bucle while:</strong> ";
    let i = 0;
    while (i < array.length) {
        output.innerHTML += array[i] + " ";
        i++;
    }
    output.innerHTML += "</p>";
}