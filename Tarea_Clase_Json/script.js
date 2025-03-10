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
  
    render() {
      const cocheDiv = document.createElement("div");
      cocheDiv.classList.add("coche");
      cocheDiv.innerHTML = `
        <h2>${this.marca} ${this.modelo} (${this.anyo})</h2>
        <img src="${this.foto}" alt="Foto de ${this.marca} ${this.modelo}">
        <p>Kilómetros: ${this.kilometros}</p>
        <p>Precio: ${this.precio} €</p>
        <p>Motor: ${this.motor}</p>
        <p>Disponible: ${this.disponible ? "Sí" : "No"}</p>
      `;
      return cocheDiv;
    }
  }
  
  async function cargarCoches() {
    try {
      const response = await fetch("coches.json");
      const cochesData = await response.json();
      const contenedor = document.getElementById("coches-container");
      
      cochesData.forEach(datos => {
        const coche = new Coche(
          datos.marca,
          datos.modelo,
          datos.kilometros,
          datos.anyo,
          datos.precio,
          datos.motor,
          datos.foto,
          datos.disponible
        );
        contenedor.appendChild(coche.render());
      });
    } catch (error) {
      console.error("Error cargando los datos:", error);
    }
  }
  
  document.addEventListener("DOMContentLoaded", cargarCoches);
  