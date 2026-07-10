initPageShell({
  title: 'Users',
  activeNav: 'users',
  roles: ['owner', 'branch_admin'],
  render: async (container, flash, user) => {
    const isOwner = user.role === 'owner';
    const allowedRoles = isOwner ? ['branch_admin'] : ['storekeeper', 'sales_assistant', 'cashier'];
    let branches = [];

    if (isOwner) {
      const res = await apiGet('/api/branches');
      branches = res.branches;
    }

    const load = async () => {
      const { users } = await apiGet('/api/users');
      container.innerHTML = `
        <div class="d-flex justify-content-end mb-3"><button class="btn btn-primary" id="add-user"><i class="bi bi-person-plus me-1"></i>Add User</button></div>
        <div class="card">${renderTable(
          [
            { key: 'full_name', label: 'Name' },
            { key: 'username', label: 'Username' },
            { key: 'role', label: 'Role', render: (r) => `<span class="badge text-bg-primary badge-soft">${escapeHtml(roleLabel(r.role))}</span>` },
            { key: 'branch_name', label: 'Branch', render: (r) => r.branch_name || '-' },
            { key: 'is_active', label: 'Status', render: (r) => statusBadge(Number(r.is_active) === 1 ? 'active' : 'inactive') },
            {
              key: 'id',
              label: 'Actions',
              render: (r) => `<button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="${r.id}">Edit</button>
                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${r.id}">Delete</button>`,
            },
          ],
          users
        )}</div>
        <div class="modal fade" id="userModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
          <form id="user-form"><div class="modal-header"><h5 class="modal-title" id="modal-title">Add User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" id="user-id">
            <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" id="full-name" required></div>
            <div class="mb-3"><label class="form-label">Username</label><input class="form-control" id="username" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" id="password"><small class="text-muted">Leave blank to keep current (edit only)</small></div>
            <div class="mb-3"><label class="form-label">Role</label><select class="form-select" id="role" required>
              ${allowedRoles.map((r) => `<option value="${r}">${roleLabel(r)}</option>`).join('')}
            </select></div>
            ${isOwner ? `<div class="mb-3"><label class="form-label">Branch</label><select class="form-select" id="branch-id" required>
              <option value="">Select branch</option>${branches.map((b) => `<option value="${b.id}">${escapeHtml(b.name)}</option>`).join('')}
            </select></div>` : ''}
            <div class="form-check" id="active-wrap" style="display:none"><input class="form-check-input" type="checkbox" id="is-active" checked><label class="form-check-label">Active</label></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div></form>
        </div></div></div>`;

      const modal = new bootstrap.Modal(document.getElementById('userModal'));
      const openModal = (row) => {
        document.getElementById('modal-title').textContent = row ? 'Edit User' : 'Add User';
        document.getElementById('user-id').value = row?.id || '';
        document.getElementById('full-name').value = row?.full_name || '';
        document.getElementById('username').value = row?.username || '';
        document.getElementById('password').value = '';
        document.getElementById('role').value = row?.role || allowedRoles[0];
        if (isOwner) document.getElementById('branch-id').value = row?.branch_id || '';
        document.getElementById('active-wrap').style.display = row ? 'block' : 'none';
        document.getElementById('is-active').checked = row ? Number(row.is_active) === 1 : true;
        document.getElementById('password').required = !row;
        modal.show();
      };

      document.getElementById('add-user').onclick = () => openModal(null);
      container.querySelectorAll('.edit-btn').forEach((btn) => {
        btn.onclick = () => openModal(users.find((u) => String(u.id) === btn.dataset.id));
      });
      container.querySelectorAll('.delete-btn').forEach((btn) => {
        btn.onclick = async () => {
          if (!confirm('Delete this user?')) return;
          await apiDelete(`/api/users/${btn.dataset.id}`);
          showFlash(flash, 'success', 'User deleted');
          await load();
        };
      });

      document.getElementById('user-form').onsubmit = async (e) => {
        e.preventDefault();
        const id = document.getElementById('user-id').value;
        const body = {
          full_name: document.getElementById('full-name').value.trim(),
          username: document.getElementById('username').value.trim(),
          role: document.getElementById('role').value,
        };
        const password = document.getElementById('password').value;
        if (password) body.password = password;
        if (isOwner) body.branch_id = Number(document.getElementById('branch-id').value);
        if (id) body.is_active = document.getElementById('is-active').checked ? 1 : 0;

        if (id) await apiPut(`/api/users/${id}`, body);
        else await apiPost('/api/users', body);
        modal.hide();
        showFlash(flash, 'success', id ? 'User updated' : 'User created');
        await load();
      };
    };

    await load();
  },
});
