initPageShell({
  title: 'Returns & Exchanges',
  activeNav: 'returns',
  roles: ['cashier'],
  render: async (container, flash) => {
    container.innerHTML = `
      <div class="card mb-4"><div class="card-body">
        <form id="lookup-form" class="row g-2">
          <div class="col-md-4"><input type="number" class="form-control" id="sale-id" placeholder="Sale / Invoice ID" required></div>
          <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Lookup</button></div>
        </form>
      </div></div>
      <div id="sale-area"></div>`;

    const renderSale = (data) => {
      const area = document.getElementById('sale-area');
      area.innerHTML = `
        <p class="text-muted">Return period: ${data.return_period_days} days from purchase.</p>
        <div class="card"><div class="card-header">Sale #${data.sale.id} — ${escapeHtml(data.sale.created_at)} — Total: ${formatMoney(data.sale.total)}</div>
          <div class="table-responsive"><table class="table mb-0"><thead><tr>
            <th>Product</th><th>Barcode</th><th>Qty</th><th>Unit Price</th><th>Return</th><th>Exchange</th>
          </tr></thead><tbody>
            ${data.items.map((item) => `<tr>
              <td>${escapeHtml(item.name)}</td>
              <td><code>${escapeHtml(item.barcode)}</code></td>
              <td>${item.qty}</td>
              <td>${formatMoney(item.unit_price)}</td>
              <td>
                <div class="d-flex gap-1">
                  <input type="number" class="form-control form-control-sm return-qty" data-item="${item.id}" value="1" min="1" max="${item.qty}" style="width:70px">
                  <input type="text" class="form-control form-control-sm return-reason" placeholder="Reason">
                  <button class="btn btn-sm btn-outline-danger return-btn" data-item="${item.id}">Return</button>
                </div>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <input type="number" class="form-control form-control-sm exchange-qty" data-item="${item.id}" value="1" min="1" max="${item.qty}" style="width:70px">
                  <select class="form-select form-select-sm exchange-product">
                    <option value="">New item</option>
                    ${data.exchange_products.map((p) => `<option value="${p.id}">${escapeHtml(p.name)} (${formatMoney(p.price)})</option>`).join('')}
                  </select>
                  <button class="btn btn-sm btn-outline-primary exchange-btn" data-item="${item.id}">Exchange</button>
                </div>
              </td>
            </tr>`).join('')}
          </tbody></table></div>
        </div>`;

      area.querySelectorAll('.return-btn').forEach((btn) => {
        btn.onclick = async () => {
          const row = btn.closest('tr');
          try {
            const res = await apiPost('/api/returns', {
              type: 'return',
              sale_id: data.sale.id,
              sale_item_id: Number(btn.dataset.item),
              qty: Number(row.querySelector('.return-qty').value),
              reason: row.querySelector('.return-reason').value.trim(),
            });
            showFlash(flash, 'success', res.message);
          } catch (err) {
            showFlash(flash, 'danger', err.message);
          }
        };
      });

      area.querySelectorAll('.exchange-btn').forEach((btn) => {
        btn.onclick = async () => {
          const row = btn.closest('tr');
          try {
            const res = await apiPost('/api/returns', {
              type: 'exchange',
              sale_id: data.sale.id,
              sale_item_id: Number(btn.dataset.item),
              qty: Number(row.querySelector('.exchange-qty').value),
              reason: '',
              exchange_product_id: Number(row.querySelector('.exchange-product').value),
            });
            showFlash(flash, 'success', res.message);
          } catch (err) {
            showFlash(flash, 'danger', err.message);
          }
        };
      });
    };

    document.getElementById('lookup-form').onsubmit = async (e) => {
      e.preventDefault();
      try {
        const saleId = document.getElementById('sale-id').value;
        const data = await apiGet(`/api/returns/lookup?sale_id=${saleId}`);
        renderSale(data);
      } catch (err) {
        showFlash(flash, 'danger', err.message);
      }
    };
  },
});
