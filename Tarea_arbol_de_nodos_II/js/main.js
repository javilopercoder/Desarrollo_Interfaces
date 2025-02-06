const countries = [
    { name: "spain", image: "images/spain.jpeg" },
    { name: "italy", image: "images/italy.jpg" },
    { name: "iceland", image: "images/iceland.jpg" },
    { name: "usa", image: "images/usa.jpg" },
    { name: "portugal", image: "images/portugal.jpg" }
];

function showCountryImage() {
    var selectedCountry = document.getElementById("country-select").value;
    var countryImage = document.getElementById("country-image");
    var country = countries.find(country => country.name === selectedCountry);

    if (country) {
        countryImage.setAttribute("src", country.image);
        countryImage.setAttribute("alt", "Imagen de " + selectedCountry.charAt(0).toUpperCase() + selectedCountry.slice(1));
    } else {
        countryImage.setAttribute("src", "images/mundo.jpg");
        countryImage.setAttribute("alt", "Mundo");
    }
}
