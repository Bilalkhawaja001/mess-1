(function () {
  var ROWS_PER_PAGE = 13;

  function isGuestPage() {
    return window.location.pathname.indexOf('/admin/guests') !== -1;
  }

  function findReportTable() {
    var tables = Array.prototype.slice.call(document.querySelectorAll('table'));
    if (!tables.length) return null;

    return tables.find(function (table) {
      var text = (table.innerText || '').toLowerCase();
      return (
        text.indexOf('guest name') !== -1 ||
        text.indexOf('grand total') !== -1 ||
        text.indexOf('meal date') !== -1 ||
        text.indexOf('meal type') !== -1
      );
    }) || tables[0];
  }

  function clearStyles(node) {
    if (!node) return;
    var all = [node].concat(Array.prototype.slice.call(node.querySelectorAll('*')));
    all.forEach(function (el) {
      el.removeAttribute('style');
      el.style.overflow = 'visible';
      el.style.height = 'auto';
      el.style.maxHeight = 'none';
      el.style.position = 'static';
      el.style.transform = 'none';
    });
  }

  function reportRange() {
    var p = new URLSearchParams(window.location.search);
    var from = p.get('from_date') || '';
    var to = p.get('to_date') || '';
    if (!from && !to) return '';
    return 'Complete Date Range: ' + from + ' to ' + to;
  }

  function makeTitle(pageNo, totalPages) {
    var title = document.createElement('div');
    title.className = 'guest-print-title';
    title.textContent = 'Guest Meal Report';

    var range = reportRange();
    var sub = document.createElement('span');
    sub.className = 'guest-print-range';
    sub.textContent = (range ? range + ' | ' : '') + 'Page ' + pageNo + ' of ' + totalPages;
    title.appendChild(sub);

    return title;
  }

  function getRows(table) {
    var head = table.querySelector('thead');
    var foot = table.querySelector('tfoot');

    var bodyRows = [];
    if (table.querySelector('tbody')) {
      bodyRows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
    } else {
      bodyRows = Array.prototype.slice.call(table.querySelectorAll('tr')).filter(function (tr) {
        return !(head && head.contains(tr)) && !(foot && foot.contains(tr));
      });
    }

    var footerRows = foot ? Array.prototype.slice.call(foot.querySelectorAll('tr')) : [];

    if (!footerRows.length && bodyRows.length) {
      var last = bodyRows[bodyRows.length - 1];
      if ((last.innerText || '').toLowerCase().indexOf('grand total') !== -1) {
        footerRows = [last];
        bodyRows = bodyRows.slice(0, -1);
      }
    }

    return { head: head, bodyRows: bodyRows, footerRows: footerRows };
  }

  function buildTable(originalTable, head, rows, footerRows) {
    var table = document.createElement('table');

    var originalColgroup = originalTable.querySelector('colgroup');
    if (originalColgroup) table.appendChild(originalColgroup.cloneNode(true));

    if (head) table.appendChild(head.cloneNode(true));

    var tbody = document.createElement('tbody');
    rows.forEach(function (r) {
      tbody.appendChild(r.cloneNode(true));
    });
    table.appendChild(tbody);

    if (footerRows && footerRows.length) {
      var tfoot = document.createElement('tfoot');
      footerRows.forEach(function (r) {
        tfoot.appendChild(r.cloneNode(true));
      });
      table.appendChild(tfoot);
    }

    clearStyles(table);
    return table;
  }

  function buildPrintClone() {
    if (!isGuestPage()) return;

    var old = document.querySelector('.guest-print-clone');
    if (old) old.remove();

    var originalTable = findReportTable();
    if (!originalTable) return;

    document.body.classList.add('guest-print-mode');

    var data = getRows(originalTable);
    var rows = data.bodyRows;
    var footerRows = data.footerRows;

    var totalPages = Math.max(1, Math.ceil(rows.length / ROWS_PER_PAGE));
    if (footerRows.length && rows.length % ROWS_PER_PAGE === 0) {
      totalPages += 1;
    }

    var clone = document.createElement('div');
    clone.className = 'guest-print-clone';

    for (var i = 0; i < totalPages; i++) {
      var start = i * ROWS_PER_PAGE;
      var chunk = rows.slice(start, start + ROWS_PER_PAGE);
      var isLastPage = i === totalPages - 1;

      var page = document.createElement('div');
      page.className = 'guest-print-page';
      page.appendChild(makeTitle(i + 1, totalPages));

      var pageFooter = isLastPage ? footerRows : [];
      page.appendChild(buildTable(originalTable, data.head, chunk, pageFooter));

      clone.appendChild(page);
    }

    document.body.appendChild(clone);
  }

  function cleanupAfterPrint() {
    document.body.classList.remove('guest-print-mode');
    var old = document.querySelector('.guest-print-clone');
    if (old) old.remove();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', buildPrintClone);
  } else {
    buildPrintClone();
  }

  window.addEventListener('beforeprint', buildPrintClone);
  window.addEventListener('afterprint', cleanupAfterPrint);
})();
