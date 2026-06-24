const pressContactsTable = document.querySelector('.press-contacts-table');
const pressSearchInput = document.getElementById('press-contact-search');
const pressDistrictFilter = document.querySelector('.press-filter-district');
const pressLocalityFilter = document.querySelector('.press-filter-locality');
const pressPageSizeSelect = document.querySelector('.press-page-size');
const pressResetFiltersBtn = document.querySelector('.press-filters-reset');
const pressPaginationInfo = document.querySelector('.press-pagination-info');
const pressPaginationControls = document.querySelector('.press-pagination-controls');

if (
  pressContactsTable
  && pressSearchInput
  && pressDistrictFilter
  && pressLocalityFilter
  && pressPageSizeSelect
  && pressPaginationInfo
  && pressPaginationControls
) {
  const rows = Array.from(pressContactsTable.querySelectorAll('tbody tr'));
  let currentPage = 1;

  const normalize = (value) => value.trim().toLowerCase();
  const getSelectedPageSize = () => Number.parseInt(pressPageSizeSelect.value, 10) || 20;

  const filterRows = () => {
    const searchTerm = normalize(pressSearchInput.value);
    const districtTerm = normalize(pressDistrictFilter.value);
    const localityTerm = normalize(pressLocalityFilter.value);

    return rows.filter((row) => {
      const rowText = normalize(row.innerText);
      const districtCell = normalize(row.children[4]?.innerText || '');
      const localityCell = normalize(row.children[3]?.innerText || '');
      const matchesSearch = searchTerm === '' || rowText.includes(searchTerm);
      const matchesDistrict = districtTerm === '' || districtCell === districtTerm;
      const matchesLocality = localityTerm === '' || localityCell === localityTerm;

      return matchesSearch && matchesDistrict && matchesLocality;
    });
  };

  const buildPagination = (totalPages, onPageChange) => {
    pressPaginationControls.innerHTML = '';
    if (totalPages <= 1) return;

    const appendControl = (label, page, disabled = false, active = false) => {
      const li = document.createElement('li');
      li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'page-link';
      button.textContent = label;
      button.disabled = disabled;
      button.addEventListener('click', () => onPageChange(page));
      li.appendChild(button);
      pressPaginationControls.appendChild(li);
    };

    appendControl('‹', currentPage - 1, currentPage === 1);

    const pageWindow = 2;
    const start = Math.max(1, currentPage - pageWindow);
    const end = Math.min(totalPages, currentPage + pageWindow);

    for (let page = start; page <= end; page += 1) {
      appendControl(String(page), page, false, page === currentPage);
    }

    appendControl('›', currentPage + 1, currentPage === totalPages);
  };

  const render = () => {
    const filteredRows = filterRows();
    const pageSize = getSelectedPageSize();
    const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));

    if (currentPage > totalPages) currentPage = totalPages;

    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = startIndex + pageSize;
    const visibleRows = new Set(filteredRows.slice(startIndex, endIndex));

    rows.forEach((row) => {
      row.style.display = visibleRows.has(row) ? '' : 'none';
    });

    if (filteredRows.length === 0) {
      pressPaginationInfo.textContent = 'Sem resultados para os filtros aplicados.';
    } else {
      pressPaginationInfo.textContent = `A mostrar ${startIndex + 1}-${Math.min(endIndex, filteredRows.length)} de ${filteredRows.length} contactos`;
    }

    buildPagination(totalPages, (page) => {
      currentPage = page;
      render();
    });
  };

  [pressSearchInput, pressDistrictFilter, pressLocalityFilter].forEach((field) => {
    field.addEventListener('input', () => {
      currentPage = 1;
      render();
    });
    field.addEventListener('change', () => {
      currentPage = 1;
      render();
    });
  });

  pressPageSizeSelect.addEventListener('change', () => {
    currentPage = 1;
    render();
  });

  if (pressResetFiltersBtn) {
    pressResetFiltersBtn.addEventListener('click', () => {
      pressSearchInput.value = '';
      pressDistrictFilter.value = '';
      pressLocalityFilter.value = '';
      pressPageSizeSelect.value = '20';
      currentPage = 1;
      render();
    });
  }

  render();
}

document.querySelectorAll('.table-search').forEach((input) => {
  if (input.id === 'press-contact-search') return;
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

function enhanceEvaluationDepartmentSelector() {
  const normalizeLabel = (value) => value.trim().toLowerCase();
  const findFieldByLabel = (root, labelText, selector = 'input, select') => {
    const labels = Array.from(root.querySelectorAll('label'));
    const label = labels.find((item) => normalizeLabel(item.textContent || '') === normalizeLabel(labelText));
    if (!label) return null;

    if (label.htmlFor) {
      const byId = root.querySelector(`#${CSS.escape(label.htmlFor)}`);
      if (byId?.matches(selector)) return byId;
    }

    return label.parentElement?.querySelector(selector) || null;
  };

  const evaluationForm = Array.from(document.querySelectorAll('form')).find((form) => {
    const heading = form.closest('.card, section, div')?.querySelector('h1, h2, h3, h4, h5, h6');
    return heading && normalizeLabel(heading.textContent || '').includes('nova avaliação');
  });

  if (!evaluationForm) return;

  const departmentField = findFieldByLabel(evaluationForm, 'Departamento');
  if (!departmentField || departmentField.tagName.toLowerCase() === 'select') return;

  const departmentFilter = Array.from(document.querySelectorAll('select')).find((select) => {
    const label = select.id ? document.querySelector(`label[for="${CSS.escape(select.id)}"]`) : null;
    const nearbyLabel = label || select.closest('div')?.querySelector('label');
    return nearbyLabel && normalizeLabel(nearbyLabel.textContent || '') === 'departamento' && select !== departmentField;
  });

  if (!departmentFilter || departmentFilter.options.length === 0) return;

  const departmentSelect = document.createElement('select');
  Array.from(departmentField.attributes).forEach((attribute) => {
    departmentSelect.setAttribute(attribute.name, attribute.value);
  });
  departmentSelect.className = departmentField.className;
  departmentSelect.required = departmentField.required;

  const currentValue = departmentField.value;
  const selectableOptions = Array.from(departmentFilter.options).filter((option) => option.value !== '');
  departmentSelect.appendChild(new Option('', ''));
  selectableOptions.forEach((option) => {
    departmentSelect.appendChild(new Option(option.textContent, option.value, false, option.value === currentValue));
  });

  if (currentValue && !Array.from(departmentSelect.options).some((option) => option.value === currentValue)) {
    departmentSelect.appendChild(new Option(currentValue, currentValue, false, true));
  }

  departmentField.replaceWith(departmentSelect);
}

enhanceEvaluationDepartmentSelector();
