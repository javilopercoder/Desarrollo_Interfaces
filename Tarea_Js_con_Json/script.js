// Clase Coche
class Coche {
    constructor(marca, modelo, kilometros, anyo, precio, motor, foto, disponible) {
        this.marca = marca;
        this.modelo = modelo;
        this.kilometros = kilometros;
        this.anyo = anyo;
        this.precio = precio;
        this.motor = motor;
        this.foto = foto;
        this.disponible = disponible;
    }

    // Método que construye el aspecto HTML de cada coche
    construirHTML() {
        const div = document.createElement("div");
        div.classList.add("coche");

        // Agregar la imagen
        const img = document.createElement("img");
        img.src = this.foto;
        img.alt = `${this.marca} ${this.modelo}`;
        div.appendChild(img);

        // Crear y agregar la información del coche
        const info = document.createElement("div");
        info.classList.add("info");

        info.innerHTML = `
            <strong>${this.marca} ${this.modelo}</strong><br>
            Año: ${this.anyo}<br>
            Kilómetros: ${this.kilometros} km<br>
            Motor: ${this.motor}<br>
            Precio: ${this.precio}€<br>
            Disponible: ${this.disponible ? "Sí" : "No"}
        `;

        div.appendChild(info);
        return div;
    }
}

// Conectar y cargar los datos desde coches.json
fetch('coches.json')
    .then(response => response.json())
    .then(cochesData => {
        const container = document.getElementById("cochesContainer");
        
        cochesData.forEach(cocheData => {
            const coche = new Coche(
                cocheData.marca,
                cocheData.modelo,
                cocheData.kilometros,
                cocheData.anyo,
                cocheData.precio,
                cocheData.motor,
                cocheData.foto,
                cocheData.disponible
            );
            
            // Usar el método para construir y agregar el coche a la página
            container.appendChild(coche.construirHTML());
        });
    })
    .catch(error => console.error('Error al cargar el archivo JSON:', error));
