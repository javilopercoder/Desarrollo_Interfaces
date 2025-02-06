const gallery = document.getElementById('gallery');
const items = [
    { image: 'images/Github-Dark.svg', link: 'https://github.com/javilopercoder' },
    { image: 'images/LinkedIn.svg', link: 'https://www.linkedin.com/in/javiloper/' },
    { image: 'images/logo_javilopercoder-Light.png', link: 'https://javilopercoder.vercel.app/' }
];

items.forEach(item => {
    const card = document.createElement('div');
    card.classList.add('card');

    const image = document.createElement('img');
    image.src = item.image;
    image.alt = 'Imagen de ' + item.link.split('/').pop();

    const link = document.createElement('a');
    link.href = item.link;
    link.textContent = 'Más información';
    link.target = '_blank';

    card.appendChild(image);
    card.appendChild(link);
    gallery.appendChild(card);
});
