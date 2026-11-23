export default function decorate(block) {
  const rows = [...block.children];
  if (rows.length > 1) block.classList.add('hero-split');
  const pic = block.querySelector('picture');
  if (pic && block.classList.contains('background-image')) {
    const img = pic.querySelector('img');
    if (img) {
      block.style.setProperty('--hero-bg', `url("${img.src}")`);
      pic.closest('div')?.classList.add('hero-media');
    }
  }
  block.classList.add('hero-ready');
}
