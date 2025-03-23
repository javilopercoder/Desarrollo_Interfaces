document.addEventListener("DOMContentLoaded", function() {
    fetch("peliculas.xml")
        .then(response => response.text())
        .then(str => new window.DOMParser().parseFromString(str, "text/xml"))
        .then(data => {
            const peliculas = data.getElementsByTagName("pelicula");
            const imagenes = document.querySelectorAll(".grid img");
            const descripcion = document.getElementById("descripcion");

            imagenes.forEach(img => {
                img.addEventListener("click", function() {
                    let id = this.getAttribute("data-id");
                    let pelicula = peliculas[id];
                    let videoUrl = this.getAttribute("data-video"); // Obtén el enlace al video

                    let titulo = pelicula.getElementsByTagName("titulo")[0].textContent;
                    let direccion = pelicula.getElementsByTagName("direccion")[0].textContent;
                    let duracion = pelicula.getElementsByTagName("duracion")[0].textContent;
                    let nacionalidad = pelicula.getElementsByTagName("nacionalidad")[0].textContent;
                    let actores = pelicula.getElementsByTagName("actores")[0].textContent;
                    let genero = pelicula.getElementsByTagName("genero")[0].textContent;
                    let sinopsis = pelicula.getElementsByTagName("sinopsis")[0].textContent;

                    descripcion.innerHTML = `
                        <div class="video-container">
                            <iframe width="560" height="315" src="${videoUrl}" frameborder="0" allowfullscreen></iframe>
                        </div>
                        <h2>${titulo}</h2>
                        <p><strong>Dirección:</strong> ${direccion}</p>
                        <p><strong>Duración:</strong> ${duracion}</p>
                        <p><strong>Nacionalidad:</strong> ${nacionalidad}</p>
                        <p><strong>Actores:</strong> ${actores}</p>
                        <p><strong>Género:</strong> ${genero}</p>
                        <p><strong>Sinopsis:</strong> ${sinopsis}</p>
                    `;
                });
            });
        })
        .catch(error => console.error("Error cargando XML:", error));
});
