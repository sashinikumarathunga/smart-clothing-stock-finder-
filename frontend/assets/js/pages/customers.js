initPageShell({
  title: 'Loyalty Customers',
  activeNav: 'customers',
  roles: ['cashier'],
  render: async (container, flash) => {
    const load = async () => {
      const { customers } = await apiGet('/api/customers');
      container.innerHTML = `
        <div class="row g-4">
          <div class="col-lg-4"><div class="card"><div class="card-header">Register Customer</div><div class="card-body">
            <form id="customer-form">
              <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" id="full-name" required></div>
              <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" id="phone" required></div>
              <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="email"></div>
              <button class="btn btn-primary w-100" type="submit">Register</button>
            </form>
          </div></div></div>
          <div class="col-lg-8"><div class="card"><div class="card-header">Customer List</div>
            ${renderTable(
              [
                { key: 'full_name', label: 'Name' },
                { key: 'phone', label: 'Phone' },
                { key: 'email', label: 'Email' },
                { key: 'loyalty_points', label: 'Points' },
              ],
              customers
            )}
          </div></div>
        </div>`;

      document.getElementById('customer-form').onsubmit = async (e) => {
        e.preventDefault();
        try {
          await apiPost('/api/customers', {
            full_name: document.getElementById('full-name').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            email: document.getElementById('email').value.trim(),
          });
          showFlash(flash, 'success', 'Customer registered');
          await load();
        } catch (err) {
          showFlash(flash, 'danger', err.message);
        }
      };
    };

    await load();
  },
});
