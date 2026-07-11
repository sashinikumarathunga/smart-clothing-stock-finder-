initPageShell({
  title: 'Branches',
  activeNav: 'branches',
  roles: ['owner'],
  render: async (container, flash) => {
    const load = async () => {
      const { branches } = await apiGet('/api/branches');
      container.innerHTML = `
        <div class="d-flex justify-content-end mb-3"><button class="btn btn-primary" id="add-branch"><i class="bi bi-plus-lg me-1"></i>Add Branch</button></div>
        <div class="card" id="list-card">${renderTable(
          [
            { key: 'name', label: 'Name' },
            { key: 'location', label: 'Location' },
            { key: 'phone', label: 'Phone' },
            {
              key: 'id',
              label: 'Actions',
              render: (r) => `<button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="${r.id}">Edit</button>
                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${r.id}">Delete</button>`,
            },
          ],
          branches
        )}</div>
        <div class="modal fade" id="branchModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
          <form id="branch-form"><div class="modal-header"><h5 class="modal-title" id="modal-title">Add Branch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" id="branch-id">
            <div class="mb-3"><label class="form-label">Name</label><input class="form-control" id="branch-name" required></div>
            <div class="mb-3"><label class="form-label">Location</label><input class="form-control" id="branch-location" required></div>
            <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" id="branch-phone" required></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div></div></form>
        </div></div></div>`;

      const modal = new bootstrap.Modal(document.getElementById('branchModal'));
      const openModal = (branch) => {
        document.getElementById('modal-title').textContent = branch ? 'Edit Branch' : 'Add Branch';
        document.getElementById('branch-id').value = branch?.id || '';
        document.getElementById('branch-name').value = branch?.name || '';
        document.getElementById('branch-location').value = branch?.location || '';
        document.getElementById('branch-phone').value = branch?.phone || '';
        modal.show();
      };

      document.getElementById('add-branch').onclick = () => openModal(null);
      container.querySelectorAll('.edit-btn').forEach((btn) => {
        btn.onclick = () => {
          const b = branches.find((x) => String(x.id) === btn.dataset.id);
          openModal(b);
        };
      });
      container.querySelectorAll('.delete-btn').forEach((btn) => {
        btn.onclick = async () => {
          if (!confirm('Delete this branch?')) return;
          await apiDelete(`/api/branches/${btn.dataset.id}`);
          showFlash(flash, 'success', 'Branch deleted');
          await load();
        };
      });

      document.getElementById('branch-form').onsubmit = async (e) => {
        e.preventDefault();
        const id = document.getElementById('branch-id').value;
        const body = {
          name: document.getElementById('branch-name').value.trim(),
          location: document.getElementById('branch-location').value.trim(),
          phone: document.getElementById('branch-phone').value.trim(),
        };
        if (id) await apiPut(`/api/branches/${id}`, body);
        else await apiPost('/api/branches', body);
        modal.hide();
        showFlash(flash, 'success', id ? 'Branch updated' : 'Branch created');
        await load();
      };
    };

    await load();
  },
});
