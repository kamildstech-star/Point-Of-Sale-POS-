import { afterEach, expect, it, vi } from 'vitest';
import { authApi, client, errorMessage } from '../api/client';
afterEach(() => vi.restoreAllMocks());

it('initializes CSRF before login and uses credentialed XSRF requests', async () => {
  const calls = [];
  vi.spyOn(client, 'get').mockImplementation(async path => { calls.push(path); return {}; });
  vi.spyOn(client, 'post').mockImplementation(async path => { calls.push(path); return { data: { data: { id: 1 } } }; });
  expect(await authApi.login({ email: 'staff@example.test', password: 'test-password' })).toEqual({ id: 1 });
  expect(calls).toEqual(['/sanctum/csrf-cookie', '/api/v1/auth/login']);
  expect(client.defaults.withCredentials).toBe(true);
  expect(client.defaults.withXSRFToken).toBe(true);
});

it('refreshes CSRF before a POST logout', async () => {
  const get = vi.spyOn(client, 'get').mockResolvedValue({});
  const post = vi.spyOn(client, 'post').mockResolvedValue({});
  await authApi.logout();
  expect(get).toHaveBeenCalledWith('/sanctum/csrf-cookie');
  expect(post).toHaveBeenCalledWith('/api/v1/auth/logout');
});

it('gives useful messages for expired sessions and rate limits', () => {
  expect(errorMessage({ response: { status: 419 } })).toMatch(/session expired/);
  expect(errorMessage({ response: { status: 429 } })).toMatch(/Too many attempts/);
});
