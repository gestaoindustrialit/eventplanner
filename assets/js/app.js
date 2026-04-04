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

const duplicateModal = document.getElementById('duplicateEventModal');
const duplicateForm = document.getElementById('duplicate-event-form');
const duplicateDateInput = document.getElementById('duplicate-event-date');
const duplicateSummary = document.getElementById('duplicate-event-summary');

if (duplicateModal && duplicateForm && duplicateDateInput && duplicateSummary) {
  duplicateModal.addEventListener('show.bs.modal', (event) => {
    const trigger = event.relatedTarget;
    if (!trigger) return;

    const eventId = trigger.getAttribute('data-event-id');
    const eventTitle = trigger.getAttribute('data-event-title') || 'Evento';
    const eventDate = trigger.getAttribute('data-event-date') || '';
    const duplicateUrl = trigger.getAttribute('data-duplicate-url') || '';

    if (!duplicateUrl || !eventId) {
      return;
    }

    duplicateForm.setAttribute('action', duplicateUrl);
    duplicateDateInput.value = eventDate;
    duplicateDateInput.min = '';
    duplicateSummary.textContent = `${eventTitle} (${eventDate})`;
  });
}

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

const addScheduleRowBtn = document.getElementById('add-schedule-row');
const scheduleTemplate = document.getElementById('schedule-template');
const scheduleWrapper = document.getElementById('schedule-wrapper');

if (addScheduleRowBtn && scheduleTemplate && scheduleWrapper) {
  addScheduleRowBtn.addEventListener('click', () => {
    scheduleWrapper.insertAdjacentHTML('beforeend', scheduleTemplate.innerHTML);
  });

  scheduleWrapper.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-schedule-row')) {
      const rows = scheduleWrapper.querySelectorAll('.schedule-row');
      if (rows.length > 1) {
        e.target.closest('.schedule-row').remove();
      }
    }
  });
}
