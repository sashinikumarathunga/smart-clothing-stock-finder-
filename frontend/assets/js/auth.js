const SESSION_KEY = 'ssf_session';

function getSession() {
  try {
    return JSON.parse(localStorage.getItem(SESSION_KEY) || 'null');
  } catch {
    return null;
  }
}

function setSession(token, user) {
  localStorage.setItem(SESSION_KEY, JSON.stringify({ token, user }));
}

function clearSession() {
  localStorage.removeItem(SESSION_KEY);
}

function getCurrentUser() {
  return getSession()?.user || null;
}

function requireAuth() {
  if (!getCurrentUser()) {
    window.location.href = window.location.pathname.includes('/pages/')
      ? '../index.html'
      : 'index.html';
    return null;
  }
  return getCurrentUser();
}

function requireRole(roles) {
  const user = requireAuth();
  if (!user) return null;
  if (!roles.includes(user.role)) {
    window.location.href = 'dashboard.html';
    return null;
  }
  return user;
}

async function login(username, password) {
  const data = await apiPost('/api/auth/login', { username, password });
  setSession(data.token, data.user);
  return data.user;
}

async function logout() {
  try {
    await apiPost('/api/auth/logout', {});
  } catch {
    // ignore
  }
  clearSession();
  window.location.href = window.location.pathname.includes('/pages/')
    ? '../index.html'
    : 'index.html';
}

function roleLabel(role) {
  const labels = {
    owner: 'Owner',
    branch_admin: 'Branch Admin',
    storekeeper: 'Storekeeper',
    sales_assistant: 'Sales Assistant',
    cashier: 'Cashier',
  };
  return labels[role] || role;
}
