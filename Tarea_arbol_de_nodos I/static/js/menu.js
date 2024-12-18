document.addEventListener("DOMContentLoaded", () => {
    const primerPlato = document.getElementById("primer-plato");
    const segundoPlato = document.getElementById("segundo-plato");
    const postre = document.getElementById("postre");
    const descuentoCheckbox = document.getElementById("descuento");
    const pedirButton = document.getElementById("pedir");
    const imagePrimerPlato = document.getElementById("image-primer-plato");
    const imageSegundoPlato = document.getElementById("image-segundo-plato");
    const imagePostre = document.getElementById("image-postre");

    // Función para actualizar la imagen de acuerdo con la opción seleccionada
    function updateImage(selectElement, imageElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const imagePath = selectedOption.getAttribute("data-image");
        if (imagePath) {
            imageElement.src = imagePath;
            imageElement.alt = selectedOption.text;
        } else {
            imageElement.src = "";
            imageElement.alt = "Selecciona un plato";
        }
    }

    primerPlato.addEventListener("change", () => updateImage(primerPlato, imagePrimerPlato));
    segundoPlato.addEventListener("change", () => updateImage(segundoPlato, imageSegundoPlato));
    postre.addEventListener("change", () => updateImage(postre, imagePostre));

    // Manejar el botón de pedido
    pedirButton.addEventListener("click", () => {
        let total = 0;

        [primerPlato, segundoPlato, postre].forEach(select => {
            const value = parseFloat(select.value) || 0;
            total += value;
        });

        if (descuentoCheckbox.checked) {
            total *= 0.9;
        }

        alert(`El total de tu pedido es: ${total.toFixed(2)}€`);
    });
});
