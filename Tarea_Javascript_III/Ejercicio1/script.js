// Ejercicio 1: Pedir nombre y número y luego imprimirlo
let nombre = prompt("Introduce tu nombre:");
let numero = parseInt(prompt("Introduce el número de veces que deseas imprimir el nombre:"));
let resultado1 = document.getElementById("resultado1");  // Obtiene el párrafo donde se mostrará el resultado

// Usamos un bucle 'for' para imprimir el nombre el número de veces indicado
let mensaje = "";
for (let i = 0; i < numero; i++) {
    mensaje += nombre + "<br>";  // Acumulamos el nombre con saltos de línea
}

resultado1.innerHTML = mensaje;
