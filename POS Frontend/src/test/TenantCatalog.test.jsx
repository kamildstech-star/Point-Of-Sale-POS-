import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { AuthProvider } from '../auth/AuthProvider';
import App from '../app/App';
import { authApi } from '../api/client';
import { workspaceApi } from '../api/workspace';

vi.mock('../api/client', async importOriginal => ({ ...(await importOriginal()), authApi: { me: vi.fn(), login: vi.fn(), logout: vi.fn(), tenants: vi.fn(), activate: vi.fn(), createBusiness: vi.fn(), register: vi.fn() } }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn(), remove: vi.fn(), exportCatalog: vi.fn(), image: id => `/images/${id}` } }));
const owner = { id: 1, name: 'Owner', role: { name: 'Owner' }, permissions: ['dashboard.view', 'products.view', 'products.manage', 'team.view', 'team.manage', 'settings.manage', 'legacy.view', 'legacy.manage'], active_tenant: { id: 1, name: 'First Shop', currency_code: 'PKR', is_legacy: true } };
const page = (data = []) => ({ data, total: data.length, last_page: 1 });
function open(path) { return render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><AuthProvider><App/></AuthProvider></MemoryRouter>); }
beforeEach(() => { vi.resetAllMocks(); localStorage.clear(); authApi.me.mockResolvedValue(owner); });

