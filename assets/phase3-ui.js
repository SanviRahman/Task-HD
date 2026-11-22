/* Presentation-only enhancements for Phase 3; existing POST handlers are unchanged. */
(() => {
  'use strict';
  const form = document.getElementById('permission-form');
  const raw = document.getElementById('permission-data');
  if (form && raw) {
    const project = document.getElementById('permission-project');
    const member = document.getElementById('permission-user');
    const status = document.getElementById('permission-state');
    let assignments = {};
    try { assignments = JSON.parse(raw.textContent || '{}') || {}; } catch (_) { assignments = {}; }
    const fields = Array.from(form.querySelectorAll('input[type="checkbox"]'));
    function loadSavedPermissions() {
      const key = `${project?.value || ''}-${member?.value || ''}`;
      const saved = project?.value && member?.value ? assignments[key] : undefined;
      fields.forEach((field) => { field.checked = !!(saved && Number(saved[field.name]) === 1); });
      if (status) status.textContent = !project?.value || !member?.value
        ? 'প্রকল্প ও ব্যবহারকারী নির্বাচন করুন।'
        : saved ? 'এই সদস্যের পূর্বে সংরক্ষিত অনুমতি লোড হয়েছে। প্রয়োজনমতো পরিবর্তন করুন।'
          : 'এই প্রকল্পে এখনো কোনো অনুমতি সংরক্ষিত নেই। নতুন করে নির্বাচন করুন।';
    }
    project?.addEventListener('change', loadSavedPermissions);
    member?.addEventListener('change', loadSavedPermissions);
    loadSavedPermissions();
  }
  // Date validation improves immediate feedback; the existing server-side comparison remains authoritative.
  const yearForm = document.querySelector('.records-years form[action="years.php"]');
  if (yearForm) {
    const start = yearForm.querySelector('[name="start_date"]');
    const end = yearForm.querySelector('[name="end_date"]');
    function validateYearDates() {
      if (end && start) {
        end.setCustomValidity(start.value && end.value && end.value <= start.value
          ? 'শেষ তারিখ শুরুর তারিখের পরে হতে হবে।' : '');
      }
    }
    start?.addEventListener('change', validateYearDates);
    end?.addEventListener('change', validateYearDates);
    yearForm.addEventListener('submit', validateYearDates);
  }
})();
