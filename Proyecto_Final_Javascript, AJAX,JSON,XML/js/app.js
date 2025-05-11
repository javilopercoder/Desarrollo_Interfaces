// Variables globales
let datosHoroscopos = null;
let signos = [];
let animalesChinos = [];

// Función que se ejecuta cuando el documento está listo
document.addEventListener('DOMContentLoaded', function() {
    // Limpiar el contenido existente y mostrar la consulta inicial
    document.body.innerHTML = '';
    mostrarPreguntaInicial();
});

// Función para mostrar la pregunta inicial
function mostrarPreguntaInicial() {
    // Crear el contenedor principal
    const container = document.createElement('div');
    container.className = 'inicio-container';
    
    // Crear el título
    const titulo = document.createElement('h1');
    titulo.textContent = '¿Quieres conocer tu horóscopo?';
    
    // Crear botones
    const btnSi = document.createElement('button');
    btnSi.textContent = 'Sí, quiero conocer mi horóscopo';
    btnSi.className = 'btn';
    btnSi.addEventListener('click', cargarDatosHoroscopos);
    
    const btnNo = document.createElement('button');
    btnNo.textContent = 'No, gracias';
    btnNo.className = 'btn';
    btnNo.addEventListener('click', () => {
        document.body.innerHTML = '';
        const mensaje = document.createElement('h2');
        mensaje.textContent = 'Gracias por tu visita';
        mensaje.style.textAlign = 'center';
        mensaje.style.marginTop = '100px';
        document.body.appendChild(mensaje);
    });
    
    // Añadir elementos al DOM
    container.appendChild(titulo);
    container.appendChild(btnSi);
    container.appendChild(btnNo);
    document.body.appendChild(container);
}

// Función para cargar los datos XML usando AJAX
function cargarDatosHoroscopos() {
    // Indicar al usuario que se está cargando
    document.body.innerHTML = '';
    const cargando = document.createElement('h2');
    cargando.textContent = 'Cargando datos...';
    cargando.style.textAlign = 'center';
    cargando.style.marginTop = '100px';
    document.body.appendChild(cargando);
    
    // Crear una solicitud XMLHttpRequest
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'data/horoscopos.xml', true);
    
    // Configurar el manejador de eventos para cuando la solicitud se complete
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Analizar el XML
            datosHoroscopos = xhr.responseXML;
            procesarDatosXML();
            mostrarInterfazHoroscopos();
        } else {
            mostrarError('Error al cargar los datos de horóscopos');
        }
    };
    
    // Configurar el manejador de errores
    xhr.onerror = function() {
        mostrarError('Error de red al intentar cargar los datos');
    };
    
    // Enviar la solicitud
    xhr.send();
}

// Función para procesar los datos XML
function procesarDatosXML() {
    // Procesar signos zodiacales
    const signosXML = datosHoroscopos.querySelectorAll('signos_zodiacales > signo');
    signos = [];
    
    signosXML.forEach(signoXML => {
        const signo = {
            nombre: signoXML.querySelector('nombre').textContent,
            fechaInicio: signoXML.querySelector('fecha_inicio').textContent,
            fechaFin: signoXML.querySelector('fecha_fin').textContent,
            imagen: signoXML.querySelector('imagen').textContent,
            prediccion: signoXML.querySelector('prediccion').textContent.trim(),
            caracteristicas: signoXML.querySelector('caracteristicas').textContent.trim()
        };
        signos.push(signo);
    });
    
    // Procesar horóscopo chino
    const animalesXML = datosHoroscopos.querySelectorAll('horoscopo_chino > animal');
    animalesChinos = [];
    
    animalesXML.forEach(animalXML => {
        const animal = {
            nombre: animalXML.querySelector('nombre').textContent,
            anios: animalXML.querySelector('anios').textContent.split(', '),
            imagen: animalXML.querySelector('imagen').textContent,
            prediccion: animalXML.querySelector('prediccion').textContent.trim(),
            caracteristicas: animalXML.querySelector('caracteristicas').textContent.trim()
        };
        animalesChinos.push(animal);
    });
}

