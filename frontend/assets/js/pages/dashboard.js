function statCard(label, value, icon, tone) {
  return `<div class="col-sm-6 col-xl-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-card__icon stat-card__icon--${tone}"><i class="bi bi-${icon}"></i></div>
    <div><div class="stat-card__label">${escapeHtml(label)}</div><div class="stat-card__value">${value}</div></div>
  </div></div></div>`;
}

initPageShell({
  title: 'Dashboard',
  activeNav: 'dashboard',
  render: async (container) => {
    const data = await apiGet('/api/dashboard');
    const { role, stats, recent_sales: recentSales, low_stock_alerts: alerts } = data;

    let statsHtml = '';
    if (role === 'owner') {
      statsHtml = `<div class="row g-3 mb-4">
        ${statCard('Branches', stats.branches, 'shop', 'blue')}
        ${statCard('Products', stats.products, 'box-seam', 'green')}
        ${statCard('Sales', stats.sales, 'cash-stack', 'amber')}
        ${statCard('Low Stock', stats.low_stock, 'exclamation-triangle', 'red')}
      </div>`;
    } else if (role === 'branch_admin') {
      statsHtml = `<div class="row g-3 mb-4">
        ${statCard('Staff', stats.staff, 'people', 'blue')}
        ${statCard('Products', stats.products, 'box-seam', 'green')}
        ${statCard('Sales', stats.sales, 'cash-stack', 'amber')}
        ${statCard('Low Stock', stats.low_stock, 'exclamation-triangle', 'red')}
      </div>`;
    } else if (role === 'storekeeper') {
      statsHtml = `<div class="row g-3 mb-4">
        ${statCard('Branch Products', stats.products, 'box-seam', 'green')}
        ${statCard('Low Stock', stats.low_stock, 'exclamation-triangle', 'red')}
      </div>`;
    } else if (role === 'cashier') {
      statsHtml = `<div class="row g-3 mb-4">
        ${statCard("Today's Sales", stats.sales, 'receipt', 'blue')}
        ${statCard("Today's Revenue", formatMoney(stats.today_sales), 'cash-coin', 'green')}
      </div>
      <a href="pos.html" class="btn btn-primary mb-4"><i class="bi bi-cart3 me-1"></i>Open Point of Sale</a>`;
    } else if (role === 'sales_assistant') {
      statsHtml = `<div class="card mb-4"><div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div class="stat-card__icon stat-card__icon--blue"><i class="bi bi-search"></i></div>
        <div class="flex-grow-1"><div class="fw-semibold">Find stock across every branch</div>
          <div class="text-muted small">Search items and suggest alternatives when out of stock.</div></div>
        <div class="d-flex gap-2">
          <a href="search.html" class="btn btn-primary"><i class="bi bi-search me-1"></i>Stock Search</a>
          <a href="reservations.html" class="btn btn-outline-secondary"><i class="bi bi-bookmark-check me-1"></i>Reservations</a>
        </div></div></div>`;
    }

    let alertsHtml = '';
    if (alerts.length && ['owner', 'branch_admin', 'storekeeper'].includes(role)) {
      alertsHtml = `<div class="card mb-4"><div class="card-header d-flex align-items-center gap-2 text-danger">
          <i class="bi bi-exclamation-triangle-fill"></i> Low Stock — ${alerts.length} item(s) below threshold</div>
        ${renderTable(
          [
            { key: 'branch_name', label: 'Branch' },
            { key: 'name', label: 'Product' },
            { key: 'style_code', label: 'Style' },
            { key: 'quantity', label: 'Qty', render: (r) => `<span class="badge text-bg-danger badge-soft">${r.quantity}</span>` },
            { key: 'low_stock_threshold', label: 'Threshold' },
          ],
          alerts
        )}</div>`;
    }

    let salesHtml = '';
    if (recentSales.length) {
      const headers = [
        { key: 'id', label: 'Invoice', render: (r) => `#${r.id}` },
        ...(role === 'owner' ? [{ key: 'branch_name', label: 'Branch' }] : []),
        { key: 'cashier_name', label: 'Cashier' },
        { key: 'total', label: 'Total', render: (r) => formatMoney(r.total) },
        { key: 'payment_method', label: 'Payment', render: (r) => statusBadge(r.payment_method) },
        { key: 'created_at', label: 'Date' },
      ];
      salesHtml = `<div class="card"><div class="card-header">Recent Sales</div>${renderTable(headers, recentSales)}</div>`;
    }

    container.innerHTML = statsHtml + alertsHtml + salesHtml ||
      renderEmpty('Nothing to show yet.', 'clipboard-data');
  },
});
