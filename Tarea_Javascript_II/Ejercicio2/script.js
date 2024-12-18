// Ejercicio 2: Verificar número entero
let numero2 = parseInt(prompt("Ejercicio 2: Introduce un número entero:"));
let resultado2 = document.getElementById("resultado2");  // Obtiene el párrafo donde se mostrará el resultado

// Verificar que el número sea positivo y distinto de cero
if (numero2 > 0) {
    if (numero2 >= 10 && numero2 <= 99) {
        // Número de dos cifras
        if (numero2 % 2 === 0) {
            resultado2.textContent = `El número ${numero2} es de dos cifras y es par.`;
        } else {
            resultado2.textContent = `El número ${numero2} es de dos cifras y es impar.`;
        }
    } else if (numero2 >= 100 && numero2 <= 999) {
        // Número de tres cifras
        let resto = numero2 % 2;
        resultado2.textContent = `El número ${numero2} es de tres cifras. El resto de dividir entre 2 es: ${resto}`;
    } else {
        resultado2.textContent = "El número no tiene dos o tres cifras.";
    }
} else {
    resultado2.textContent = "Error: El número debe ser positivo y distinto de cero.";
}