// Función para mostrar la interfaz principal de horóscopos
function mostrarInterfazHoroscopos() {
    // Limpiar el contenido existente
    document.body.innerHTML = '';
    
    // Crear contenedor principal
    const mainContainer = document.createElement('div');
    mainContainer.className = 'horoscopo-container';
    
    // Título principal
    const titulo = document.createElement('h1');
    titulo.className = 'titulo-principal';
    titulo.textContent = 'Horóscopo y signos del zodíaco';
    mainContainer.appendChild(titulo);
    
    // Sección para consultar el horóscopo por fecha
    const consultaContainer = crearSeccionConsulta();
    mainContainer.appendChild(consultaContainer);
    
    // Sección de signos del zodíaco
    const signosContainer = document.createElement('div');
    signosContainer.innerHTML = '<h2>Signos del Zodíaco</h2>';
    
    // Grid para mostrar todos los signos
    const signosGrid = document.createElement('div');
    signosGrid.className = 'signos-grid';
    
    // Añadir cada signo a la cuadrícula
    signos.forEach(signo => {
        const signoCard = document.createElement('div');
        signoCard.className = 'signo-card';
        
        const fechas = `${signo.fechaInicio.split('-').reverse().join('/')} - ${signo.fechaFin.split('-').reverse().join('/')}`;
        
        signoCard.innerHTML = `
            <img src="assets/${signo.imagen}" alt="${signo.nombre}">
            <h3>${signo.nombre}</h3>
            <p>${fechas}</p>
        `;
        
        // Añadir evento para mostrar detalles al hacer clic
        signoCard.addEventListener('click', () => mostrarDetalleSigno(signo));
        
        signosGrid.appendChild(signoCard);
    });
    
    signosContainer.appendChild(signosGrid);
    mainContainer.appendChild(signosContainer);
    
    // Sección de horóscopo chino
    const chinoContainer = document.createElement('div');
    chinoContainer.className = 'horoscopo-chino-container';
    chinoContainer.innerHTML = '<h2>El Horóscopo chino</h2>';
    
    // Grid para mostrar todos los animales
    const animalesGrid = document.createElement('div');
    animalesGrid.className = 'animales-grid';
    
    // Añadir cada animal a la cuadrícula
    animalesChinos.forEach(animal => {
        const animalCard = document.createElement('div');
        animalCard.className = 'animal-card';
        
        animalCard.innerHTML = `
            <img src="assets/${animal.imagen}" alt="${animal.nombre}">
            <h3>${animal.nombre}</h3>
        `;

        // Añadir evento para mostrar detalles al hacer clic
        animalCard.addEventListener('click', () => mostrarDetalleAnimal(animal));
        
        animalesGrid.appendChild(animalCard);
    });
    
    chinoContainer.appendChild(animalesGrid);
    mainContainer.appendChild(chinoContainer);
    
    // Añadir todo al body
    document.body.appendChild(mainContainer);
}

// Función para crear la sección de consulta de horóscopo
function crearSeccionConsulta() {
    const container = document.createElement('div');
    container.className = 'consulta-container';
    
    // Título de la sección
    const titulo = document.createElement('h2');
    titulo.textContent = '¿Cuál es mi signo del zodíaco? ¿Y mi horóscopo chino?';
    container.appendChild(titulo);
    
    // Formulario
    const form = document.createElement('div');
    form.className = 'consulta-form';
    
    // Label para la fecha
    const label = document.createElement('label');
    label.textContent = 'Introduce tu fecha de nacimiento:';
    form.appendChild(label);
    
    // Input para la fecha
    const inputFecha = document.createElement('input');
    inputFecha.type = 'date';
    inputFecha.id = 'fecha-nacimiento';
    
    // Establecer fecha mínima y máxima razonable
    inputFecha.min = '1900-01-01';
    inputFecha.max = new Date().toISOString().split('T')[0]; // Fecha actual
    
    // Evento al cambiar la fecha
    inputFecha.addEventListener('change', consultarHoroscopos);
    
    form.appendChild(inputFecha);
    container.appendChild(form);
    
    // Div para mostrar el resultado
    const resultado = document.createElement('div');
    resultado.className = 'consulta-resultado';
    resultado.id = 'resultado-consulta';
    container.appendChild(resultado);
    
    return container;
}

// Función para consultar los horóscopos según la fecha de nacimiento
function consultarHoroscopos() {
    const fechaInput = document.getElementById('fecha-nacimiento');
    const resultadoDiv = document.getElementById('resultado-consulta');
    
    if (fechaInput.value) {
        const fecha = new Date(fechaInput.value);
        
        // Obtener el día y mes para el signo zodiacal
        const dia = fecha.getDate();
        const mes = fecha.getMonth() + 1; // getMonth() devuelve 0-11
        
        // Obtener el año para el horóscopo chino
        const anio = fecha.getFullYear();
        
        // Determinar el signo zodiacal
        const signo = determinarSignoZodiacal(dia, mes);
        
        // Determinar el animal del horóscopo chino
        const animal = determinarAnimalChino(anio);
        
        // Mostrar los resultados
        resultadoDiv.innerHTML = `
            <h3>Tus Resultados</h3>
            <p>Tu signo del zodíaco es: <strong>${signo.nombre}</strong></p>
            <p>Tu horóscopo chino es: <strong>${animal.nombre}</strong></p>
        `;
        
        // Resaltar el signo en la grid
        resaltarSigno(signo.nombre);
        
        // Resaltar el animal en la grid
        resaltarAnimalChino(animal.nombre);
        
        // Mostrar automáticamente los detalles de ambos
        mostrarAmbosDetalles(signo, animal);
        
    } else {
        resultadoDiv.innerHTML = '<p>Por favor, selecciona una fecha válida</p>';
    }
}

