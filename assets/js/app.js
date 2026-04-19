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

function refreshIndexedCheckboxValues(wrapperSelector, checkboxName) {
  const wrapper = document.querySelector(wrapperSelector);
  if (!wrapper) return;
  wrapper.querySelectorAll(`input[name="${checkboxName}[]"]`).forEach((input, index) => {
    input.value = String(index);
  });
}

const addTemplateFieldBtn = document.getElementById('add-template-field-row');
const templateFieldTemplate = document.getElementById('template-field-template');
const templateFieldWrapper = document.getElementById('template-field-wrapper');

if (addTemplateFieldBtn && templateFieldTemplate && templateFieldWrapper) {
  addTemplateFieldBtn.addEventListener('click', () => {
    const idx = templateFieldWrapper.querySelectorAll('.template-field-row').length;
    templateFieldWrapper.insertAdjacentHTML('beforeend', templateFieldTemplate.innerHTML.replaceAll('__INDEX__', String(idx)));
    refreshIndexedCheckboxValues('#template-field-wrapper', 'field_required');
  });

  templateFieldWrapper.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-template-field-row')) {
      const rows = templateFieldWrapper.querySelectorAll('.template-field-row');
      if (rows.length > 1) {
        e.target.closest('.template-field-row').remove();
        refreshIndexedCheckboxValues('#template-field-wrapper', 'field_required');
      }
    }
  });

  refreshIndexedCheckboxValues('#template-field-wrapper', 'field_required');
}

const addEventChecklistItemBtn = document.getElementById('add-event-checklist-item');
const eventChecklistTemplate = document.getElementById('event-checklist-template');
const eventChecklistWrapper = document.getElementById('event-checklist-wrapper');

function syncChecklistValueUi(row) {
  const typeSelect = row.querySelector('.event-item-type');
  const textInput = row.querySelector('.event-item-value-text');
  const checkWrapper = row.querySelector('.event-item-value-check');
  if (!typeSelect || !textInput || !checkWrapper) return;

  if (typeSelect.value === 'text') {
    textInput.classList.remove('d-none');
    checkWrapper.classList.add('d-none');
  } else {
    textInput.classList.add('d-none');
    checkWrapper.classList.remove('d-none');
  }
}

function refreshEventChecklistIndexes() {
  if (!eventChecklistWrapper) return;
  eventChecklistWrapper.querySelectorAll('.event-checklist-row').forEach((row, index) => {
    row.querySelectorAll('input[name="item_required[]"], input[name="item_checked[]"]').forEach((input) => {
      input.value = String(index);
    });
  });
}

if (addEventChecklistItemBtn && eventChecklistTemplate && eventChecklistWrapper) {
  addEventChecklistItemBtn.addEventListener('click', () => {
    const idx = eventChecklistWrapper.querySelectorAll('.event-checklist-row').length;
    eventChecklistWrapper.insertAdjacentHTML('beforeend', eventChecklistTemplate.innerHTML.replaceAll('__INDEX__', String(idx)));
    refreshEventChecklistIndexes();
  });

  eventChecklistWrapper.addEventListener('change', (e) => {
    const row = e.target.closest('.event-checklist-row');
    if (!row) return;
    if (e.target.classList.contains('event-item-type')) {
      syncChecklistValueUi(row);
    }
  });

  eventChecklistWrapper.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-event-checklist-item')) {
      const rows = eventChecklistWrapper.querySelectorAll('.event-checklist-row');
      if (rows.length > 1) {
        e.target.closest('.event-checklist-row').remove();
        refreshEventChecklistIndexes();
      }
    }
  });

  eventChecklistWrapper.querySelectorAll('.event-checklist-row').forEach((row) => syncChecklistValueUi(row));
  refreshEventChecklistIndexes();
}
