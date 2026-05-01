(function () {
  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatCurrency(value) {
    const amount = Number(value ?? 0);
    if (!Number.isFinite(amount)) return '0 kr';
    return new Intl.NumberFormat('nb-NO', {
      style: 'currency',
      currency: 'NOK',
      maximumFractionDigits: 2,
    }).format(amount);
  }

  function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return escapeHtml(value);
    return new Intl.DateTimeFormat('nb-NO', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date);
  }

  function renderBadge(status) {
    const raw = String(status ?? '').trim();
    const key = normalizeStatus(raw);
    const classes = {
      active: 'badge badge-green',
      approved: 'badge badge-green',
      paid: 'badge badge-green',
      pending: 'badge badge-yellow',
      requested: 'badge badge-yellow',
      paused: 'badge badge-gray',
      pauset: 'badge badge-gray',
      inactive: 'badge badge-red',
      inaktiv: 'badge badge-red',
      aktiv: 'badge badge-green',
      draft: 'badge badge-gray',
      rejected: 'badge badge-red',
      failed: 'badge badge-red',
    };
    const cls = classes[key] || 'badge badge-gray';
    return '<span class="' + cls + '">' + escapeHtml(raw || 'ukjent') + '</span>';
  }


  function normalizeStatus(status) {
    const key = String(status ?? '').trim().toLowerCase();
    if (['active','aktiv','approved'].includes(key)) return 'approved';
    if (['paused','pauset'].includes(key)) return 'paused';
    if (['inactive','inaktiv','rejected'].includes(key)) return 'rejected';
    if (key === 'pending') return 'pending';
    return key || 'unknown';
  }

  function renderStatusDot(status) {
    const st = normalizeStatus(status);
    const map = { approved: 'status-dot status-dot-green', paused: 'status-dot status-dot-blue', rejected: 'status-dot status-dot-red', pending: 'status-dot status-dot-yellow' };
    const title = { approved: 'Aktiv ambassadør', paused: 'Pauset ambassadør', rejected: 'Avvist/inaktiv ambassadør', pending: 'Venter godkjenning' };
    return '<span class="' + (map[st] || 'status-dot') + '" title="' + escapeHtml(title[st] || 'Ukjent status') + '"></span>';
  }

  function renderTable(container, rows, columns) {
    if (!container) return;
    if (!Array.isArray(columns) || columns.length === 0) {
      container.innerHTML = '<div class="empty">Ingen kolonner definert.</div>';
      return;
    }

    if (!Array.isArray(rows) || rows.length === 0) {
      container.innerHTML = '<div class="empty">Ingen data</div>';
      return;
    }

    const head = '<thead><tr>' + columns.map(function (col) {
      return '<th>' + escapeHtml(col.label) + '</th>';
    }).join('') + '</tr></thead>';

    const body = '<tbody>' + rows.map(function (row) {
      return '<tr>' + columns.map(function (col) {
        const raw = row ? row[col.key] : '';
        const html = typeof col.render === 'function'
          ? col.render(raw, row)
          : escapeHtml(raw ?? '');
        return '<td>' + html + '</td>';
      }).join('') + '</tr>';
    }).join('') + '</tbody>';

    container.innerHTML = '<div class="table-wrap"><table>' + head + body + '</table></div>';
  }

  window.DashboardUI = {
    escapeHtml: escapeHtml,
    formatCurrency: formatCurrency,
    formatDate: formatDate,
    normalizeStatus: normalizeStatus,
    renderStatusDot: renderStatusDot,
    renderBadge: renderBadge,
    renderTable: renderTable,
  };
})();