// Función para determinar el signo zodiacal según el día y mes
function determinarSignoZodiacal(dia, mes) {
    // Formatear día y mes para comparar con las fechas del XML
    const diaFormateado = dia.toString().padStart(2, '0');
    const mesFormateado = mes.toString().padStart(2, '0');
    const fechaActual = `${diaFormateado}-${mesFormateado}`;
    
    for (const signo of signos) {
        // Extraer día y mes de las fechas de inicio y fin
        const fechaInicio = signo.fechaInicio; // formato: "DD-MM"
        const fechaFin = signo.fechaFin; // formato: "DD-MM"
        
        // Comprobar si la fecha está en el rango del signo
        if (estaEnRangoFecha(fechaActual, fechaInicio, fechaFin)) {
            return signo;
        }
    }
    
    // Por defecto, devolver el primer signo (no debería ocurrir)
    return signos[0];
}

// Función para comprobar si una fecha está en un rango
function estaEnRangoFecha(fecha, inicio, fin) {
    // Extraer componentes de fechas (día y mes)
    const [diaFecha, mesFecha] = fecha.split('-').map(Number);
    const [diaInicio, mesInicio] = inicio.split('-').map(Number);
    const [diaFin, mesFin] = fin.split('-').map(Number);
    
    // Caso especial: Capricornio (a caballo entre dos años)
    if (mesInicio > mesFin) {
        // Si estamos en el primer año del ciclo (diciembre)
        if (mesFecha === mesInicio && diaFecha >= diaInicio) {
            return true;
        }
        // Si estamos en el segundo año del ciclo (enero)
        if (mesFecha === mesFin && diaFecha <= diaFin) {
            return true;
        }
        return false;
    }
    
    // Caso normal: el resto de signos
    if (mesFecha > mesInicio || (mesFecha === mesInicio && diaFecha >= diaInicio)) {
        if (mesFecha < mesFin || (mesFecha === mesFin && diaFecha <= diaFin)) {
            return true;
        }
    }
    
    return false;
}

// Función para determinar el animal del horóscopo chino según el año
function determinarAnimalChino(anio) {
    for (const animal of animalesChinos) {
        if (animal.anios.includes(anio.toString())) {
            return animal;
        }
    }
    
    // Si no se encuentra exactamente, calculamos el animal por el ciclo de 12 años
    const ciclo = (anio - 4) % 12; // El año 4 d.C. fue año de la Rata
    return animalesChinos[ciclo];
}

// Función para resaltar el signo seleccionado
function resaltarSigno(nombreSigno) {
    // Quitar resaltado anterior
    const signosCards = document.querySelectorAll('.signo-card');
    signosCards.forEach(card => {
        card.style.transform = '';
        card.style.boxShadow = '';
        card.style.backgroundColor = '';
    });
    
    // Resaltar el signo actual
    signosCards.forEach(card => {
        if (card.querySelector('h3').textContent === nombreSigno) {
            card.style.transform = 'scale(1.05)';
            card.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.2)';
            card.style.backgroundColor = '#f0e6ff';
        }
    });
}

// Función para resaltar el animal del horóscopo chino
function resaltarAnimalChino(nombreAnimal) {
    // Quitar resaltado anterior
    const animalCards = document.querySelectorAll('.animal-card');
    animalCards.forEach(card => {
        card.classList.remove('animal-destacado');
    });
    
    // Resaltar el animal actual
    animalCards.forEach(card => {
        if (card.querySelector('h3').textContent === nombreAnimal) {
            card.classList.add('animal-destacado');
        }
    });
}

