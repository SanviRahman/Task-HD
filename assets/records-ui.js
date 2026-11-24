/* JV Construction Records UI v1.0: browser-only filters and form editing helpers.
 * No API requests or changes to existing PHP save/approve endpoints.
 */
(() => {
  'use strict';
  const page = document.querySelector('[data-records-page]');
  if (!page) return;

  const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const flashToForm = (form) => {
    if (!form) return;
    form.closest('.record-surface')?.scrollIntoView({ behavior: reduceMotion ? 'instant' : 'smooth', block: 'start' });
    const focusField = form.querySelector('select[name="project_id"]');
    if (focusField) focusField.focus({ preventScroll: true });
  };

  function initRecordTable(surface) {
    const table = surface.querySelector('[data-record-table]');
    if (!table) return;
    const rows = Array.from(table.querySelectorAll('tbody tr[data-record-row]'));
    const toolbar = surface.querySelector('[data-record-toolbar]');
    const pager = surface.querySelector('[data-record-pagination]');
    const search = surface.querySelector('[data-search-input]');
    const filters = Array.from(surface.querySelectorAll('[data-filter]'));
    const reset = surface.querySelector('[data-clear-filters]');
    const noResults = surface.querySelector('[data-no-results]');
    const pageSizeSelect = surface.querySelector('[data-page-size]');
    const resultCount = surface.querySelector('[data-result-count]');
    const pageInfo = surface.querySelector('[data-page-info]');
    const previous = surface.querySelector('[data-page-prev]');
    const next = surface.querySelector('[data-page-next]');
    if (!rows.length || !toolbar || !pager) return;

    let currentPage = 1;
    const searchableText = rows.map((row) => (row.textContent || '').toLocaleLowerCase().normalize('NFC'));
    function render() {
      const query = (search?.value || '').trim().toLocaleLowerCase().normalize('NFC');
      const matching = rows.filter((row, index) => {
        if (query && !searchableText[index].includes(query)) return false;
        return filters.every((filter) => !filter.value || row.dataset[filter.dataset.filter] === filter.value);
      });
      const perPage = Math.max(1, Number.parseInt(pageSizeSelect?.value || '10', 10) || 10);
      const pageCount = Math.max(1, Math.ceil(matching.length / perPage));
      currentPage = Math.min(currentPage, pageCount);
      const visible = new Set(matching.slice((currentPage - 1) * perPage, currentPage * perPage));
      rows.forEach((row) => { row.hidden = !visible.has(row); });
      if (noResults) noResults.hidden = matching.length !== 0;
      if (table.parentElement) table.parentElement.hidden = matching.length === 0;
      if (resultCount) resultCount.textContent = `${matching.length}টি রেকর্ড পাওয়া গেছে`;
      if (pageInfo) pageInfo.textContent = `${currentPage} / ${pageCount}`;
      if (previous) previous.disabled = currentPage <= 1;
      if (next) next.disabled = currentPage >= pageCount;
    }

    toolbar.hidden = false;
    pager.hidden = false;
    search?.addEventListener('input', () => { currentPage = 1; render(); });
    filters.forEach((select) => select.addEventListener('change', () => { currentPage = 1; render(); }));
    pageSizeSelect?.addEventListener('change', () => { currentPage = 1; render(); });
    reset?.addEventListener('click', () => {
      if (search) search.value = '';
      filters.forEach((select) => { select.value = ''; });
      currentPage = 1;
      render();
      search?.focus();
    });
    previous?.addEventListener('click', () => { currentPage = Math.max(1, currentPage - 1); render(); });
    next?.addEventListener('click', () => { currentPage += 1; render(); });
    render();
  }

  page.querySelectorAll('.record-surface').forEach(initRecordTable);

  // Existing expense form posts to expenses.php. Editing reuses its original hidden action/id.
  if (page.dataset.recordsPage === 'expenses') {
    const form = document.getElementById('expenseForm');
    const project = document.getElementById('pid');
    const head = document.getElementById('hid');
    const cancel = document.getElementById('cancelExpenseEdit');
    const saveButton = document.getElementById('expenseSaveButton');
    const hint = document.getElementById('expense-head-hint');
    const action = document.getElementById('action');
    const idInput = document.getElementById('rid');
    const title = page.querySelector('[data-form-title]');
    const badge = page.querySelector('[data-form-badge]');
    const budgetField = document.getElementById('expenseProjectBudget');
    const remainingField = document.getElementById('expenseProjectRemaining');
    function updateProjectBudget() {
      if (!budgetField || !remainingField) return;
      const selected = project?.selectedOptions[0];
      if (!selected?.value) {
        budgetField.value = '';
        remainingField.value = '';
      } else if (selected.dataset.budgetAccess !== '1') {
        budgetField.value = 'কোনো অনুমতি নেই';
        remainingField.value = 'কোনো অনুমতি নেই';
      } else {
        budgetField.value = selected.dataset.projectBudget || 'বাজেট সেট হয়নি';
        remainingField.value = selected.dataset.projectRemaining || '---';
      }
    }


    function toggleProjectPermissions(editMode) {
      if (!project) return;
      Array.from(project.options).forEach((option) => {
        if (!option.value) return;
        option.disabled = (editMode ? option.dataset.canEdit : option.dataset.canAdd) !== '1';
      });
      if (project.selectedOptions[0]?.disabled) project.value = '';
    }
    function filterHeads() {
      updateProjectBudget();
      if (!head || !project) return;
      let optionsFound = 0;
      Array.from(head.options).forEach((option) => {
        if (!option.value) return;
        const matches = !!project.value && option.dataset.project === project.value;
        option.hidden = !matches;
        option.disabled = !matches;
        if (matches) optionsFound += 1;
      });
      if (head.selectedOptions[0]?.disabled || !head.querySelector(`option[value="${head.value}"]`)) head.value = '';
      const placeholder = head.options[0];
      if (placeholder) placeholder.textContent = project.value && !optionsFound ? 'এই প্রকল্পে কোনো ব্যয়ের খাত নেই' : 'ব্যয়ের খাত নির্বাচন করুন';
      if (hint) hint.textContent = project.value && !optionsFound ? 'আগে ব্যয়ের খাত পেজ থেকে এই প্রকল্পের একটি খাত তৈরি করুন।' : 'প্রকল্প অনুযায়ী ব্যয়ের খাত দেখাবে।';
    }
    function resetExpense() {
      if (!form) return;
      form.reset();
      if (action) action.value = 'add';
      if (idInput) idInput.value = '';
      toggleProjectPermissions(false);
      if (project) project.value = '';
      if (head) head.value = '';
      filterHeads();
      if (title) title.textContent = 'ব্যয় এন্ট্রি করুন';
      if (badge) badge.textContent = 'নতুন এন্ট্রি';
      if (saveButton) saveButton.textContent = 'ব্যয় সংরক্ষণ';
      if (cancel) cancel.hidden = true;
    }
    project?.addEventListener('change', filterHeads);
    cancel?.addEventListener('click', resetExpense);
    window.filterHeads = filterHeads;
    window.editExpense = (row) => {
      if (!form || !project || !head || !row) return;
      toggleProjectPermissions(true);
      const option = Array.from(project.options).find((item) => item.value === String(row.project_id) && !item.disabled);
      if (!option) {
        resetExpense();
        window.alert('এই প্রকল্পটি এখন সংশোধনের জন্য উপলব্ধ নয়। Admin-এর সঙ্গে যোগাযোগ করুন।');
        return;
      }
      project.value = String(row.project_id);
      filterHeads();
      head.value = String(row.head_id);
      if (action) action.value = 'edit';
      if (idInput) idInput.value = String(row.id);
      document.getElementById('emp').value = String(row.advance_employee_id || 0);
      document.getElementById('dt').value = row.entry_date || '';
      document.getElementById('amt').value = row.amount || '';
      document.getElementById('desc').value = row.description || '';
      if (title) title.textContent = 'ব্যয়ের তথ্য সংশোধন করুন';
      if (badge) badge.textContent = `এন্ট্রি #${row.id}`;
      if (saveButton) saveButton.textContent = 'সংশোধন সংরক্ষণ';
      if (cancel) cancel.hidden = false;
      flashToForm(form);
    };
    toggleProjectPermissions(false);
    filterHeads();
  }

  // Existing advance form posts to advances.php; maintain the original POST contract.
  if (page.dataset.recordsPage === 'advances') {
    const form = document.getElementById('advanceForm');
    const project = document.getElementById('pid');
    const employee = document.getElementById('eid');
    const action = document.getElementById('action');
    const recordId = document.getElementById('recordId');
    const title = page.querySelector('[data-form-title]');
    const badge = page.querySelector('[data-form-badge]');
    const cancel = document.getElementById('cancelAdvanceEdit');
    const saveButton = document.getElementById('advanceSaveButton');

    function resetAdvance() {
      if (!form) return;
      form.reset();
      if (action) action.value = 'add';
      if (recordId) recordId.value = '';
      if (project) project.value = '';
      if (employee) employee.value = '';
      if (title) title.textContent = 'নতুন অগ্রিম এন্ট্রি করুন';
      if (badge) badge.textContent = 'নতুন এন্ট্রি';
      if (cancel) cancel.hidden = true;
      if (saveButton) saveButton.textContent = 'অগ্রিম সংরক্ষণ';
    }
    cancel?.addEventListener('click', resetAdvance);
    window.editAdvance = (row) => {
      if (!form || !project || !employee || !row) return;
      const option = Array.from(project.options).find((item) => item.value === String(row.project_id) && !item.disabled);
      if (!option) {
        window.alert('এই প্রকল্পটি এখন সংশোধনের জন্য উপলব্ধ নয়। Admin-এর সঙ্গে যোগাযোগ করুন।');
        return;
      }
      project.value = String(row.project_id);
      employee.value = String(row.employee_id);
      document.getElementById('adate').value = row.advance_date || '';
      document.getElementById('amount').value = row.amount || '';
      document.getElementById('note').value = row.note || '';
      if (action) action.value = 'edit';
      if (recordId) recordId.value = String(row.id);
      if (title) title.textContent = 'অগ্রিমের তথ্য সংশোধন করুন';
      if (badge) badge.textContent = `এন্ট্রি #${row.id}`;
      if (saveButton) saveButton.textContent = 'সংশোধন সংরক্ষণ';
      if (cancel) cancel.hidden = false;
      flashToForm(form);
    };
  }
})();
