export default function decorate(block) {
  const slides = [...block.children];
  if (!slides.length) return;

  block.classList.add('slider-ready');
  const track = document.createElement('div');
  track.className = 'slider-track';
  slides.forEach((slide, i) => {
    slide.classList.add('slider-slide');
    slide.dataset.index = String(i);
    if (i !== 0) slide.hidden = true;
    track.append(slide);
  });

  const controls = document.createElement('div');
  controls.className = 'slider-controls';
  const prev = document.createElement('button');
  prev.type = 'button';
  prev.className = 'slider-btn';
  prev.setAttribute('aria-label', 'Slide anterior');
  prev.textContent = '‹';
  const next = document.createElement('button');
  next.type = 'button';
  next.className = 'slider-btn';
  next.setAttribute('aria-label', 'Próximo slide');
  next.textContent = '›';
  const dots = document.createElement('div');
  dots.className = 'slider-dots';
  dots.setAttribute('role', 'tablist');

  let index = 0;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const autoplay = block.classList.contains('autoplay') && !reduceMotion;
  let timer;

  function go(to) {
    slides[index].hidden = true;
    dots.children[index]?.setAttribute('aria-selected', 'false');
    index = (to + slides.length) % slides.length;
    slides[index].hidden = false;
    dots.children[index]?.setAttribute('aria-selected', 'true');
  }

  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'slider-dot';
    dot.setAttribute('role', 'tab');
    dot.setAttribute('aria-label', `Ir para slide ${i + 1}`);
    dot.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
    dot.addEventListener('click', () => { go(i); restart(); });
    dots.append(dot);
  });

  prev.addEventListener('click', () => { go(index - 1); restart(); });
  next.addEventListener('click', () => { go(index + 1); restart(); });

  block.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') { go(index - 1); restart(); }
    if (e.key === 'ArrowRight') { go(index + 1); restart(); }
  });
  block.tabIndex = 0;

  let startX = 0;
  block.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; }, { passive: true });
  block.addEventListener('touchend', (e) => {
    const dx = e.changedTouches[0].clientX - startX;
    if (Math.abs(dx) < 40) return;
    go(dx < 0 ? index + 1 : index - 1);
    restart();
  });

  function restart() {
    if (!autoplay) return;
    clearInterval(timer);
    timer = setInterval(() => go(index + 1), 5000);
  }

  controls.append(prev, dots, next);
  block.replaceChildren(track, controls);
  restart();
  block.addEventListener('pointerenter', () => clearInterval(timer));
  block.addEventListener('pointerleave', restart);
}
