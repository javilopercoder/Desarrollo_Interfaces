// Ejercicio 1: Operador NOT
let numero1 = parseFloat(prompt("Ejercicio 1: Introduce un número:"));
let resultado = document.getElementById("resultado");  // Obtiene el párrafo donde se mostrará el resultado

// Verificar que el número sea distinto de cero y mayor que cero
if (numero1 !== 0 && numero1 > 0) {
    if (numero1 % 2 === 0) {
        resultado.textContent = `El número ${numero1} es par.`;
    } else {
        resultado.textContent = `El número ${numero1} es impar.`;
    }
} else {
    resultado.textContent = "Error: El número debe ser distinto de cero y mayor que cero.";
}
