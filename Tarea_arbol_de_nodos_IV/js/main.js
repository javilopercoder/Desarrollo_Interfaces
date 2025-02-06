const thumbnails = document.querySelectorAll('.thumbnail');
const mainImage = document.querySelector('.main-image');

thumbnails.forEach(thumbnail => {
    thumbnail.addEventListener('click', () => {
        mainImage.src = thumbnail.src;
        mainImage.alt = thumbnail.alt;
    });
});
