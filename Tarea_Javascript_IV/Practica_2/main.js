function arrayPractice2() {
    // Array con nombres de imágenes (solo los nombres de archivo)
    let images = [
        "HTML.svg",
        "Java-Light.svg",
        "JavaScript.svg",
        "MySQL-Light.svg",
        "Python-Light.svg",
        "VIM-Light.svg",
        "VSCode-Light.svg"
    ];

    // Generar una lista amigable de nombres base de las imágenes
    let imageNames = images.map(image => image.split("-")[0].split(".")[0]);

    // Preguntar al usuario qué imagen desea imprimir
    let selectedImage = prompt(
        `¿Qué imagen deseas imprimir? Escribe solo el nombre (por ejemplo: HTML, Java, etc.)\nImágenes disponibles: ${imageNames.join(", ")}`
    );

    // Normalizar la entrada del usuario
    selectedImage = selectedImage.trim().toLowerCase();

    // Buscar la imagen que coincida (sin considerar sufijos ni extensión)
    let foundImage = images.find(image => {
        // Extraer el nombre base de la imagen sin sufijo ni extensión
        let baseName = image.split("-")[0].split(".")[0].toLowerCase();
        return baseName === selectedImage;
    });

    // Validar si se encontró la imagen
    if (foundImage) {
        // Crear y agregar la imagen al documento
        let imgElement = document.createElement("img");
        imgElement.src = `images/${foundImage}`; // Ajustar la ruta a la carpeta "images"
        imgElement.alt = `Imagen de ${selectedImage}`;
        imgElement.style.width = "300px"; // Tamaño opcional para la imagen

        document.body.appendChild(imgElement);
    } else {
        // Mostrar mensaje de error con las imágenes disponibles
        alert(
            `La imagen no existe en el array. Por favor, inténtalo nuevamente.\nImágenes disponibles: ${imageNames.join(", ")}`
        );
    }
}