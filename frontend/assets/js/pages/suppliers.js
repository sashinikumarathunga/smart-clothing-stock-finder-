initPageShell({
  title: 'Suppliers',
  activeNav: 'suppliers',
  roles: ['owner', 'branch_admin', 'storekeeper'],
  render: async (container, flash) => {
    const load = async () => {
      const { suppliers } = await apiGet('/api/suppliers');
      container.innerHTML = `
        <div class="d-flex justify-content-end mb-3"><button class="btn btn-primary" id="add-supplier"><i class="bi bi-plus-lg me-1"></i>Add Supplier</button></div>
        <div class="card">${renderTable(
          [
            { key: 'name', label: 'Name' },
            { key: 'contact_person', label: 'Contact' },
            { key: 'phone', label: 'Phone' },
            { key: 'email', label: 'Email' },
            {
              key: 'id',
              label: 'Actions',
              render: (r) => `<button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="${r.id}">Edit</button>
                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${r.id}">Delete</button>`,
            },
          ],
          suppliers
        )}</div>
        <div class="modal fade" id="supplierModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
          <form id="supplier-form"><div class="modal-header"><h5 class="modal-title" id="modal-title">Add Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" id="supplier-id">
            <div class="mb-3"><label class="form-label">Name</label><input class="form-control" id="name" required></div>
            <div class="mb-3"><label class="form-label">Contact Person</label><input class="form-control" id="contact-person"></div>
            <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" id="phone"></div>
            <div class="mb-3"><label class="form-label">Email</label><input class="form-control" id="email"></div>
            <div class="mb-3"><label class="form-label">Address</label><input class="form-control" id="address"></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div></form>
        </div></div></div>`;

      const modal = new bootstrap.Modal(document.getElementById('supplierModal'));
      const openModal = (s) => {
        document.getElementById('modal-title').textContent = s ? 'Edit Supplier' : 'Add Supplier';
        document.getElementById('supplier-id').value = s?.id || '';
        document.getElementById('name').value = s?.name || '';
        document.getElementById('contact-person').value = s?.contact_person || '';
        document.getElementById('phone').value = s?.phone || '';
        document.getElementById('email').value = s?.email || '';
        document.getElementById('address').value = s?.address || '';
        modal.show();
      };

      document.getElementById('add-supplier').onclick = () => openModal(null);
      container.querySelectorAll('.edit-btn').forEach((btn) => {
        btn.onclick = () => openModal(suppliers.find((s) => String(s.id) === btn.dataset.id));
      });
      container.querySelectorAll('.delete-btn').forEach((btn) => {
        btn.onclick = async () => {
          if (!confirm('Delete this supplier?')) return;
          await apiDelete(`/api/suppliers/${btn.dataset.id}`);
          showFlash(flash, 'success', 'Supplier deleted');
          await load();
        };
      });

      document.getElementById('supplier-form').onsubmit = async (e) => {
        e.preventDefault();
        const id = document.getElementById('supplier-id').value;
        const body = {
          name: document.getElementById('name').value.trim(),
          contact_person: document.getElementById('contact-person').value.trim(),
          phone: document.getElementById('phone').value.trim(),
          email: document.getElementById('email').value.trim(),
          address: document.getElementById('address').value.trim(),
        };
        if (id) await apiPut(`/api/suppliers/${id}`, body);
        else await apiPost('/api/suppliers', body);
        modal.hide();
        showFlash(flash, 'success', id ? 'Supplier updated' : 'Supplier created');
        await load();
      };
    };

    await load();
  },
});