describe('tenant and catalog screens', () => {
  it('switches businesses, refreshes permissions and removes old tenant navigation', async () => {
    const other = { id: 2, name: 'Second Shop', currency_code: 'PKR' };
    authApi.tenants.mockResolvedValue({ data: [owner.active_tenant, other] });
    authApi.activate.mockResolvedValue({ ...owner, active_tenant: other, permissions: ['products.view'] });
    open('/workspace');
    expect(await screen.findByRole('link', { name: /Team & roles/ })).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Switch to dark theme' }));
    await userEvent.click(await screen.findByRole('button', { name: 'Open business' }));
    await waitFor(() => expect(authApi.activate).toHaveBeenCalledWith(2));
    await waitFor(() => expect(screen.queryByRole('link', { name: /Team & roles/ })).not.toBeInTheDocument());
    expect(screen.queryByRole('link', { name: /Legacy mapping/ })).not.toBeInTheDocument();
    expect(document.documentElement).toHaveClass('dark');
  });

  it('shows catalog rows, exports and restricts product creation by permission', async () => {
    authApi.me.mockResolvedValue({ ...owner, permissions: ['products.view'] });
    workspaceApi.get.mockResolvedValue(page([{ id: 1, name: 'Cotton Shirt', product_type: 'simple', track_inventory: true, active: true }]));
    open('/catalog');
    expect(await screen.findByText('Cotton Shirt')).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Add product' })).not.toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Export CSV' }));
    expect(workspaceApi.exportCatalog).toHaveBeenCalledOnce();
  });

  it('creates a simple product with a first variant and preserves price strings', async () => {
    workspaceApi.get.mockImplementation(async path => path === 'catalog/units' ? page([{ id: 4, name: 'Each' }]) : path.startsWith('products/') ? { data: { id: 7, name: 'New Shirt', product_type: 'simple', variants: [], images: [] } } : page());
    workspaceApi.save.mockResolvedValue({ data: { id: 7 } });
    open('/catalog/new'); const user = userEvent.setup();
    await user.type(await screen.findByLabelText('Product name'), 'New Shirt');
    await user.type(screen.getByLabelText('SKU'), 'SHIRT-NEW');
    await user.selectOptions(screen.getByLabelText('Unit of measure'), '4');
    await user.clear(screen.getByLabelText('Default price'));
    await user.type(screen.getByLabelText('Default price'), '12.3456');
    await user.click(screen.getByRole('button', { name: 'Create product' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('products', expect.objectContaining({ name: 'New Shirt', variant: expect.objectContaining({ sku: 'SHIRT-NEW', default_price: '12.3456', base_unit_id: '4' }) }), false));
  });

  it('shows server validation errors on catalog forms', async () => {
    workspaceApi.get.mockResolvedValue(page());
    workspaceApi.save.mockRejectedValue({ response: { status: 422, data: { message: 'Validation failed.', errors: { code: ['This unit code already exists.'] } } } });
    open('/setup/units'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Add units' }));
    await user.type(screen.getByLabelText('Name'), 'Each');
    await user.type(screen.getByLabelText('Code'), 'EA');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));
    expect(await screen.findByText('This unit code already exists.')).toBeInTheDocument();
    expect(screen.getByLabelText('Code')).toHaveAttribute('aria-invalid', 'true');
  });

  it('generates a root category slug and offers the saved root as a child category parent', async () => {
    const categories = [];
    workspaceApi.get.mockImplementation(async () => page([...categories]));
    workspaceApi.save.mockImplementation(async (path, data) => {
      const category = { ...data, id: categories.length + 1 };
      categories.push(category); return { data: category };
    });
    open('/setup/categories'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Add categories' }));
    await user.type(screen.getByLabelText('Name'), 'School Uniforms');
    expect(screen.getByLabelText('Category slug')).toHaveValue('school-uniforms');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('catalog/categories', { name: 'School Uniforms', slug: 'school-uniforms', parent_id: null, active: true }, false));
    await user.click(await screen.findByRole('button', { name: 'Add categories' }));
    await user.type(screen.getByLabelText('Name'), 'Boys Shirts');
    await user.selectOptions(screen.getByLabelText('Parent category'), '1');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('catalog/categories', { name: 'Boys Shirts', slug: 'boys-shirts', parent_id: '1', active: true }, false));
  });

  it('normalizes a custom category slug and keeps it when the name changes', async () => {
    workspaceApi.get.mockResolvedValue(page()); workspaceApi.save.mockResolvedValue({ data: { id: 1 } });
    open('/setup/categories'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Add categories' }));
    await user.type(screen.getByLabelText('Name'), 'School Uniforms');
    await user.clear(screen.getByLabelText('Category slug'));
    await user.type(screen.getByLabelText('Category slug'), 'Custom Code');
    await user.click(screen.getByLabelText('Name'));
    expect(screen.getByLabelText('Category slug')).toHaveValue('custom-code');
    await user.type(screen.getByLabelText('Name'), ' Updated');
    expect(screen.getByLabelText('Category slug')).toHaveValue('custom-code');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('catalog/categories', expect.objectContaining({ name: 'School Uniforms Updated', slug: 'custom-code' }), false));
  });

  it('preserves an existing category slug while editing its name', async () => {
    workspaceApi.get.mockResolvedValue(page([{ id: 1, name: 'Uniforms', slug: 'original-category-code', parent_id: null, active: true }]));
    workspaceApi.save.mockResolvedValue({ data: { id: 1 } });
    open('/setup/categories'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Edit' }));
    await user.clear(screen.getByLabelText('Name')); await user.type(screen.getByLabelText('Name'), 'Renamed Uniforms');
    expect(screen.getByLabelText('Category slug')).toHaveValue('original-category-code');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('catalog/categories/1', expect.objectContaining({ name: 'Renamed Uniforms', slug: 'original-category-code' }), true));
  });

  it('displays a repeated summary and field validation message only once', async () => {
    const message = 'A category with this slug already exists in this business. Choose a different slug.';
    workspaceApi.get.mockResolvedValue(page());
    workspaceApi.save.mockRejectedValue({ response: { status: 422, data: { message, errors: { slug: [message] } } } });
    open('/setup/categories'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Add categories' }));
    await user.type(screen.getByLabelText('Name'), 'School Uniforms');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));
    expect(await screen.findByText(message)).toBeInTheDocument();
    expect(screen.getByLabelText('Category slug')).toHaveAttribute('aria-invalid', 'true');
    expect(screen.getAllByText(message)).toHaveLength(1);
  });

  it('requires explicit owner confirmation before approving legacy SKU policy', async () => {
    workspaceApi.get.mockImplementation(async path => path === 'legacy/catalog' ? { data: { stocks: page(), mappings: page(), exceptions: page(), sku_policy: null, batch: null } } : page([{ id: 1, name: 'Main' }]));
    workspaceApi.save.mockResolvedValue({ data: {} });
    open('/legacy'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Approve distinct SKUs' }));
    await user.click(await screen.findByRole('checkbox', { name: /I approve a separate SKU/ }));
    await user.click(screen.getByRole('button', { name: 'Approve distinct SKUs' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('legacy/sku-policy', { policy: 'distinct_by_school', confirmed: true }));
  });

  it('offers registration and sends owner/business fields without a legacy role', async () => {
    authApi.me.mockRejectedValue({ response: { status: 401 } });
    authApi.register.mockResolvedValue(owner);
    authApi.tenants.mockResolvedValue({ data: [owner.active_tenant] });
    open('/register'); const user = userEvent.setup();
    await user.type(await screen.findByLabelText('Your name'), 'New Owner');
    await user.type(screen.getByLabelText('Email address'), 'new@example.test');
    await user.type(screen.getByLabelText('Password'), 'long-test-password');
    await user.type(screen.getByLabelText('Confirm password'), 'long-test-password');
    await user.type(screen.getByLabelText('Business name'), 'My Shop');
    await user.type(screen.getByLabelText('Business address name'), 'my-shop');
    await user.click(screen.getByRole('button', { name: 'Create account and business' }));
    await waitFor(() => expect(authApi.register).toHaveBeenCalledWith(expect.objectContaining({ business_name: 'My Shop', email: 'new@example.test', slug: 'my-shop' })));
    expect(authApi.register.mock.calls[0][0]).not.toHaveProperty('role_id');
  });
});
