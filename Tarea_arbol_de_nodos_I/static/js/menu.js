document.addEventListener("DOMContentLoaded", () => {
    const primerPlato = document.getElementById("primer-plato");
    const segundoPlato = document.getElementById("segundo-plato");
    const postre = document.getElementById("postre");
    const imagePrimerPlato = document.getElementById("image-primer-plato");
    const imageSegundoPlato = document.getElementById("image-segundo-plato");
    const imagePostre = document.getElementById("image-postre");

    // Datos de imágenes para el collage de cada sección
    const collageData = {
        "primer-plato": [
            "static/img/lentejas.webp",
            "static/img/ensalada.webp",
            "static/img/sopa.webp",
        ],
        "segundo-plato": [
            "static/img/pollo.webp",
            "static/img/pescado.webp",
            "static/img/vegetales.webp",
        ],
        "postre": [
            "static/img/flan.webp",
            "static/img/helado.webp",
            "static/img/pastel.webp",
        ],
    };

    // Función para actualizar la imagen del plato seleccionado
    function updateImage(selectElement, imageElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const imagePath = selectedOption.getAttribute("data-image");
        if (imagePath) {
            imageElement.src = imagePath;
            imageElement.alt = selectedOption.text;
            imageElement.dataset.hoverDisabled = "true"; // Desactivar hover si hay selección
        } else {
            imageElement.src = "static/img/kitchen-tools.webp";
            imageElement.alt = "Herramientas de cocina";
            imageElement.dataset.hoverDisabled = "false"; // Reactivar hover si no hay selección
        }
    }

    // Función para crear una imagen collage en base a los platos disponibles
    function createCollageImage(images) {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");

        canvas.width = 400;
        canvas.height = 300;

        const imgPromises = images.map((src, index) => {
            return new Promise((resolve) => {
                const img = new Image();
                img.src = src;
                img.onload = () => {
                    const x = (index % 2) * (canvas.width / 2);
                    const y = Math.floor(index / 2) * (canvas.height / 2);
                    ctx.drawImage(img, x, y, canvas.width / 2, canvas.height / 2);
                    resolve();
                };
            });
        });

        return Promise.all(imgPromises).then(() => canvas.toDataURL());
    }

    // Configurar eventos para el hover de cada imagen
    function setupHover(imageElement, sectionKey) {
        const originalSrc = imageElement.src;

        imageElement.addEventListener("mouseenter", async () => {
            if (imageElement.dataset.hoverDisabled === "true") return; // No cambiar si hay selección
            const collageSrc = await createCollageImage(collageData[sectionKey]);
            imageElement.src = collageSrc;
        });

        imageElement.addEventListener("mouseleave", () => {
            if (imageElement.dataset.hoverDisabled === "true") return; // No restaurar si hay selección
            imageElement.src = originalSrc;
        });
    }

    // Configurar las imágenes y eventos de hover
    setupHover(imagePrimerPlato, "primer-plato");
    setupHover(imageSegundoPlato, "segundo-plato");
    setupHover(imagePostre, "postre");

    // Actualizar las imágenes al cambiar la selección
    primerPlato.addEventListener("change", () => updateImage(primerPlato, imagePrimerPlato));
    segundoPlato.addEventListener("change", () => updateImage(segundoPlato, imageSegundoPlato));
    postre.addEventListener("change", () => updateImage(postre, imagePostre));
});