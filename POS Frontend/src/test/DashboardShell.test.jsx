import { act, cleanup, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import { AuthProvider } from '../auth/AuthProvider';
import { authApi } from '../api/client';
import { workspaceApi } from '../api/workspace';
import App from '../app/App';

vi.mock('../api/client', async original => ({ ...(await original()), authApi: { me: vi.fn(), logout: vi.fn(), tenants: vi.fn(), activate: vi.fn() } }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn(), remove: vi.fn(), image: id => `/images/${id}` } }));
const permissions = ['dashboard.view', 'products.view', 'products.manage', 'team.view', 'team.manage', 'settings.manage', 'sales.create', 'sales.view', 'inventory.view', 'purchases.view', 'customers.view', 'cash.open', 'reports.view', 'billing.manage', 'alerts.view'];
const owner = { id: 71, name: 'Owner Example', email: 'owner@example.test', role: { id: 1, code: 'owner', name: 'Owner' }, permissions, active_tenant: { id: 1, name: 'First Shop', currency_code: 'PKR', timezone: 'Asia/Karachi' } };
const second = { id: 2, name: 'Second Shop', currency_code: 'PKR' };
const page = rows => ({ data: rows, total: rows.length, last_page: 1 });
let media, listeners;
function open(path = '/') { return render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><AuthProvider><App/></AuthProvider></MemoryRouter>); }
beforeEach(() => {
  vi.resetAllMocks(); localStorage.clear(); listeners = new Set();
  media = { matches: false, addEventListener: (_, listener) => listeners.add(listener), removeEventListener: (_, listener) => listeners.delete(listener) };
  vi.stubGlobal('matchMedia', () => media);
  authApi.me.mockResolvedValue(owner); authApi.logout.mockResolvedValue();
  authApi.tenants.mockResolvedValue({ data: [owner.active_tenant, second] });
  workspaceApi.get.mockImplementation(path => Promise.resolve(path === 'me/context'
    ? { data: { active_tenant: { id: 1, permissions: ['dashboard.view'], dashboard: { profile: null } } } }
    : page([{ id: 1, name: 'First product', product_type: 'simple', active: true }])));
});
afterEach(() => { cleanup(); vi.unstubAllGlobals(); localStorage.clear(); });

