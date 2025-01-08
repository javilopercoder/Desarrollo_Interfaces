// Clase principal: Electrodoméstico
class Electrodomestico {
    constructor(nombre, precioBase, peso) {
        this.nombre = nombre;
        this.precioBase = precioBase;
        this.peso = peso;
    }

    precioFinal() {
        return this.precioBase;
    }
}

// Subclase: Televisión
class Television extends Electrodomestico {
    constructor(nombre, precioBase, peso, resolucion, sintonizadorTDT) {
        super(nombre, precioBase, peso);
        this.resolucion = resolucion;
        this.sintonizadorTDT = sintonizadorTDT;
    }

    precioFinal() {
        let precio = super.precioFinal();
        if (this.resolucion > 40) {
            precio += precio * 0.3; // Incrementa un 30%
        }
        if (this.sintonizadorTDT) {
            precio += 50; // Incrementa 50€
        }
        return precio;
    }
}

// Función para calcular el precio total
function calcularPrecio() {
    // Capturar las cantidades seleccionadas
    const lavadoras = parseInt(document.getElementById("lavadora").value);
    const microondas = parseInt(document.getElementById("microondas").value);
    const televisores = parseInt(document.getElementById("television").value);
    const resolucion = parseInt(document.getElementById("resolucion").value);
    const tdt = document.getElementById("tdt").value === "true";

    // Crear objetos y calcular
    const electrodomesticos = [];
    for (let i = 0; i < lavadoras; i++) {
        electrodomesticos.push(new Electrodomestico("Lavadora", 200, 30));
    }
    for (let i = 0; i < microondas; i++) {
        electrodomesticos.push(new Electrodomestico("Microondas", 150, 10));
    }
    for (let i = 0; i < televisores; i++) {
        const pesoTV = resolucion > 40 ? 15 : 10;
        electrodomesticos.push(new Television("Televisión", 300, pesoTV, resolucion, tdt));
    }

    // Calcular totales
    let totalPrecio = 0;
    let totalPeso = 0;
    const resultados = electrodomesticos.map((e) => {
        const precioFinal = e.precioFinal();
        totalPrecio += precioFinal;
        totalPeso += e.peso;

        if (e instanceof Television) {
            return `<p>${e.nombre} (${e.resolucion}" TDT ${e.sintonizadorTDT ? "Sí" : "No"}): Peso = ${e.peso}kg, Precio final = ${precioFinal.toFixed(
                2
            )} €</p>`;
        } else {
            return `<p>${e.nombre}: Peso = ${e.peso}kg, Precio final = ${precioFinal.toFixed(2)} €</p>`;
        }
    });

    // Verificar si se añade incremento por peso total
    if (totalPeso > 30) {
        totalPrecio += 50;
        resultados.push(`<p><strong>Se añaden 50 € por superar los 30kg de peso total.</strong></p>`);
    }

    // Mostrar resultados
    resultados.push(`<p><strong>Total: ${totalPrecio.toFixed(2)} €</strong></p>`);
    document.getElementById("resultado").innerHTML = resultados.join("");
}
