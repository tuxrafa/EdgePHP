export default function decorate(block) {
  block.querySelectorAll('a').forEach((a) => {
    if (!a.classList.contains('button')) a.classList.add('button', 'primary');
  });
}
