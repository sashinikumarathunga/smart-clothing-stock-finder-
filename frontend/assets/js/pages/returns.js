initPageShell({
  title: 'Returns & Exchanges',
  activeNav: 'returns',
  roles: ['cashier'],
  render: async (container, flash) => {

    const params = new URLSearchParams(window.location.search);
    const view = params.get('view');

    container.innerHTML = `
      <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="returns.html" class="btn ${view !== 'history' ? 'btn-primary' : 'btn-outline-primary'}">
          <i class="bi bi-arrow-left-right me-1"></i>
          New Return & Exchange
        </a>

        <a href="returns.html?view=history" class="btn ${view === 'history' ? 'btn-primary' : 'btn-outline-primary'}">
          <i class="bi bi-clock-history me-1"></i>
          Today's Returned Items
        </a>
      </div>

      <div id="return-content"></div>
    `;

    const content = document.getElementById('return-content');

    if (view === 'history') {

      try {
        const data = await apiGet('/api/returns');
        const returns = data.returns || [];

        if (!returns.length) {
          content.innerHTML = renderEmpty(
            'No return or exchange items today.',
            'arrow-left-right'
          );
          return;
        }

        content.innerHTML = `
          <div class="card">
            <div class="card-header">
              Today's Returned & Exchanged Items
            </div>

            ${renderTable(
              [
                {
                  key: 'sale_id',
                  label: 'Invoice',
                  render: (r) => `#${r.sale_id}`
                },
                {
                  key: 'returned_product',
                  label: 'Returned Item',
                  render: (r) => `
                    <div class="fw-semibold">${escapeHtml(r.returned_product)}</div>
                    <div class="small text-muted">
                      ${escapeHtml(r.returned_brand || '')}
                      ${r.returned_size ? ` | Size: ${escapeHtml(r.returned_size)}` : ''}
                      ${r.returned_color ? ` | ${escapeHtml(r.returned_color)}` : ''}
                    </div>
                  `
                },
                {
                  key: 'qty',
                  label: 'Qty'
                },
                {
                  key: 'exchange_product',
                  label: 'Exchange Item',
                  render: (r) => `
                    <div class="fw-semibold">
                      ${escapeHtml(r.exchange_product || '-')}
                    </div>

                    <div class="small text-muted">
                      ${escapeHtml(r.exchange_brand || '')}
                      ${r.exchange_size ? ` | Size: ${escapeHtml(r.exchange_size)}` : ''}
                      ${r.exchange_color ? ` | ${escapeHtml(r.exchange_color)}` : ''}
                    </div>
                  `
                },
                {
                  key: 'reason',
                  label: 'Reason',
                  render: (r) => escapeHtml(r.reason || '-')
                },
                {
                  key: 'price_difference',
                  label: 'Price Difference',
                  render: (r) => formatMoney(r.price_difference || 0)
                },
                {
                  key: 'created_at',
                  label: 'Date'
                }
              ],
              returns
            )}
          </div>
        `;

      } catch (err) {
        showFlash(flash, 'danger', err.message);
      }

      return;
    }

    content.innerHTML = `
      <div class="card mb-4">
        <div class="card-body">
          <form id="lookup-form" class="row g-2">
            <div class="col-md-4">
              <input
                type="number"
                class="form-control"
                id="sale-id"
                placeholder="Sale / Invoice ID"
                required
              >
            </div>

            <div class="col-md-2">
              <button class="btn btn-primary w-100" type="submit">
                Lookup
              </button>
            </div>
          </form>
        </div>
      </div>

      <div id="sale-area"></div>
    `;

    const renderSale = (data) => {
      const area = document.getElementById('sale-area');
      const carts = new Map();

      const productById = new Map(
        (data.exchange_products || []).map((product) => [Number(product.id), product])
      );

      const calculateCart = (saleItemId) => {
        const rows = carts.get(saleItemId) || [];
        return rows.reduce((sum, cartItem) => {
          const product = productById.get(Number(cartItem.product_id));
          return sum + (Number(product?.price || 0) * Number(cartItem.qty || 0));
        }, 0);
      };

      const renderCart = (saleItemId, returnedValue) => {
        const cartArea = area.querySelector(`.exchange-cart[data-item="${saleItemId}"]`);
        if (!cartArea) return;

        const rows = carts.get(saleItemId) || [];
        const total = calculateCart(saleItemId);
        const remaining = Math.max(returnedValue - total, 0);
        const additional = Math.max(total - returnedValue, 0);

        cartArea.innerHTML = `
          <div class="border rounded p-2 bg-light mt-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong>Exchange Cart</strong>
              <span class="small text-muted">Returned value: ${formatMoney(returnedValue)}</span>
            </div>

            ${rows.length ? `
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                  <thead><tr><th>Replacement Product</th><th>Price</th><th></th></tr></thead>
                  <tbody>
                    ${rows.map((cartItem, index) => {
                      const product = productById.get(Number(cartItem.product_id));
                      const lineTotal = Number(product?.price || 0) * Number(cartItem.qty || 0);
                      return `<tr>
                        <td>
                          <strong>${escapeHtml(product?.name || '')}</strong>
                          <div class="small text-muted">
                            ${escapeHtml(product?.style_code || '')}
                            ${product?.size ? ` | Size: ${escapeHtml(product.size)}` : ''}
                            ${product?.color ? ` | ${escapeHtml(product.color)}` : ''}
                          </div>
                        </td>
                        <td>${formatMoney(product?.price || 0)}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-exchange-item" data-index="${index}">Remove</button></td>
                      </tr>`;
                    }).join('')}
                  </tbody>
                </table>
              </div>
            ` : '<div class="small text-muted mb-2">No replacement items added yet.</div>'}

            <div class="d-flex flex-wrap gap-3 small">
              <strong>Replacement total: ${formatMoney(total)}</strong>
              ${remaining > 0
                ? `<span class="text-danger fw-semibold">Remaining exchange value: ${formatMoney(remaining)}</span>`
                : `<span class="text-success fw-semibold">Exchange value covered</span>`}
              ${additional > 0
                ? `<span class="text-primary fw-semibold">Additional amount to collect: ${formatMoney(additional)}</span>`
                : ''}
            </div>
          </div>
        `;

        cartArea.querySelectorAll('.remove-exchange-item').forEach((button) => {
          button.onclick = () => {
            const current = carts.get(saleItemId) || [];
            current.splice(Number(button.dataset.index), 1);
            carts.set(saleItemId, current);
            renderCart(saleItemId, returnedValue);
          };
        });
      };

      area.innerHTML = `
        <div class="alert alert-info">
          <strong>Exchange policy:</strong> A customer may choose multiple <strong>different replacement products</strong> for one returned value.
          Add each different product separately to the exchange cart. The replacement total must be equal to or greater than the returned value.
          No cash refund is given for unused exchange value. If the cart is higher, collect only the additional amount.
        </div>

        <div class="card"><div class="card-header">Sale #${data.sale.id} — ${escapeHtml(data.sale.created_at)} — Total: ${formatMoney(data.sale.total)}</div>
          <div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr>
            <th>Returned Product</th><th>Barcode</th><th>Purchased Qty</th><th>Available</th><th>Unit Price</th><th>Exchange</th>
          </tr></thead><tbody>
            ${data.items.map((item) => {
              const remainingQty = Number(item.remaining_qty || 0);
              const disabled = remainingQty < 1;
              return `<tr data-sale-item="${item.id}">
                <td>${escapeHtml(item.name)}</td>
                <td><code>${escapeHtml(item.barcode)}</code></td>
                <td>${item.qty}</td>
                <td>${remainingQty}</td>
                <td>${formatMoney(item.unit_price)}</td>
                <td style="min-width:520px">
                  ${disabled ? '<span class="badge text-bg-secondary">Already exchanged</span>' : `
                    <div class="row g-2">
                      <div class="col-sm-3">
                        <label class="form-label small mb-1">Return Qty</label>
                        <input type="number" class="form-control form-control-sm exchange-qty" value="1" min="1" max="${remainingQty}">
                      </div>
                      <div class="col-sm-8">
                        <label class="form-label small mb-1">Add Different Replacement Product</label>
                        <select class="form-select form-select-sm exchange-product">
                          <option value="">Select a product</option>
                          ${(data.exchange_products || []).map((p) => `
                            <option value="${p.id}">
                              ${escapeHtml(p.name)} | ${escapeHtml(p.style_code || '')} | Size: ${escapeHtml(p.size || '')} | ${escapeHtml(p.color || '')} | Stock: ${Number(p.quantity || 0)} | ${formatMoney(p.price)}
                            </option>
                          `).join('')}
                        </select>
                        <div class="form-text">Choose another product and press + again to add it to this exchange.</div>
                      </div>
                      <div class="col-sm-1 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-primary add-exchange-item" title="Add to exchange cart">+</button>
                      </div>
                    </div>

                    <div class="exchange-cart" data-item="${item.id}"></div>

                    <div class="mt-2 d-flex gap-2">
                      <input type="text" class="form-control form-control-sm exchange-reason" placeholder="Reason for exchange">
                      <button type="button" class="btn btn-sm btn-primary exchange-btn" data-item="${item.id}">Complete Exchange</button>
                    </div>
                  `}
                </td>
              </tr>`;
            }).join('')}
          </tbody></table></div>
        </div>`;

      data.items.forEach((item) => {
        const saleItemId = Number(item.id);
        const row = area.querySelector(`tr[data-sale-item="${saleItemId}"]`);
        if (!row || Number(item.remaining_qty || 0) < 1) return;

        carts.set(saleItemId, []);

        const returnQtyInput = row.querySelector('.exchange-qty');
        const returnedValueForCurrentQty = () => Number(item.unit_price || 0) * Number(returnQtyInput.value || 0);

        renderCart(saleItemId, returnedValueForCurrentQty());

        returnQtyInput.oninput = () => {
          renderCart(saleItemId, returnedValueForCurrentQty());
        };

        row.querySelector('.add-exchange-item').onclick = () => {
          try {
            const select = row.querySelector('.exchange-product');
            const productId = Number(select.value);
            const product = productById.get(productId);

            if (!productId || !product) {
              throw new Error('Please select a replacement product.');
            }

            if (Number(product.quantity || 0) < 1) {
              throw new Error('This replacement product is out of stock.');
            }

            const current = carts.get(saleItemId) || [];
            const existing = current.find((entry) => Number(entry.product_id) === productId);

            if (existing) {
              throw new Error('This product is already in the exchange cart. Please choose a different product.');
            }

            // Each cart row represents one different replacement product.
            current.push({ product_id: productId, qty: 1 });

            carts.set(saleItemId, current);
            select.value = '';
            renderCart(saleItemId, returnedValueForCurrentQty());
          } catch (err) {
            showFlash(flash, 'danger', err.message);
          }
        };

        row.querySelector('.exchange-btn').onclick = async () => {
          try {
            const returnQty = Number(returnQtyInput.value);
            const returnedValue = Number(item.unit_price || 0) * returnQty;
            const exchangeItems = carts.get(saleItemId) || [];
            const cartTotal = calculateCart(saleItemId);

            if (!Number.isInteger(returnQty) || returnQty <= 0 || returnQty > Number(item.remaining_qty || 0)) {
              throw new Error(`Return quantity must be between 1 and ${Number(item.remaining_qty || 0)}.`);
            }
            if (!exchangeItems.length) {
              throw new Error('Add at least one item to the exchange cart.');
            }
            if (cartTotal + 0.00001 < returnedValue) {
              throw new Error(`Please add ${formatMoney(returnedValue - cartTotal)} more to the exchange cart.`);
            }

            const res = await apiPost('/api/returns', {
              sale_id: data.sale.id,
              sale_item_id: saleItemId,
              qty: returnQty,
              reason: row.querySelector('.exchange-reason').value.trim(),
              exchange_items: exchangeItems,
            });

            showFlash(flash, 'success', res.message);
            document.getElementById('lookup-form').requestSubmit();
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
