export default function decorate(block) {
  const cols = [...block.firstElementChild.children];
  block.classList.add(`grid-${cols.length}-cols`);
}