// Función para mostrar los detalles de un signo
function mostrarDetalleSigno(signo) {
    // Cerrar cualquier ventana de detalle abierta anteriormente
    cerrarVentanasDetalles();
    
    // Crear el overlay para el fondo
    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    overlay.id = 'overlay';
    
    // Al hacer clic en el overlay, cerrar la ventana
    overlay.addEventListener('click', () => {
        cerrarVentanasDetalles();
    });
    
    // Crear un elemento detalle
    const detalleContainer = document.createElement('div');
    detalleContainer.className = 'detalle-container';
    detalleContainer.id = 'detalle-signo';
    
    // Evitar que los clics dentro del detalle cierren la ventana
    detalleContainer.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    
    // Parte de la imagen
    const imagenDiv = document.createElement('div');
    imagenDiv.className = 'detalle-imagen';
    imagenDiv.innerHTML = `<img src="assets/${signo.imagen}" alt="${signo.nombre}">`;
    
    // Parte del texto
    const textoDiv = document.createElement('div');
    textoDiv.className = 'detalle-texto';
    textoDiv.innerHTML = `
        <h3>${signo.nombre}</h3>
        <p>${signo.fechaInicio.split('-').reverse().join('/')} - ${signo.fechaFin.split('-').reverse().join('/')}</p>
        <h4>PREDICCIÓN DE HOY</h4>
        <div class="prediccion">${signo.prediccion}</div>
        <h4>CARACTERÍSTICAS DEL SIGNO</h4>
        <p>${signo.caracteristicas}</p>
    `;
    
    // Botón para cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.textContent = 'Cerrar';
    btnCerrar.className = 'btn';
    btnCerrar.addEventListener('click', () => {
        cerrarVentanasDetalles();
    });
    textoDiv.appendChild(btnCerrar);
    
    // Añadir al contenedor
    detalleContainer.appendChild(imagenDiv);
    detalleContainer.appendChild(textoDiv);
    
    // Añadir al cuerpo del documento
    document.body.appendChild(overlay);
    document.body.appendChild(detalleContainer);
}

// Función para mostrar los detalles de un animal del horóscopo chino
function mostrarDetalleAnimal(animal) {
    // Cerrar cualquier ventana de detalle abierta anteriormente
    cerrarVentanasDetalles();
    
    // Crear el overlay para el fondo
    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    overlay.id = 'overlay';
    
    // Al hacer clic en el overlay, cerrar la ventana
    overlay.addEventListener('click', () => {
        cerrarVentanasDetalles();
    });
    
    // Crear un elemento detalle
    const detalleContainer = document.createElement('div');
    detalleContainer.className = 'detalle-container';
    detalleContainer.id = 'detalle-animal';
    
    // Evitar que los clics dentro del detalle cierren la ventana
    detalleContainer.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    
    // Parte de la imagen
    const imagenDiv = document.createElement('div');
    imagenDiv.className = 'detalle-imagen';
    imagenDiv.innerHTML = `<img src="assets/${animal.imagen}" alt="${animal.nombre}">`;
    
    // Parte del texto
    const textoDiv = document.createElement('div');
    textoDiv.className = 'detalle-texto';
    
    // Formatear años para mejor visualización
    const aniosFormateados = animal.anios.join(', ');
    
    textoDiv.innerHTML = `
        <h3>${animal.nombre}</h3>
        <p><strong>Años:</strong> ${aniosFormateados}</p>
        <h4>PREDICCIÓN ANUAL</h4>
        <div class="prediccion">${animal.prediccion}</div>
        <h4>CARACTERÍSTICAS</h4>
        <p>${animal.caracteristicas}</p>
    `;
    
    // Botón para cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.textContent = 'Cerrar';
    btnCerrar.className = 'btn';
    btnCerrar.addEventListener('click', () => {
        cerrarVentanasDetalles();
    });
    textoDiv.appendChild(btnCerrar);
    
    // Añadir al contenedor
    detalleContainer.appendChild(imagenDiv);
    detalleContainer.appendChild(textoDiv);
    
    // Añadir al cuerpo del documento
    document.body.appendChild(overlay);
    document.body.appendChild(detalleContainer);
}

// Función para cerrar todas las ventanas de detalles
function cerrarVentanasDetalles() {
    // Eliminar overlay si existe
    const overlay = document.getElementById('overlay');
    if (overlay) {
        overlay.remove();
    }
    
    // Eliminar ventanas de detalles si existen
    const detalleSigno = document.getElementById('detalle-signo');
    if (detalleSigno) {
        detalleSigno.remove();
    }
    
    const detalleAnimal = document.getElementById('detalle-animal');
    if (detalleAnimal) {
        detalleAnimal.remove();
    }
}

