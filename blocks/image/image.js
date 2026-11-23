export default function decorate(block) {
  const pic = block.querySelector('picture');
  if (pic) {
    block.classList.add('has-image');
  }
}
