import { describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { AuthProvider } from '../auth/AuthProvider';
import App from '../app/App';
import { authApi } from '../api/client';

vi.mock('../api/client', async importOriginal => ({ ...(await importOriginal()), authApi: { me: vi.fn(), login: vi.fn(), logout: vi.fn(), tenants: vi.fn() } }));
const staff = { id: 1, name: 'Staff Member', role: { name: 'Admin' }, permissions: ['dashboard.view'] };
const unauthorized = { response: { status: 401 } };
function open(path = '/') {
  return render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><AuthProvider><App /></AuthProvider></MemoryRouter>);
}

describe('workspace authentication and navigation', () => {
  it('redirects a guest to sign in, submits credentials and signs out', async () => {
    authApi.me.mockRejectedValueOnce(unauthorized);
    authApi.login.mockResolvedValueOnce(staff);
    authApi.logout.mockResolvedValueOnce();
    open('/workspace');
    const user = userEvent.setup();
    await user.type(await screen.findByLabelText('Email address'), 'staff@example.test');
    await user.type(screen.getByLabelText('Password'), 'test-password');
    authApi.tenants.mockResolvedValue({ data: [], meta: { active_tenant: null } });
    await user.click(screen.getByRole('button', { name: /Sign in to workspace/ }));
    expect(authApi.login).toHaveBeenCalledWith({ email: 'staff@example.test', password: 'test-password' });
    expect(await screen.findByRole('heading', { name: 'Your business workspace' })).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'Sign out' }));
    expect(await screen.findByLabelText('Password')).toBeInTheDocument();
    expect(document.title).toBe('Sign in · Universal POS');
  });

  it('shows server validation errors', async () => {
    authApi.me.mockRejectedValueOnce(unauthorized);
    authApi.login.mockRejectedValueOnce({ response: { status: 422, data: { message: 'The provided credentials are incorrect.' } } });
    open('/login');
    const user = userEvent.setup();
    await user.type(await screen.findByLabelText('Email address'), 'staff@example.test');
    await user.type(screen.getByLabelText('Password'), 'wrong');
    await user.click(screen.getByRole('button', { name: /Sign in to workspace/ }));
    expect(await screen.findByRole('alert')).toHaveTextContent('credentials are incorrect');
  });

  it('hides overview navigation and blocks the page when permission is missing', async () => {
    authApi.me.mockResolvedValueOnce({ ...staff, permissions: [] });
    open();
    expect(await screen.findByRole('heading', { name: 'Access restricted' })).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /Overview/ })).not.toBeInTheDocument();
  });

  it('keeps connection failures distinct from an unauthenticated session and retries', async () => {
    authApi.me.mockRejectedValueOnce(new Error('Offline')).mockResolvedValueOnce(staff);
    open();
    await userEvent.click(await screen.findByRole('button', { name: 'Try again' }));
    expect(await screen.findByRole('heading', { name: 'Welcome, Staff.' })).toBeInTheDocument();
  });

  it('shows a missing page for an unknown signed-in route', async () => {
    authApi.me.mockResolvedValueOnce(staff);
    open('/missing');
    expect(await screen.findByRole('heading', { name: 'Page not found' })).toBeInTheDocument();
  });

  it('retains the session view and reports failed logout', async () => {
    authApi.me.mockResolvedValueOnce(staff);
    authApi.logout.mockRejectedValueOnce(new Error('Offline'));
    open();
    await userEvent.click(await screen.findByRole('button', { name: 'Sign out' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('Unable to connect');
    await waitFor(() => expect(screen.getByRole('button', { name: 'Sign out' })).toBeEnabled());
  });
});
