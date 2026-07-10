initPageShell({
  title: 'Reports',
  activeNav: 'reports',
  roles: ['owner', 'branch_admin'],
  render: async (container, flash, user) => {
    const isOwner = user.role === 'owner';
    const today = new Date().toISOString().slice(0, 10);
    const monthStart = `${today.slice(0, 8)}01`;

    container.innerHTML = `
      <div class="card mb-4"><div class="card-body">
        <form id="report-form" class="row g-3">
          <div class="col-md-3"><label class="form-label">Report</label><select class="form-select" id="report-type">
            <option value="fast_moving">Fast Moving Items</option>
            <option value="slow_moving">Slow Moving Items</option>
            <option value="stock_levels">Stock by Branch</option>
            <option value="low_stock">Low Stock</option>
            <option value="monthly_sales">Sales Summary</option>
            <option value="returns_summary">Returns & Exchanges</option>
            <option value="loyalty_stats">Loyalty Statistics</option>
            <option value="supplier_orders">Supplier Orders</option>
          </select></div>
          ${isOwner ? '<div class="col-md-2"><label class="form-label">Branch</label><select class="form-select" id="branch-id"><option value="0">All Branches</option></select></div>' : ''}
          <div class="col-md-2"><label class="form-label">From</label><input type="date" class="form-control" id="date-from" value="${monthStart}"></div>
          <div class="col-md-2"><label class="form-label">To</label><input type="date" class="form-control" id="date-to" value="${today}"></div>
          <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Generate</button></div>
        </form>
      </div></div>
      <div class="card"><div class="card-header d-flex justify-content-between"><span id="report-title">Report</span>
        <button class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()">Print</button></div>
        <div id="report-body" class="p-3 text-muted">Select filters and generate a report.</div>
      </div>`;

    if (isOwner) {
      const { branches } = await apiGet('/api/branches');
      document.getElementById('branch-id').innerHTML += branches.map((b) => `<option value="${b.id}">${escapeHtml(b.name)}</option>`).join('');
    }

    const runReport = async () => {
      const params = new URLSearchParams({
        type: document.getElementById('report-type').value,
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value,
      });
      if (isOwner) params.set('branch_id', document.getElementById('branch-id').value);

      try {
        const data = await apiGet(`/api/reports?${params.toString()}`);
        document.getElementById('report-title').textContent = data.title;

        if (!data.rows.length) {
          document.getElementById('report-body').innerHTML = '<p class="text-muted mb-0">No data for selected filters.</p>';
          return;
        }

        const keys = Object.keys(data.rows[0]);
        document.getElementById('report-body').innerHTML = renderTable(
          keys.map((k) => ({
            key: k,
            label: k.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
            render: (r) => escapeHtml(String(r[k] ?? '')),
          })),
          data.rows
        );
      } catch (err) {
        showFlash(flash, 'danger', err.message);
      }
    };

    document.getElementById('report-form').onsubmit = async (e) => {
      e.preventDefault();
      await runReport();
    };
  },
});
