initPageShell({
  title: 'Branch Settings',
  activeNav: 'settings',
  roles: ['branch_admin'],
  render: async (container, flash) => {
    const load = async () => {
      const data = await apiGet('/api/settings');
      const s = data.settings;

      container.innerHTML = `
        <div class="card mb-4"><div class="card-header">Loyalty & Returns</div><div class="card-body">
          <form id="settings-form" class="row g-3">
            <div class="col-md-4"><label class="form-label">Spend per Loyalty Point (Rs.)</label>
              <input type="number" step="0.01" class="form-control" id="loyalty-spend" value="${s.loyalty_spend_per_point}" required></div>
            <div class="col-md-4"><label class="form-label">Point Redemption Value (Rs.)</label>
              <input type="number" step="0.01" class="form-control" id="loyalty-value" value="${s.loyalty_point_value}" required></div>
            <div class="col-md-4"><label class="form-label">Minimum Points to Redeem</label>
              <input type="number" min="1" class="form-control" id="loyalty-min" value="${s.loyalty_min_redeem}" required></div>
            <div class="col-md-4"><label class="form-label">Return Period (days)</label>
              <input type="number" min="1" class="form-control" id="return-days" value="${s.return_period_days}" required></div>
            <div class="col-md-4"><label class="form-label">Low Stock Threshold</label>
              <input type="number" min="1" class="form-control" id="low-stock-threshold" value="${s.low_stock_threshold}" required></div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Save Settings</button></div>
          </form>
        </div></div>
        <div class="card"><div class="card-header">Low Stock Alerts per Product</div>
          ${renderTable(
            [
              { key: 'name', label: 'Product' },
              { key: 'style_code', label: 'Style' },
              { key: 'quantity', label: 'Qty' },
              {
                key: 'low_stock_alert_enabled',
                label: 'Alert',
                render: (r) => (Number(r.low_stock_alert_enabled) === 1 ? 'Yes' : 'No'),
              },
              {
                key: 'id',
                label: 'Action',
                render: (r) => `<button class="btn btn-sm btn-outline-secondary toggle-btn" data-id="${r.id}" data-enabled="${Number(r.low_stock_alert_enabled) === 1 ? 0 : 1}">
                  ${Number(r.low_stock_alert_enabled) === 1 ? 'Disable' : 'Enable'}
                </button>`,
              },
            ],
            data.products
          )}
        </div>`;

      document.getElementById('settings-form').onsubmit = async (e) => {
        e.preventDefault();
        await apiPut('/api/settings', {
          loyalty_spend_per_point: Number(document.getElementById('loyalty-spend').value),
          loyalty_point_value: Number(document.getElementById('loyalty-value').value),
          loyalty_min_redeem: Number(document.getElementById('loyalty-min').value),
          return_period_days: Number(document.getElementById('return-days').value),
          low_stock_threshold: Number(document.getElementById('low-stock-threshold').value),
        });
        showFlash(flash, 'success', 'Branch settings updated');
        await load();
      };

      container.querySelectorAll('.toggle-btn').forEach((btn) => {
        btn.onclick = async () => {
          await apiPut('/api/settings', {
            product_id: Number(btn.dataset.id),
            low_stock_alert_enabled: Number(btn.dataset.enabled),
          });
          showFlash(flash, 'success', 'Alert setting updated');
          await load();
        };
      });
    };

    await load();
  },
});
