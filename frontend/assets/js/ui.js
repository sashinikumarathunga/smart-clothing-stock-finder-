function formatMoney(amount) {
  return `Rs. ${Number(amount).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function ensureIconFont() {
  if (document.getElementById('bi-icons')) return;
  const link = document.createElement('link');
  link.id = 'bi-icons';
  link.rel = 'stylesheet';
  link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css';
  document.head.appendChild(link);
}

function toastStack() {
  let stack = document.getElementById('toast-stack');
  if (!stack) {
    stack = document.createElement('div');
    stack.id = 'toast-stack';
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
  }
  return stack;
}

const TOAST_META = {
  success: { icon: 'check-circle-fill' },
  danger: { icon: 'exclamation-octagon-fill' },
  warning: { icon: 'exclamation-triangle-fill' },
  info: { icon: 'info-circle-fill' },
};

function showToast(type, message) {
  const meta = TOAST_META[type] || TOAST_META.info;
  const toast = document.createElement('div');
  toast.className = `app-toast app-toast--${type}`;
  toast.innerHTML = `<i class="bi bi-${meta.icon}"></i><span class="app-toast__msg">${escapeHtml(message)}</span>
    <button class="app-toast__close" aria-label="Dismiss"><i class="bi bi-x"></i></button>`;
  toastStack().appendChild(toast);

  const dismiss = () => {
    toast.classList.add('app-toast--out');
    setTimeout(() => toast.remove(), 250);
  };

  toast.querySelector('.app-toast__close').addEventListener('click', dismiss);
  requestAnimationFrame(() => toast.classList.add('app-toast--in'));
  setTimeout(dismiss, 4200);
}

// Backwards-compatible signature: showFlash(target, type, message).
// `target` is ignored in favour of stacked toasts for consistent UX.
function showFlash(_target, type, message) {
  showToast(type, message);
}

function renderLoading(label = 'Loading…') {
  return `<div class="state-block"><div class="spinner-border text-primary" role="status"></div>
    <p class="state-block__text">${escapeHtml(label)}</p></div>`;
}

function renderEmpty(message = 'No records found.', icon = 'inbox') {
  return `<div class="state-block"><i class="bi bi-${icon} state-block__icon"></i>
    <p class="state-block__text">${escapeHtml(message)}</p></div>`;
}

function statusBadge(value) {
  const key = String(value).toLowerCase();
  const map = {
    active: 'success',
    delivered: 'success',
    completed: 'success',
    pending: 'warning',
    expired: 'secondary',
    cancelled: 'danger',
    inactive: 'secondary',
    return: 'danger',
    exchange: 'info',
    cash: 'success',
    card: 'info',
  };
  const tone = map[key] || 'secondary';
  return `<span class="badge text-bg-${tone} badge-soft">${escapeHtml(value)}</span>`;
}

function navItemsForRole(role) {
  const items = [{ href: 'dashboard.html', label: 'Dashboard', id: 'dashboard', icon: 'speedometer2' }];

  if (role === 'owner') {
    items.push(
      { href: 'branches.html', label: 'Branches', id: 'branches', icon: 'shop' },
      { href: 'users.html', label: 'Branch Admins', id: 'users', icon: 'people' },
      { href: 'inventory.html', label: 'All Inventory', id: 'inventory', icon: 'box-seam' },
      { href: 'suppliers.html', label: 'Suppliers', id: 'suppliers', icon: 'truck' },
      { href: 'sales.html', label: 'All Sales', id: 'sales', icon: 'cash-stack' },
      { href: 'reports.html', label: 'Reports', id: 'reports', icon: 'bar-chart' }
    );
  }

  if (role === 'branch_admin') {
    items.push(
      { href: 'users.html', label: 'Staff', id: 'users', icon: 'people' },
      { href: 'inventory.html', label: 'Branch Inventory', id: 'inventory', icon: 'box-seam' },
      { href: 'suppliers.html', label: 'Suppliers', id: 'suppliers', icon: 'truck' },
      { href: 'settings.html', label: 'Branch Settings', id: 'settings', icon: 'gear' },
      { href: 'sales.html', label: 'Branch Sales', id: 'sales', icon: 'cash-stack' },
      { href: 'reports.html', label: 'Reports', id: 'reports', icon: 'bar-chart' }
    );
  }

  if (role === 'storekeeper') {
    items.push(
      { href: 'inventory.html', label: 'Inventory', id: 'inventory', icon: 'box-seam' },
      { href: 'suppliers.html', label: 'Suppliers', id: 'suppliers', icon: 'truck' },
      { href: 'purchase-orders.html', label: 'Purchase Orders', id: 'purchase-orders', icon: 'receipt' }
    );
  }

  if (role === 'sales_assistant') {
    items.push(
      { href: 'search.html', label: 'Stock Search', id: 'search', icon: 'search' },
      { href: 'reservations.html', label: 'Reservations', id: 'reservations', icon: 'bookmark-check' }
    );
  }

  if (role === 'cashier') {
    items.push(
      { href: 'pos.html', label: 'Point of Sale', id: 'pos', icon: 'cart3' },
      { href: 'customers.html', label: 'Customers', id: 'customers', icon: 'person-badge' },
      { href: 'returns.html', label: 'Returns & Exchanges', id: 'returns', icon: 'arrow-return-left' },
      { href: 'sales.html', label: 'My Sales', id: 'sales', icon: 'cash-stack' }
    );
  }

  return items;
}

function renderSidebar(role, activeId) {
  const items = navItemsForRole(role);
  return `<aside class="app-sidebar" id="app-sidebar">
    <nav class="side-nav">
      ${items
        .map(
          (item) => `<a href="${item.href}" class="side-nav__item ${item.id === activeId ? 'is-active' : ''}">
            <i class="bi bi-${item.icon}"></i><span>${escapeHtml(item.label)}</span></a>`
        )
        .join('')}
    </nav>
  </aside>`;
}

function userInitials(name) {
  return String(name || '?')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('');
}

function renderPageShell({ title, activeNav, content }) {
  const user = getCurrentUser();
  if (!user) return '';

  return `<div class="nav-backdrop" id="nav-backdrop"></div>
  <header class="topbar">
    <div class="topbar__left">
      <button class="topbar__toggle" id="nav-toggle" type="button" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
      <a class="topbar__brand" href="dashboard.html"><i class="bi bi-bag-check-fill"></i><span>Smart Stock Finder</span></a>
    </div>
    <div class="topbar__right">
      <div class="user-chip">
        <span class="user-chip__avatar">${escapeHtml(userInitials(user.full_name))}</span>
        <span class="user-chip__meta">
          <span class="user-chip__name">${escapeHtml(user.full_name)}</span>
          <span class="user-chip__role">${escapeHtml(roleLabel(user.role))}${user.branch_name ? ' · ' + escapeHtml(user.branch_name) : ''}</span>
        </span>
      </div>
      <button class="btn btn-logout" id="logout-btn" type="button"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline">Logout</span></button>
    </div>
  </header>
  <div class="app-body">
    ${renderSidebar(user.role, activeNav)}
    <main class="app-main">
      <h1 class="app-title">${escapeHtml(title)}</h1>
      <div id="flash-area"></div>
      ${content}
    </main>
  </div>`;
}

function initPageShell({ title, activeNav, roles, render }) {
  ensureIconFont();
  const user = roles ? requireRole(roles) : requireAuth();
  if (!user) return;

  const app = document.getElementById('app');
  app.innerHTML = renderPageShell({ title, activeNav, content: `<div id="page-content">${renderLoading()}</div>` });

  document.getElementById('logout-btn')?.addEventListener('click', () => logout());

  const sidebar = document.getElementById('app-sidebar');
  const backdrop = document.getElementById('nav-backdrop');
  const closeNav = () => {
    sidebar?.classList.remove('is-open');
    backdrop?.classList.remove('is-open');
  };
  document.getElementById('nav-toggle')?.addEventListener('click', () => {
    sidebar?.classList.toggle('is-open');
    backdrop?.classList.toggle('is-open');
  });
  backdrop?.addEventListener('click', closeNav);

  render(document.getElementById('page-content'), document.getElementById('flash-area'), user)
    .catch((err) => showToast('danger', err.message));
}

function renderTable(headers, rows, emptyMessage = 'No records found.') {
  if (!rows.length) return renderEmpty(emptyMessage);

  return `<div class="table-responsive"><table class="table app-table align-middle mb-0"><thead><tr>
    ${headers.map((h) => `<th>${escapeHtml(h.label)}</th>`).join('')}
  </tr></thead><tbody>
    ${rows
      .map(
        (row) => `<tr>${headers
          .map((h) => `<td>${h.render ? h.render(row) : escapeHtml(row[h.key])}</td>`)
          .join('')}</tr>`
      )
      .join('')}
  </tbody></table></div>`;
}
