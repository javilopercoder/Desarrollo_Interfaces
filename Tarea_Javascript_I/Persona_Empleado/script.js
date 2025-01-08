// Clase Persona
class Persona {
    constructor(nombre, apellidos, edad) {
        this.nombre = nombre;
        this.apellidos = apellidos;
        this.edad = edad;
    }

    // Métodos getters y setters
    getNombre() {
        return this.nombre;
    }

    setNombre(nombre) {
        this.nombre = nombre;
    }

    getApellidos() {
        return this.apellidos;
    }

    setApellidos(apellidos) {
        this.apellidos = apellidos;
    }

    getEdad() {
        return this.edad;
    }

    setEdad(edad) {
        this.edad = edad;
    }

    // Método para imprimir datos
    imprimir() {
        return `Persona: ${this.nombre} ${this.apellidos}, Edad: ${this.edad}`;
    }
}

// Clase Empleado que hereda de Persona
class Empleado extends Persona {
    constructor(nombre, apellidos, edad, sueldo, comision) {
        super(nombre, apellidos, edad);
        this.sueldo = sueldo > 0 ? sueldo : 0;
        this.comision = comision > 0 ? comision : 0;
    }

    // Métodos getters y setters para sueldo y comisión
    getSueldo() {
        return this.sueldo;
    }

    setSueldo(sueldo) {
        if (sueldo > 0) {
            this.sueldo = sueldo;
        }
    }

    getComision() {
        return this.comision;
    }

    setComision(comision) {
        if (comision > 0) {
            this.comision = comision;
        }
    }

    // Método para calcular total cobrado
    calcularTotalCobrado() {
        return this.sueldo + this.comision;
    }

    // Método para imprimir datos
    imprimir() {
        return `Empleado: ${this.nombre} ${this.apellidos}, Edad: ${this.edad}, Sueldo: ${this.sueldo} €, Comisión: ${this.comision} €, Total Cobrado: ${this.calcularTotalCobrado()} €`;
    }
}

// Listas para almacenar las personas y empleados
const personas = [];
const empleados = [];

// Función para agregar una Persona
function agregarPersona() {
    const nombre = document.getElementById("nombre").value;
    const apellidos = document.getElementById("apellidos").value;
    const edad = parseInt(document.getElementById("edad").value);

    const nuevaPersona = new Persona(nombre, apellidos, edad);
    personas.push(nuevaPersona);

    mostrarResultados();
    document.getElementById("persona-form").reset();
}

// Función para agregar un Empleado
function agregarEmpleado() {
    const nombre = document.getElementById("nombre-emp").value;
    const apellidos = document.getElementById("apellidos-emp").value;
    const edad = parseInt(document.getElementById("edad-emp").value);
    const sueldo = parseFloat(document.getElementById("sueldo").value);
    const comision = parseFloat(document.getElementById("comision").value);

    const nuevoEmpleado = new Empleado(nombre, apellidos, edad, sueldo, comision);
    empleados.push(nuevoEmpleado);

    mostrarResultados();
    document.getElementById("empleado-form").reset();
}

// Función para mostrar resultados
function mostrarResultados() {
    const resultadoDiv = document.getElementById("resultado");
    resultadoDiv.innerHTML = "";

    const personasHTML = personas.map((p) => `<p>${p.imprimir()}</p>`).join("");
    const empleadosHTML = empleados.map((e) => `<p>${e.imprimir()}</p>`).join("");

    resultadoDiv.innerHTML = `<h3>Personas:</h3>${personasHTML}<h3>Empleados:</h3>${empleadosHTML}`;
}
