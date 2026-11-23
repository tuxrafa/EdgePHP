export default function decorate(block) {
  const items = [...block.children];
  block.classList.add('carousel-ready');

  const viewport = document.createElement('div');
  viewport.className = 'carousel-viewport';
  const track = document.createElement('div');
  track.className = 'carousel-track';
  items.forEach((item) => {
    item.classList.add('carousel-item');
    track.append(item);
  });
  viewport.append(track);

  const prev = document.createElement('button');
  prev.type = 'button';
  prev.className = 'carousel-btn prev';
  prev.setAttribute('aria-label', 'Anterior');
  prev.textContent = '‹';
  const next = document.createElement('button');
  next.type = 'button';
  next.className = 'carousel-btn next';
  next.setAttribute('aria-label', 'Próximo');
  next.textContent = '›';

  const step = () => Math.max(viewport.clientWidth * 0.8, 240);
  prev.addEventListener('click', () => viewport.scrollBy({ left: -step(), behavior: 'smooth' }));
  next.addEventListener('click', () => viewport.scrollBy({ left: step(), behavior: 'smooth' }));

  block.replaceChildren(prev, viewport, next);
}