// Función para mostrar los detalles de ambos signos cuando se selecciona una fecha
function mostrarAmbosDetalles(signo, animal) {
    // Cerrar cualquier ventana de detalle abierta anteriormente
    cerrarVentanasDetalles();
    
    // Crear el overlay para el fondo
    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    overlay.id = 'overlay';
    
    // Al hacer clic en el overlay, cerrar las ventanas
    overlay.addEventListener('click', () => {
        cerrarVentanasDetalles();
    });
    
    // Añadir el overlay al cuerpo del documento
    document.body.appendChild(overlay);
    
    // Determinar si estamos en una pantalla ancha o estrecha
    const esDispositivoAncho = window.innerWidth > 1200;
    
    // Crear y mostrar el detalle del signo zodiacal
    const detalleSigno = document.createElement('div');
    detalleSigno.className = 'detalle-container';
    detalleSigno.id = 'detalle-signo';
    
    if (esDispositivoAncho) {
        detalleSigno.style.left = '25%';
    } else {
        detalleSigno.style.top = '25%';
    }
    
    // Evitar que los clics dentro del detalle cierren la ventana
    detalleSigno.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    
    // Parte de la imagen del signo
    const imagenSignoDiv = document.createElement('div');
    imagenSignoDiv.className = 'detalle-imagen';
    imagenSignoDiv.innerHTML = `<img src="assets/${signo.imagen}" alt="${signo.nombre}">`;
    
    // Parte del texto del signo
    const textoSignoDiv = document.createElement('div');
    textoSignoDiv.className = 'detalle-texto';
    textoSignoDiv.innerHTML = `
        <h3>${signo.nombre}</h3>
        <p>${signo.fechaInicio.split('-').reverse().join('/')} - ${signo.fechaFin.split('-').reverse().join('/')}</p>
        <h4>PREDICCIÓN DE HOY</h4>
        <div class="prediccion">${signo.prediccion}</div>
        <h4>CARACTERÍSTICAS DEL SIGNO</h4>
        <p>${signo.caracteristicas}</p>
    `;
    
    // Añadir al contenedor del signo
    detalleSigno.appendChild(imagenSignoDiv);
    detalleSigno.appendChild(textoSignoDiv);
    
    // Crear y mostrar el detalle del animal chino
    const detalleAnimal = document.createElement('div');
    detalleAnimal.className = 'detalle-container';
    detalleAnimal.id = 'detalle-animal';
    
    if (esDispositivoAncho) {
        detalleAnimal.style.left = '75%';
    } else {
        detalleAnimal.style.top = '75%';
    }
    
    // Evitar que los clics dentro del detalle cierren la ventana
    detalleAnimal.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    
    // Parte de la imagen del animal
    const imagenAnimalDiv = document.createElement('div');
    imagenAnimalDiv.className = 'detalle-imagen';
    imagenAnimalDiv.innerHTML = `<img src="assets/${animal.imagen}" alt="${animal.nombre}">`;
    
    // Parte del texto del animal
    const textoAnimalDiv = document.createElement('div');
    textoAnimalDiv.className = 'detalle-texto';
    
    // Formatear años para mejor visualización
    const aniosFormateados = animal.anios.join(', ');
    
    textoAnimalDiv.innerHTML = `
        <h3>${animal.nombre}</h3>
        <p><strong>Años:</strong> ${aniosFormateados}</p>
        <h4>PREDICCIÓN ANUAL</h4>
        <div class="prediccion">${animal.prediccion}</div>
        <h4>CARACTERÍSTICAS</h4>
        <p>${animal.caracteristicas}</p>
    `;
    
    // Añadir al contenedor del animal
    detalleAnimal.appendChild(imagenAnimalDiv);
    detalleAnimal.appendChild(textoAnimalDiv);
    
    // Botón para cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.textContent = 'Cerrar';
    btnCerrar.className = 'btn';
    btnCerrar.style.position = 'fixed';
    btnCerrar.style.top = '20px';
    btnCerrar.style.right = '20px';
    btnCerrar.style.zIndex = '1001';
    btnCerrar.addEventListener('click', () => {
        cerrarVentanasDetalles();
    });
    
    // Añadir todo al cuerpo del documento
    document.body.appendChild(detalleSigno);
    document.body.appendChild(detalleAnimal);
    document.body.appendChild(btnCerrar);
}

// Función para mostrar error
function mostrarError(mensaje) {
    document.body.innerHTML = '';
    const error = document.createElement('div');
    error.className = 'error-container';
    error.innerHTML = `
        <h2>Error</h2>
        <p>${mensaje}</p>
        <button class="btn" onclick="location.reload()">Reintentar</button>
    `;
    document.body.appendChild(error);
}
