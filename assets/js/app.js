document.querySelectorAll('.table-search').forEach((input) => {
  input.addEventListener('input', () => {
    const table = input.parentElement.querySelector('.searchable-table') || document.querySelector('.searchable-table');
    if (!table) return;

    const term = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach((row) => {
      row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
    });
  });
});

document.querySelectorAll('.delete-btn').forEach((btn) => {
  btn.addEventListener('click', (e) => {
    if (!confirm('Tem a certeza que deseja eliminar este registo?')) {
      e.preventDefault();
    }
  });
});

const addRowBtn = document.getElementById('add-lineup-row');
const lineupTemplate = document.getElementById('lineup-template');
const lineupWrapper = document.getElementById('lineup-wrapper');

if (addRowBtn && lineupTemplate && lineupWrapper) {
  addRowBtn.addEventListener('click', () => {
    lineupWrapper.insertAdjacentHTML('beforeend', lineupTemplate.innerHTML);
  });

  lineupWrapper.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-lineup-row')) {
      const rows = lineupWrapper.querySelectorAll('.lineup-row');
      if (rows.length > 1) {
        e.target.closest('.lineup-row').remove();
      }
    }
  });
}
