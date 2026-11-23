export default function decorate(block) {
  if (!block.querySelector('hr')) {
    const hr = document.createElement('hr');
    block.append(hr);
  }
}
