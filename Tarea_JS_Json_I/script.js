// Cargar el archivo JSON
fetch('personas.json')
    .then(response => response.json())
    .then(personas => {
        function calcularEdad(anioNacimiento) {
            const anioActual = new Date().getFullYear();
            return anioActual - anioNacimiento;
        }

        function mostrarPersonas() {
            const container = document.getElementById("personasContainer");
            personas.forEach(persona => {
                const div = document.createElement("div");
                div.classList.add("card");
                
                const img = document.createElement("img");
                const info = document.createElement("div");
                info.classList.add("info");
                
                img.src = persona.foto;
                img.alt = `${persona.nombre} ${persona.apellidos}`;
                img.style.width = "100px"; // Ajusta el tamaño de la imagen si es necesario
                
                info.innerHTML = `
                    <strong>${persona.nombre} ${persona.apellidos}</strong><br>
                    Año de Nacimiento: ${persona.anioNacimiento}<br>
                    Edad: ${calcularEdad(persona.anioNacimiento)}<br>
                    Curso: ${persona.curso}
                `;
                
                div.appendChild(img);
                div.appendChild(info);
                container.appendChild(div);
            });
        }

        function mostrarTabla() {
            const tbody = document.querySelector("#tablaPersonas tbody");
            personas.forEach(persona => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${persona.nombre}</td>
                    <td>${persona.apellidos}</td>
                    <td>${persona.anioNacimiento}</td>
                    <td>${calcularEdad(persona.anioNacimiento)}</td>
                    <td>${persona.curso}</td>
                    <td><img src="${persona.foto}" alt="${persona.nombre} ${persona.apellidos}" width="50"></td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Llamar a las funciones para mostrar los datos
        mostrarPersonas();
        mostrarTabla();
    })
    .catch(error => console.error('Error al cargar el archivo JSON:', error));
