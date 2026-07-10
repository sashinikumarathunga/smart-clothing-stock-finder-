initPageShell({
  title: 'Sales',
  activeNav: 'sales',
  roles: ['owner', 'branch_admin', 'cashier'],
  render: async (container, flash, user) => {
    const { sales } = await apiGet('/api/sales');
    const showBranch = user.role === 'owner';

    container.innerHTML = `
      <div class="card">${renderTable(
        [
          { key: 'id', label: 'ID', render: (r) => `#${r.id}` },
          ...(showBranch ? [{ key: 'branch_name', label: 'Branch' }] : []),
          { key: 'cashier_name', label: 'Cashier' },
          { key: 'subtotal', label: 'Subtotal', render: (r) => formatMoney(r.subtotal) },
          { key: 'discount_amount', label: 'Discount', render: (r) => formatMoney(r.discount_amount) },
          { key: 'total', label: 'Total', render: (r) => formatMoney(r.total) },
          { key: 'payment_method', label: 'Payment', render: (r) => statusBadge(r.payment_method) },
          { key: 'created_at', label: 'Date' },
          {
            key: 'id',
            label: '',
            render: (r) => `<button class="btn btn-sm btn-outline-primary view-btn" data-id="${r.id}"><i class="bi bi-receipt me-1"></i>Invoice</button>`,
          },
        ],
        sales,
        'No sales recorded yet.'
      )}</div>
      <div id="invoice-modal" class="modal fade" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="invoice-title">Invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="invoice-body"></div>
      </div></div></div>`;

    const modal = new bootstrap.Modal(document.getElementById('invoice-modal'));

    container.querySelectorAll('.view-btn').forEach((btn) => {
      btn.onclick = async () => {
        try {
          const data = await apiGet(`/api/sales/${btn.dataset.id}`);
          document.getElementById('invoice-title').textContent = `Invoice #${data.sale.id}`;
          document.getElementById('invoice-body').innerHTML = `
            <p><strong>Branch:</strong> ${escapeHtml(data.sale.branch_name)}<br>
            <strong>Cashier:</strong> ${escapeHtml(data.sale.cashier_name)}<br>
            <strong>Date:</strong> ${escapeHtml(data.sale.created_at)}<br>
            <strong>Total:</strong> ${formatMoney(data.sale.total)}</p>
            ${renderTable(
              [
                { key: 'name', label: 'Item' },
                { key: 'barcode', label: 'Barcode', render: (r) => `<code>${escapeHtml(r.barcode)}</code>` },
                { key: 'qty', label: 'Qty' },
                { key: 'unit_price', label: 'Price', render: (r) => formatMoney(r.unit_price) },
                { key: 'line_total', label: 'Line Total', render: (r) => formatMoney(r.line_total) },
              ],
              data.items
            )}
            <button class="btn btn-primary no-print mt-3" onclick="window.print()">Print</button>`;
          modal.show();
        } catch (err) {
          showFlash(flash, 'danger', err.message);
        }
      };
    });
  },
});
