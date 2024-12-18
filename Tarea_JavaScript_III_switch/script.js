function startGame(response) {
    const destino = document.getElementById("destino");
    const message = document.getElementById("message");

    if (response === 'si') {
        if (destino) {
            destino.classList.remove("hidden");
            destino.setAttribute("aria-hidden", "false");
            destino.focus();
        }
        if (message) {
            message.innerHTML = "";
        }
    } else {
        if (message) {
            const img = document.createElement("img");
            img.src = "images/you-miss-it.jpg";
            img.alt = "Imagen de juego no iniciado";
            message.appendChild(img);
        }
        if (destino) {
            destino.setAttribute("aria-hidden", "true");
        }
    }
}

function chooseDestination(destination) {
    const destino = document.getElementById("destino");
    if (destino) {
        destino.classList.add("hidden");
        destino.setAttribute("aria-hidden", "true");
    }

    const section = document.getElementById(destination);
    if (section) {
        section.classList.remove("hidden");
        section.setAttribute("aria-hidden", "false");
        section.focus();
    }
}

function chooseCity(city) {
    const francia = document.getElementById("francia");
    const section = document.getElementById(city);

    if (francia) {
        francia.classList.add("hidden");
        francia.setAttribute("aria-hidden", "true");
    }
    if (section) {
        section.classList.remove("hidden");
        section.setAttribute("aria-hidden", "false");
        section.focus();
    }
}

function chooseNature(nature) {
    const finalMessage = document.getElementById("finalMessage");
    let images = "";

    switch (nature) {
        case 'playa':
            images = `
                <p>Las playas son ideales para relajarse y disfrutar del sol.</p>
                <div class='image-gallery'>
                    <img src='images/playa1.jpg' alt='Vista de playa 1'>
                    <img src='images/playa2.jpg' alt='Vista de playa 2'>
                    <img src='images/playa3.jpg' alt='Vista de playa 3'>
                </div>`;
            break;

        case 'montaña':
            images = `
                <p>Las montañas ofrecen tranquilidad y hermosos paisajes.</p>
                <div class='image-gallery'>
                    <img src='images/montana1.jpg' alt='Vista de montaña 1'>
                    <img src='images/montana2.jpg' alt='Vista de montaña 2'>
                </div>`;
            break;
    }

    if (finalMessage) {
        finalMessage.innerHTML = images;
        finalMessage.classList.remove("hidden");
        finalMessage.setAttribute("aria-hidden", "false");
        finalMessage.setAttribute("role", "alert");
        finalMessage.focus();
    }
}

function chooseSpain(nature) {
    const finalMessage = document.getElementById("finalMessage");
    let message = "";

    if (nature === 'playa') {
        message = `
            <p>España es conocida por sus hermosas playas.</p>
            <div class='image-gallery'>
                <img src='images/playa_spain1.jpg' alt='Playa en España 1'>
                <img src='images/playa_spain2.jpg' alt='Playa en España 2'>
            </div>`;
    } else if (nature === 'montaña') {
        message = `
            <p>España también ofrece hermosos paisajes montañosos.</p>
            <div class='image-gallery'>
                <img src='images/montana_spain1.jpg' alt='Montañas en España 1'>
                <img src='images/montana_spain2.jpg' alt='Montañas en España 2'>
            </div>`;
    }

    if (finalMessage) {
        finalMessage.innerHTML = message;
        finalMessage.classList.remove("hidden");
        finalMessage.setAttribute("aria-hidden", "false");
        finalMessage.setAttribute("role", "alert");
        finalMessage.focus();
    }
}

function choosePortugal(type) {
    const finalMessage = document.getElementById("finalMessage");
    let message = "";

    switch (type) {
        case 'turismo':
            message = `
                <p>Portugal es famoso por su rica historia y arquitectura. ¡Explora Lisboa y más!</p>
                <div class='image-gallery'>
                    <img src='images/lisboa.jpg' alt='Vista de Lisboa, capital de Portugal'>
                </div>`;
            break;

        case 'playa':
            message = `
                <p>El Algarve es conocido por sus impresionantes playas. Perfecto para unas vacaciones de ensueño.</p>
                <div class='image-gallery'>
                    <img src='images/algarve1.jpg' alt='Playa en el Algarve, Portugal 1'>
                    <img src='images/algarve2.jpg' alt='Playa en el Algarve, Portugal 2'>
                </div>`;
            break;

        case 'montaña':
            message = `
                <p>Portugal ofrece vistas impresionantes en Nazaret y Fátima. Perfecto para los amantes de la montaña.</p>
                <div class='image-gallery'>
                    <img src='images/nazare.jpg' alt='Vista de Nazaret, Portugal'>
                    <img src='images/fatima.jpg' alt='Vista de Fátima, Portugal'>
                </div>`;
            break;

        default:
            message = `<p>Opción no válida. Por favor selecciona Turismo, Playa o Montaña.</p>`;
    }

    if (finalMessage) {
        finalMessage.innerHTML = message;
        finalMessage.classList.remove("hidden");
        finalMessage.setAttribute("aria-hidden", "false");
        finalMessage.setAttribute("role", "alert");
        finalMessage.focus();
    }
}