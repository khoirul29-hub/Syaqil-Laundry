let csrf = '';
const API_BASE = (import.meta.env?.VITE_API_BASE_URL || '/api').replace(/\/$/, '');

export async function ownerApi(path, body, { timeout = 15000 } = {}) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeout);
  try {
    const url = path.startsWith('http') ? path : API_BASE + path;
    const res = await fetch(url, {
      signal: controller.signal,
      method: body === undefined ? 'GET' : 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrf,
      },
      ...(body !== undefined ? { body: JSON.stringify(body) } : {})
    });
    const data = await res.json();
    if (!res.ok) {
      const error = new Error(data.error || 'Permintaan gagal.');
      error.status = res.status;
      error.code = data.code;
      throw error;
    }
    if (data.csrf) csrf = data.csrf;
    return data;
  } finally {
    clearTimeout(timer);
  }
}
