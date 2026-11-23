export default function decorate(block) {
  block.classList.add('gallery-ready');
  const figures = [...block.querySelectorAll('picture')];
  figures.forEach((pic, i) => {
    const wrap = pic.closest('div') || pic.parentElement;
    wrap?.classList.add('gallery-item');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'gallery-open';
    btn.setAttribute('aria-label', `Abrir imagem ${i + 1}`);
    btn.append(pic.cloneNode(true));
    pic.replaceWith(btn);

    btn.addEventListener('click', () => {
      const dialog = document.createElement('dialog');
      dialog.className = 'gallery-lightbox';
      const img = btn.querySelector('img')?.cloneNode(true);
      const close = document.createElement('button');
      close.type = 'button';
      close.textContent = 'Fechar';
      close.className = 'gallery-close';
      close.addEventListener('click', () => dialog.close());
      dialog.addEventListener('close', () => dialog.remove());
      if (img) dialog.append(img, close);
      document.body.append(dialog);
      dialog.showModal();
    });
  });
}
