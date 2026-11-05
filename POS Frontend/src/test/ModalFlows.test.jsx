import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { AuthProvider } from '../auth/AuthProvider';
import App from '../app/App';
import { authApi } from '../api/client';
import { workspaceApi } from '../api/workspace';

vi.mock('../api/client', async original => ({ ...(await original()), authApi: { me: vi.fn(), login: vi.fn(), logout: vi.fn(), tenants: vi.fn(), activate: vi.fn(), createBusiness: vi.fn(), register: vi.fn() } }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn(), remove: vi.fn(), exportCatalog: vi.fn() } }));
const permissions = ['dashboard.view', 'products.view', 'products.manage', 'settings.manage', 'team.view', 'team.manage', 'cash.open', 'customers.view', 'customers.manage', 'purchases.view', 'purchases.manage', 'inventory.view', 'inventory.adjust', 'expenses.view', 'expenses.manage', 'support.manage', 'alerts.view'];
const owner = { id: 1, name: 'Owner', role: { name: 'Owner' }, permissions, active_tenant: { id: 1, name: 'Test Shop', currency_code: 'PKR' } };
const page = data => ({ data, total: data.length, last_page: 1 });
let products;
function open(path) { return render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><AuthProvider><App/></AuthProvider></MemoryRouter>); }
beforeEach(() => {
  vi.resetAllMocks(); products = [{ id: 1, name: 'Existing shirt', product_type: 'simple', active: true }];
  authApi.me.mockResolvedValue(owner); authApi.tenants.mockResolvedValue({ data: [owner.active_tenant] });
  workspaceApi.get.mockImplementation(async path => {
    if (path.startsWith('products?')) return page([...products]);
    if (path === 'catalog/units') return page([{ id: 4, name: 'Each' }]);
    if (path === 'roles') return { data: [{ id: 1, name: 'Owner', permissions, is_system: true }], meta: { permission_codes: permissions } };
    if (path === 'workspace/settings') return { data: { receipt_name: 'Test Shop', receipt_footer: 'Thank you' } };
    return page([]);
  });
  workspaceApi.save.mockResolvedValue({ data: { id: 2 } });
});

describe('list forms open in popup dialogs', () => {
  it('keeps invalid product prices in the popup and sends no request', async () => {
    open('/catalog'); const user = userEvent.setup(); await screen.findByText('Existing shirt');
    await user.click(screen.getByRole('button', { name: 'Add product' }));
    fireEvent.change(await screen.findByLabelText('Product name'), { target: { value: 'New shirt' } });
    fireEvent.change(screen.getByLabelText('SKU'), { target: { value: 'NEW-SHIRT' } });
    await user.selectOptions(screen.getByLabelText('Unit of measure'), '4');
    fireEvent.change(screen.getByLabelText('Default price'), { target: { value: '1e3' } });
    fireEvent.submit(screen.getByLabelText('Product name').closest('form'));
    expect(workspaceApi.save).not.toHaveBeenCalled(); expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByLabelText('Default price')).toHaveAttribute('aria-invalid', 'true');
    expect(screen.getByLabelText('Product name')).toHaveValue('New shirt');
  });
  it('rejects an empty purchase order before the API', async () => {
    workspaceApi.get.mockImplementation(async path => page(path === 'operations/suppliers' ? [{id:1,name:'Supplier'}] : path === 'catalog/locations' ? [{id:1,name:'Store'}] : []));
    open('/purchasing'); const user=userEvent.setup(); await user.click(await screen.findByRole('button',{name:'New purchase order'}));
    await user.selectOptions(screen.getByLabelText('Supplier'),'1'); await user.selectOptions(screen.getByLabelText('Destination location'),'1');
    await user.click(screen.getByRole('button',{name:'Save draft order'})); expect(workspaceApi.save).not.toHaveBeenCalled(); expect(screen.getByRole('alert')).toHaveTextContent('Choose between 1 and 100 product variants.');
  });
  it('creates a product from the list in a popup and refreshes the list after save', async () => {
    workspaceApi.save.mockImplementation(async (path, body) => { const product = { id: 2, ...body }; products.push(product); return { data: product }; });
    open('/catalog'); const user = userEvent.setup(); await screen.findByText('Existing shirt');
    expect(screen.queryByLabelText('Product name')).not.toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'Add product' }));
    const dialog = screen.getByRole('dialog', { name: 'Add product' });
    await user.type(await within(dialog).findByLabelText('Product name'), 'New shirt');
    await user.type(within(dialog).getByLabelText('SKU'), 'NEW-SHIRT');
    await user.selectOptions(within(dialog).getByLabelText('Unit of measure'), '4');
    await user.clear(within(dialog).getByLabelText('Default price')); await user.type(within(dialog).getByLabelText('Default price'), '12.3456');
    await user.click(within(dialog).getByRole('button', { name: 'Create product' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(await screen.findByText('New shirt')).toBeInTheDocument();
    expect(workspaceApi.save).toHaveBeenCalledWith('products', expect.objectContaining({ variant: expect.objectContaining({ default_price: '12.3456' }) }), false);
  });

  it.each([
    ['/setup/units', 'Add units', 'Name'],
    ['/setup/categories', 'Add categories', 'Category slug'],
    ['/operations/customers', 'Add customer', 'Name'],
    ['/operations/suppliers', 'Add supplier', 'Name'],
    ['/operations/registers', 'Add cash register', 'Store location'],
    ['/team', 'Add team member', 'Email address'],
    ['/inventory', 'New adjustment', 'Reason'],
    ['/purchasing', 'New purchase order', 'Supplier'],
    ['/expenses', 'Record expense', 'Amount'],
    ['/workspace', 'Create business', 'Business name'],
    ['/workspace-tools', 'Edit receipt and returns settings', 'Receipt footer'],
  ])('%s opens its form in a dialog and cancels without posting', async (path, button, field) => {
    open(path); const user = userEvent.setup();
    const trigger = await screen.findByRole('button', { name: button }); await user.click(trigger);
    const dialog = await screen.findByRole('dialog');
    expect(within(dialog).getByLabelText(field)).toBeInTheDocument();
    await user.click(within(dialog).getByRole('button', { name: 'Cancel' }));
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument(); expect(workspaceApi.save).not.toHaveBeenCalled();
    expect(document.body.style.overflow).toBe('');
  });

  it('keeps a failed support request open with its fields and server error', async () => {
    workspaceApi.save.mockRejectedValue({ response: { data: { message: 'Please provide more detail.' } } });
    open('/workspace-tools'); const user = userEvent.setup(); await user.click(await screen.findByRole('button', { name: 'Support' }));
    await user.click(screen.getByRole('button', { name: 'Open support case' }));
    const dialog = screen.getByRole('dialog', { name: 'Open support case' });
    await user.type(within(dialog).getByLabelText('Subject'), 'Receipt issue');
    await user.type(within(dialog).getByLabelText('Description'), 'Details here');
    await user.click(within(dialog).getByRole('button', { name: 'Open support case' }));
    expect(await within(dialog).findByRole('alert')).toHaveTextContent('Please provide more detail.');
    expect(within(dialog).getByLabelText('Subject')).toHaveValue('Receipt issue');
    expect(screen.getByRole('dialog')).toBeInTheDocument();
  });
});
