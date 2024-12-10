// Solicitar al usuario el importe en euros mediante prompt
let euros = parseFloat(prompt("Introduce el importe en Euros:"));

// Verificar si el valor ingresado es un número válido
if (!isNaN(euros)) {
    // Realizar la conversión a libras
    const tasaConversion = 0.87; // 1 euro = 0.87 libras
    let libras = euros * tasaConversion;

    // Mostrar el resultado en el documento HTML
    document.body.innerHTML += `<h2>Resultado de la Conversión</h2>`;
    document.body.innerHTML += `<p>${euros} euros equivalen a ${libras.toFixed(2)} libras.</p>`;
} else {
    // Mostrar mensaje de error si el valor es inválido
    document.body.innerHTML += `<p>Por favor, introduce un valor numérico válido.</p>`;
}
