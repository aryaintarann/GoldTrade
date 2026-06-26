const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

export const fetchCsrfCookie = () =>
  fetch(`${API_URL}/sanctum/csrf-cookie`, {
    credentials: 'include',
    mode: 'cors',
  });

export async function apiLogin(email: string, password: string) {
  await fetchCsrfCookie();

  const csrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1];

  const response = await fetch(`${API_URL}/login`, {
    method: 'POST',
    credentials: 'include',
    mode: 'cors',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-XSRF-TOKEN': csrfToken ? decodeURIComponent(csrfToken) : '',
    },
    body: JSON.stringify({ email, password }),
  });

  if (!response.ok) throw new Error('Login failed');
  return response.json();
}

export async function apiLogout() {
  const csrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1];

  await fetch(`${API_URL}/logout`, {
    method: 'POST',
    credentials: 'include',
    mode: 'cors',
    headers: {
      'Accept': 'application/json',
      'X-XSRF-TOKEN': csrfToken ? decodeURIComponent(csrfToken) : '',
    },
  });
}