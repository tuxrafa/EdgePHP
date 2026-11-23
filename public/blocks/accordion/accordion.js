export default function decorate(block) {
  const rows = [...block.children];
  rows.forEach((row, index) => {
    const titleCell = row.children[0];
    const bodyCell = row.children[1] || document.createElement('div');
    if (!row.children[1]) row.append(bodyCell);

    const details = document.createElement('details');
    details.className = 'accordion-item';
    if (index === 0 && block.classList.contains('open-first')) details.open = true;

    const summary = document.createElement('summary');
    summary.className = 'accordion-summary';
    summary.append(...titleCell.childNodes);
    const content = document.createElement('div');
    content.className = 'accordion-panel';
    content.append(...bodyCell.childNodes);

    details.append(summary, content);
    row.replaceChildren(details);
  });
}
