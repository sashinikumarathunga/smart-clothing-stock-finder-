initPageShell({
  title: 'Reservations',
  activeNav: 'reservations',
  roles: ['sales_assistant'],
  render: async (container, flash) => {
    const load = async () => {
      const data = await apiGet('/api/reservations');
      container.innerHTML = `
        <div class="row g-4">
          <div class="col-lg-5"><div class="card"><div class="card-header">Reserve Item</div><div class="card-body">
            <form id="reserve-form">
              <div class="mb-3"><label class="form-label">Product</label><select class="form-select" id="product-id" required>
                <option value="">Select product</option>
                ${data.products.map((p) => `<option value="${p.id}">${escapeHtml(p.name)} (${escapeHtml(p.size)}/${escapeHtml(p.color)}) — Avail: ${p.available_qty}</option>`).join('')}
              </select></div>
              <div class="mb-3"><label class="form-label">Quantity</label><input type="number" min="1" class="form-control" id="qty" value="1" required></div>
              <div class="mb-3"><label class="form-label">Customer Name</label><input class="form-control" id="customer-name" required></div>
              <div class="mb-3"><label class="form-label">Customer Phone</label><input class="form-control" id="customer-phone" required></div>
              <button class="btn btn-primary w-100" type="submit">Reserve (1 hour)</button>
            </form>
          </div></div></div>
          <div class="col-lg-7"><div class="card"><div class="card-header">Active Reservations</div>
            ${renderTable(
              [
                { key: 'product_name', label: 'Product' },
                { key: 'customer_name', label: 'Customer' },
                { key: 'customer_phone', label: 'Phone' },
                { key: 'qty', label: 'Qty' },
                { key: 'expires_at', label: 'Expires' },
                {
                  key: 'id',
                  label: '',
                  render: (r) => `<button class="btn btn-sm btn-outline-danger cancel-btn" data-id="${r.id}">Cancel</button>`,
                },
              ],
              data.reservations
            )}
          </div></div>
        </div>`;

      document.getElementById('reserve-form').onsubmit = async (e) => {
        e.preventDefault();
        await apiPost('/api/reservations', {
          product_id: Number(document.getElementById('product-id').value),
          qty: Number(document.getElementById('qty').value),
          customer_name: document.getElementById('customer-name').value.trim(),
          customer_phone: document.getElementById('customer-phone').value.trim(),
        });
        showFlash(flash, 'success', 'Item reserved for 1 hour');
        await load();
      };

      container.querySelectorAll('.cancel-btn').forEach((btn) => {
        btn.onclick = async () => {
          await apiPost(`/api/reservations/${btn.dataset.id}/cancel`, {});
          showFlash(flash, 'success', 'Reservation cancelled');
          await load();
        };
      });
    };

    await load();
  },
});