describe('D1 shared tenant shell', () => {
  it('uses effective permissions even if a role name claims super-admin access', async () => {
    authApi.me.mockResolvedValue({ ...owner, role: { id: 1, name: 'Super Admin' }, permissions: ['dashboard.view', 'products.view'] });
    // Initial session + permission-context loading can exceed the query's 1s
    // default on Windows; retain all assertions after the resolved view appears.
    open(); await screen.findByRole('heading', { name: 'Welcome, Owner.' }, { timeout: 10000 });
    const navigation = screen.getByRole('navigation', { name: 'Main navigation' });
    expect(within(navigation).getByRole('link', { name: 'Products' })).toBeInTheDocument();
    for (const label of ['Reports', 'Plans & billing', 'Suppliers', 'Team & roles', 'Point of sale']) expect(within(navigation).queryByRole('link', { name: label })).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Branch')).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /Manage tenants/ })).not.toBeInTheDocument();
    expect(authApi.tenants).not.toHaveBeenCalled(); expect(workspaceApi.get).toHaveBeenCalledTimes(1);
    expect(workspaceApi.get).toHaveBeenCalledWith('me/context', expect.any(Object));
  });

  it('persists collapsed navigation with accessible link names for this user', async () => {
    const view = open(); await screen.findByRole('heading', { name: 'Welcome, Owner.' });
    await userEvent.click(screen.getByRole('button', { name: 'Collapse navigation' }));
    expect(screen.getByRole('button', { name: 'Expand navigation' })).toHaveAttribute('aria-expanded', 'false');
    expect(screen.getByRole('navigation', { name: 'Main navigation' }).querySelector('a[href="/catalog"]')).toHaveAttribute('aria-label', 'Products');
    expect(localStorage.getItem('universal-pos:navigation:user:71')).toBe('collapsed');
    view.unmount(); open();
    expect(await screen.findByRole('button', { name: 'Expand navigation' })).toBeInTheDocument();
  });

  it('uses a focus-managed mobile drawer and closes it after navigation', async () => {
    media.matches = true; open(); await screen.findByRole('heading', { name: 'Welcome, Owner.' });
    expect(screen.queryByRole('navigation', { name: 'Main navigation' })).not.toBeInTheDocument();
    const trigger = screen.getByRole('button', { name: 'Open navigation' });
    await userEvent.click(trigger);
    const dialog = screen.getByRole('dialog', { name: 'Navigation' });
    expect(dialog).toContainElement(document.activeElement);
    const last = within(dialog).getByRole('link', { name: 'Workspace tools' });
    last.focus(); await userEvent.keyboard('{Tab}');
    expect(within(dialog).getByRole('button', { name: 'Close dialog' })).toHaveFocus();
    await userEvent.keyboard('{Escape}');
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument(); expect(trigger).toHaveFocus();
    await userEvent.click(trigger);
    await userEvent.click(within(screen.getByRole('dialog')).getByRole('link', { name: 'Products' }));
    expect(await screen.findByRole('heading', { name: 'Your products' })).toBeInTheDocument();
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument(); expect(document.body.style.overflow).toBe('');
  });

  it('restores the normal document when resizing from an open drawer to desktop', async () => {
    media.matches = true; open(); await screen.findByRole('heading', { name: 'Welcome, Owner.' });
    await userEvent.click(screen.getByRole('button', { name: 'Open navigation' }));
    act(() => { media.matches = false; listeners.forEach(listener => listener()); });
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(screen.getByRole('navigation', { name: 'Main navigation' })).toBeInTheDocument();
    expect(document.body.style.overflow).toBe('');
  });

  it('finds permitted destinations without pretending to search business records', async () => {
    authApi.me.mockResolvedValue({ ...owner, permissions: ['dashboard.view', 'products.view'] });
    open(); await screen.findByRole('heading', { name: 'Welcome, Owner.' });
    const trigger = screen.getByRole('button', { name: 'Search pages' }); await userEvent.click(trigger);
    const input = screen.getByLabelText('Search permitted pages');
    await userEvent.type(input, 'Reports'); expect(screen.getByRole('status')).toHaveTextContent('No permitted pages');
    expect(workspaceApi.get.mock.calls.filter(([path]) => path !== 'me/context')).toHaveLength(0);
    await userEvent.clear(input); await userEvent.type(input, 'Products');
    await userEvent.click(within(screen.getByRole('dialog')).getByRole('link', { name: 'Products' }));
    expect(await screen.findByRole('heading', { name: 'Your products' })).toBeInTheDocument();
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('switches a real membership, replaces tenant content and refreshes navigation grants', async () => {
    let switched = false;
    authApi.activate.mockImplementation(async () => { switched = true; return { ...owner, active_tenant: second, permissions: ['products.view'] }; });
    workspaceApi.get.mockImplementation(async () => page([{ id: switched ? 2 : 1, name: switched ? 'Second product' : 'First product', active: true }]));
    open('/catalog'); await screen.findByText('First product');
    await userEvent.click(screen.getByRole('button', { name: 'Choose business' }));
    const dialog = screen.getByRole('dialog', { name: 'Switch business' });
    await userEvent.selectOptions(await within(dialog).findByLabelText('Business'), '2');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Switch business' }));
    expect(await screen.findByText('Second product')).toBeInTheDocument();
    expect(authApi.activate).toHaveBeenCalledWith(2); expect(screen.queryByText('First product')).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Reports' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Add product' })).not.toBeInTheDocument();
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('keeps a refused business switch open with selection and an error', async () => {
    authApi.activate.mockRejectedValue({ response: { status: 403, data: { message: 'Business access was revoked.' } } });
    open('/catalog'); await screen.findByText('First product');
    await userEvent.click(screen.getByRole('button', { name: 'Choose business' }));
    const dialog = screen.getByRole('dialog');
    await userEvent.selectOptions(await within(dialog).findByLabelText('Business'), '2');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Switch business' }));
    expect(await within(dialog).findByRole('alert')).toHaveTextContent('Business access was revoked.');
    expect(within(dialog).getByLabelText('Business')).toHaveValue('2');
    expect(document.querySelector('.tenant-switcher')).toHaveTextContent('First Shop');
  });

  it('groups authorized destinations and marks the active dashboard without adding platform links', async () => {
    open(); await screen.findByRole('heading', { name: 'Welcome, Owner.' });
    const navigation = screen.getByRole('navigation', { name: 'Main navigation' });
    expect(within(navigation).getByRole('heading', { name: 'Workspace' })).toBeInTheDocument();
    expect(within(navigation).getByRole('heading', { name: 'Operations' })).toBeInTheDocument();
    expect(within(navigation).getByRole('heading', { name: 'Management' })).toBeInTheDocument();
    expect(within(navigation).getByRole('link', { name: 'Dashboard' })).toHaveAttribute('aria-current', 'page');
    expect(within(navigation).getByRole('link', { name: 'Products' }).closest('section')).toHaveAttribute('aria-label', 'Operations');
    expect(within(navigation).getByRole('link', { name: 'Reports' }).closest('section')).toHaveAttribute('aria-label', 'Management');
    expect(within(navigation).queryByRole('heading', { name: 'Platform' })).not.toBeInTheDocument();
    expect(screen.getAllByRole('button', { name: 'Choose business' })).toHaveLength(1);
    expect(screen.getByRole('button', { name: 'Choose business' }).closest('aside')).toBeInTheDocument();
  });

  it('retains the real business switch when navigation is collapsed', async () => {
    open('/catalog'); await screen.findByText('First product');
    await userEvent.click(screen.getByRole('button', { name: 'Collapse navigation' }));
    const trigger = screen.getByRole('button', { name: 'Choose business' });
    expect(trigger).toHaveAttribute('title', 'First Shop'); await userEvent.click(trigger);
    const dialog = screen.getByRole('dialog', { name: 'Switch business' });
    await within(dialog).findByRole('combobox', { name: 'Business' });
    await userEvent.keyboard('{Escape}'); expect(trigger).toHaveFocus();
  });

  it('switches business from the mobile drawer and restores the document after nested dialogs close', async () => {
    media.matches = true;
    authApi.activate.mockResolvedValue({ ...owner, active_tenant: second, permissions: ['products.view'] });
    open('/catalog'); await screen.findByText('First product');
    await userEvent.click(screen.getByRole('button', { name: 'Open navigation' }));
    await userEvent.click(within(screen.getByRole('dialog', { name: 'Navigation' })).getByRole('button', { name: 'Choose business' }));
    const dialog = screen.getByRole('dialog', { name: 'Switch business' });
    await userEvent.selectOptions(await within(dialog).findByLabelText('Business'), '2');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Switch business' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(authApi.activate).toHaveBeenCalledWith(2);
    expect(document.body.style.overflow).toBe(''); expect(document.querySelector('[inert]')).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Choose business' })).toHaveTextContent('Second Shop');
  });
});
