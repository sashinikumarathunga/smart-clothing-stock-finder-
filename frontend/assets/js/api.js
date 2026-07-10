function getToken() {
  const session = JSON.parse(localStorage.getItem('ssf_session') || 'null');
  return session?.token || '';
}

async function apiRequest(method, path, body) {
  const headers = { 'Content-Type': 'application/json' };
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  const options = { method, headers };
  if (body !== undefined) options.body = JSON.stringify(body);

  const response = await fetch(`${API_BASE_URL}${path}`, options);
  let data = null;

  try {
    data = await response.json();
  } catch {
    data = { error: 'Invalid server response' };
  }

  if (response.status === 401) {
    localStorage.removeItem('ssf_session');
    const loginPath = window.location.pathname.includes('/pages/')
      ? '../index.html'
      : 'index.html';
    window.location.href = loginPath;
    throw new Error(data.error || 'Unauthorized');
  }

  if (!response.ok) {
    throw new Error(data.error || `Request failed (${response.status})`);
  }

  return data;
}

const apiGet = (path) => apiRequest('GET', path);
const apiPost = (path, body) => apiRequest('POST', path, body);
const apiPut = (path, body) => apiRequest('PUT', path, body);
const apiDelete = (path) => apiRequest('DELETE', path);
