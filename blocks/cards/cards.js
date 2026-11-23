export default function decorate(block) {
  const cards = [...block.children];
  block.classList.add(`cards-${cards.length}`);
  cards.forEach((row) => {
    row.classList.add('cards-card');
    const pic = row.querySelector('picture');
    if (pic) row.classList.add('has-image');
  });
}
