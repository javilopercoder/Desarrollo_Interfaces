// Ejercicio 2: Imprimir números del 1 al 100 en intervalos de 10
let resultado2 = document.getElementById("resultado2");  // Obtiene el párrafo donde se mostrará el resultado
let mensaje = "";

// Usamos un bucle 'while' para imprimir los números
let i = 1;
while (i <= 100) {
    mensaje += i + "<br>";
    i += 10;  // Aumentamos de 10 en 10
}

resultado2.innerHTML = mensaje;
