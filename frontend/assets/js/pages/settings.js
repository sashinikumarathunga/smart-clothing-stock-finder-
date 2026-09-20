initPageShell({
  title: 'Branch Settings',
  activeNav: 'settings',
  roles: ['owner'],
  render: async (container, flash) => {
    let selectedBranchId = '';

    const loadSettings = async () => {
      if (!selectedBranchId) {
        container.innerHTML = `
          <div class="alert alert-warning mb-0">
            No branch is available. Create a branch before configuring branch settings.
          </div>`;
        return;
      }

      const data = await apiGet(`/api/settings?branch_id=${encodeURIComponent(selectedBranchId)}`);
      const s = data.settings;
      const branches = data.branches || [];

      container.innerHTML = `
        <div class="card mb-4">
          <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
              <h5 class="mb-1">Branch Policy Control</h5>
              <small class="text-muted">Configure loyalty, returns and the default low-stock threshold for each branch.</small>
            </div>
            <div style="min-width: 280px;">
              <label class="form-label mb-1" for="settings-branch">Select Branch</label>
              <select class="form-select" id="settings-branch">
                ${branches.map((branch) => `
                  <option value="${branch.id}" ${String(branch.id) === String(selectedBranchId) ? 'selected' : ''}>
                    ${escapeHtml(branch.name)}${branch.location ? ` — ${escapeHtml(branch.location)}` : ''}
                  </option>
                `).join('')}
              </select>
            </div>
          </div>
          <div class="card-body">
            <form id="settings-form" class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Spend per Loyalty Point (Rs.)</label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="loyalty-spend" value="${s.loyalty_spend_per_point}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Point Redemption Value (Rs.)</label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="loyalty-value" value="${s.loyalty_point_value}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Minimum Points to Redeem</label>
                <input type="number" min="1" class="form-control" id="loyalty-min" value="${s.loyalty_min_redeem}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Return Period (days)</label>
                <input type="number" min="1" class="form-control" id="return-days" value="${s.return_period_days}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Default Low Stock Threshold</label>
                <input type="number" min="1" class="form-control" id="low-stock-threshold" value="${s.low_stock_threshold}" required>
                <div class="form-text">Branch Admins control individual product alerts from Low Stock Alert Settings.</div>
              </div>
              <div class="col-12">
                <button class="btn btn-primary" type="submit">
                  <i class="bi bi-check-circle me-1"></i>Save Branch Settings
                </button>
              </div>
            </form>
          </div>
        </div>`;

      document.getElementById('settings-branch').addEventListener('change', async (event) => {
        selectedBranchId = event.target.value;
        await loadSettings();
      });

      document.getElementById('settings-form').addEventListener('submit', async (event) => {
        event.preventDefault();

        await apiPut('/api/settings', {
          branch_id: Number(selectedBranchId),
          loyalty_spend_per_point: Number(document.getElementById('loyalty-spend').value),
          loyalty_point_value: Number(document.getElementById('loyalty-value').value),
          loyalty_min_redeem: Number(document.getElementById('loyalty-min').value),
          return_period_days: Number(document.getElementById('return-days').value),
          low_stock_threshold: Number(document.getElementById('low-stock-threshold').value),
        });

        showFlash(flash, 'success', 'Branch settings updated successfully.');
        await loadSettings();
      });
    };

    const { branches } = await apiGet('/api/branches');
    selectedBranchId = branches?.length ? String(branches[0].id) : '';
    await loadSettings();
  },
});
