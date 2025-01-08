// Clase base Vehículo
class Vehiculo {
    constructor(matricula, marca, modelo, color, numeroPuertas, potencia) {
        this.matricula = matricula;
        this.marca = marca;
        this.modelo = modelo;
        this.color = color;
        this.numeroPuertas = numeroPuertas;
        this.potencia = potencia;
    }

    imprimirDatos() {
        return `Matrícula: ${this.matricula}, Marca: ${this.marca}, Modelo: ${this.modelo}, Color: ${this.color}, Puertas: ${this.numeroPuertas}, Potencia: ${this.potencia} CV`;
    }
}

// Subclase Turismo
class Turismo extends Vehiculo {
    constructor(matricula, marca, modelo, color, numeroPuertas, potencia, numeroPlazas) {
        super(matricula, marca, modelo, color, numeroPuertas, potencia);
        this.numeroPlazas = numeroPlazas;
    }

    imprimirDatos() {
        return `${super.imprimirDatos()}, Plazas: ${this.numeroPlazas}`;
    }
}

// Subclase Deportivo
class Deportivo extends Vehiculo {
    constructor(matricula, marca, modelo, color, numeroPuertas, potencia, descapotable) {
        super(matricula, marca, modelo, color, numeroPuertas, potencia);
        this.descapotable = descapotable;
    }

    imprimirDatos() {
        return `${super.imprimirDatos()}, Descapotable: ${this.descapotable ? "Sí" : "No"}`;
    }
}

// Lista de vehículos
const vehiculos = [];

// Función para mostrar u ocultar campos según el tipo de vehículo
document.getElementById("tipo").addEventListener("change", (event) => {
    const tipo = event.target.value;
    document.getElementById("turismo-fields").style.display = tipo === "Turismo" ? "block" : "none";
    document.getElementById("deportivo-fields").style.display = tipo === "Deportivo" ? "block" : "none";
});

// Función para agregar un vehículo
function agregarVehiculo() {
    const tipo = document.getElementById("tipo").value;
    const matricula = document.getElementById("matricula").value;
    const marca = document.getElementById("marca").value;
    const modelo = document.getElementById("modelo").value;
    const color = document.getElementById("color").value;
    const numeroPuertas = parseInt(document.getElementById("puertas").value);
    const potencia = parseInt(document.getElementById("potencia").value);

    if (tipo === "") {
        alert("Por favor, selecciona un tipo de vehículo.");
        return;
    }

    let vehiculo;

    if (tipo === "Turismo") {
        const numeroPlazas = parseInt(document.getElementById("plazas").value);
        if (isNaN(numeroPlazas) || numeroPlazas <= 0) {
            alert("Por favor, ingresa un número válido de plazas.");
            return;
        }
        vehiculo = new Turismo(matricula, marca, modelo, color, numeroPuertas, potencia, numeroPlazas);
    } else if (tipo === "Deportivo") {
        const descapotable = document.getElementById("descapotable").value === "true";
        vehiculo = new Deportivo(matricula, marca, modelo, color, numeroPuertas, potencia, descapotable);
    }

    vehiculos.push(vehiculo);
    mostrarResultados();
    document.getElementById("vehiculo-form").reset();
    document.getElementById("turismo-fields").style.display = "none";
    document.getElementById("deportivo-fields").style.display = "none";
}

// Función para mostrar resultados
function mostrarResultados() {
    const resultadoDiv = document.getElementById("resultado");
    resultadoDiv.innerHTML = vehiculos.map((v) => `<p>${v.imprimirDatos()}</p>`).join("");
}
