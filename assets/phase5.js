/* Progressive enhancement only; all forms work without JavaScript. */
(function () {
  'use strict';
  document.querySelectorAll('.p5-page [data-p5-table]').forEach(function (table) {
    var parent = table.closest('.p5-surface');
    if (!parent) return;
    var search = parent.querySelector('[data-p5-search]');
    var empty = parent.querySelector('[data-p5-no-match]');
    if (!search) return;
    var rows = Array.from(table.querySelectorAll('tbody [data-p5-row]'));
    var update = function () {
      var q = search.value.toLocaleLowerCase().trim();
      var seen = 0;
      rows.forEach(function (tr) {
        var visible = tr.textContent.toLocaleLowerCase().includes(q);
        tr.hidden = !visible;
        if (visible) seen++;
      });
      if (empty) empty.hidden = seen > 0;
    };
    search.addEventListener('input', update);
    update();
  });

  var project = document.querySelector('[data-p5-project-select]');
  var items = document.querySelector('[data-p5-items]');
  if (project && items) {
    var adjust = function () {
      var pid = project.value;
      var selected = items.selectedOptions[0];
      if (selected && selected.dataset.project && selected.dataset.project !== pid) items.value = '';
      Array.from(items.options).forEach(function (option) {
        if (!option.value) return;
        var valid = Boolean(pid) && option.dataset.project === pid;
        option.hidden = !valid;
        option.disabled = !valid;
      });
    };
    project.addEventListener('change', adjust);
    adjust();
  }
})();
