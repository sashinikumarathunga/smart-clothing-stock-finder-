function statCard(label, value, icon, tone, href = '') {
  const card = `
    <div class="card stat-card h-100 ${href ? 'dashboard-stat-link' : ''}">
      <div class="card-body">
        <div class="stat-card__icon stat-card__icon--${tone}">
          <i class="bi bi-${icon}"></i>
        </div>

        <div>
          <div class="stat-card__label">${escapeHtml(label)}</div>
          <div class="stat-card__value">${value}</div>
        </div>
      </div>
    </div>
  `;

  return `
    <div class="col-sm-6 col-xl-3">
      ${href
        ? `<a href="${href}" class="text-decoration-none text-reset">${card}</a>`
        : card
      }
    </div>
  `;
}

initPageShell({
  title: 'Dashboard',
  activeNav: 'dashboard',
  render: async (container) => {
    const data = await apiGet('/api/dashboard');
    const { role, stats, recent_sales: recentSales, low_stock_alerts: alerts } = data;
    const roleIntro = {
      owner: ['Business overview', 'Monitor branches, inventory performance and sales activity.'],
      branch_admin: ['Branch overview', 'Manage your team, inventory health and branch performance.'],
      storekeeper: ['Inventory operations', 'Keep stock accurate and purchase orders moving.'],
      sales_assistant: ['Customer assistance', 'Find available stock quickly and manage reservations.'],
      cashier: ['Checkout overview', 'Process sales, customers, returns and exchanges efficiently.']
    }[role] || ['Overview', 'Your latest system activity.'];

    const introHtml = `<div class="dashboard-welcome mb-4">
      <div><div class="dashboard-welcome__eyebrow">${escapeHtml(roleIntro[0])}</div>
      <p class="dashboard-welcome__text mb-0">${escapeHtml(roleIntro[1])}</p></div>
      <div class="dashboard-welcome__date"><i class="bi bi-calendar3 me-2"></i>${new Date().toLocaleDateString('en-LK', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}</div>
    </div>`;

    let statsHtml = '';
    if (role === 'owner') {
      statsHtml = `<div class="row g-3 mb-4">
        ${statCard('Branches', stats.branches, 'shop', 'blue')}
        ${statCard('Products', stats.products, 'box-seam', 'green')}
        ${statCard('Sales', stats.sales, 'cash-stack', 'amber')}
        ${statCard('Low Stock', stats.low_stock, 'exclamation-triangle', 'red')}
        ${statCard('Return & Exchange', Number(stats.returns || 0) + Number(stats.exchanges || 0), 'arrow-left-right', 'blue')}
        ${statCard('Loyalty Discounts', formatMoney(stats.loyalty_discount), 'gift', 'green')}
      </div>`;
    } else if (role === 'branch_admin') {
      statsHtml = `<div class="row g-3 mb-4">
        ${statCard('Staff', stats.staff, 'people', 'blue')}
        ${statCard('Products', stats.products, 'box-seam', 'green')}
        ${statCard('Sales', stats.sales, 'cash-stack', 'amber')}
        ${statCard('Low Stock', stats.low_stock, 'exclamation-triangle', 'red')}
        ${statCard('Return & Exchange', Number(stats.returns || 0) + Number(stats.exchanges || 0), 'arrow-left-right', 'blue')}
        ${statCard('Loyalty Discounts', formatMoney(stats.loyalty_discount), 'gift', 'green')}
      </div>`;
    } else if (role === 'storekeeper') {
      statsHtml = `<div class="row g-3 mb-4">
        ${statCard('Branch Products', stats.products, 'box-seam', 'green')}
        ${statCard('Low Stock', stats.low_stock, 'exclamation-triangle', 'red')}
      </div>
      <div class="dashboard-actions mb-4">
        <a href="inventory.html" class="dashboard-action"><span class="dashboard-action__icon"><i class="bi bi-box-seam"></i></span><span><strong>Manage Inventory</strong><small>Add products and update stock quantities.</small></span><i class="bi bi-arrow-right"></i></a>
        <a href="purchase-orders.html" class="dashboard-action"><span class="dashboard-action__icon"><i class="bi bi-receipt"></i></span><span><strong>Purchase Orders</strong><small>Create and follow supplier orders.</small></span><i class="bi bi-arrow-right"></i></a>
      </div>`;
    } else if (role === 'cashier') {
      statsHtml = `<div class="row g-3 mb-4">
        ${statCard(
  "Today's Sales",
  stats.sales,
  'receipt',
  'blue',
  'sales.html'
)}

${statCard(
  "Today's Revenue",
  formatMoney(stats.today_sales),
  'cash-coin',
  'green'
)}

${statCard(
  "Today's Return & Exchange",
  Number(stats.returns || 0) + Number(stats.exchanges || 0),
  'arrow-left-right',
  'blue',
  'returns.html?view=history'
)}

${statCard(
  "Today's Loyalty Discounts",
  formatMoney(stats.loyalty_discount),
  'gift',
  'green'
)}
      </div>
      <div class="dashboard-actions mb-4">
        <a href="pos.html" class="dashboard-action"><span class="dashboard-action__icon"><i class="bi bi-cart3"></i></span><span><strong>Open Point of Sale</strong><small>Scan items and complete a new sale.</small></span><i class="bi bi-arrow-right"></i></a>
        <a href="returns.html" class="dashboard-action"><span class="dashboard-action__icon"><i class="bi bi-arrow-left-right"></i></span><span><strong>Returns & Exchanges</strong><small>Process compulsory exchanges safely.</small></span><i class="bi bi-arrow-right"></i></a>
      </div>`;
    } else if (role === 'sales_assistant') {
      statsHtml = `<div class="card dashboard-feature mb-4"><div class="card-body d-flex flex-wrap align-items-center gap-3">
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

    container.innerHTML = introHtml + (statsHtml + alertsHtml + salesHtml ||
      renderEmpty('Nothing to show yet.', 'clipboard-data'));
  },
});
