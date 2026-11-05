import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import App from '../app/App';
import { AuthProvider } from '../auth/AuthProvider';
import { authApi } from '../api/client';
import { workspaceApi } from '../api/workspace';
import { successNotification } from '../notifications/apiMessages';

vi.mock('../api/client', async original => ({ ...(await original()), authApi: { me: vi.fn(), tenants: vi.fn() } }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn(), remove: vi.fn(), exportCatalog: vi.fn(), image: id => `/images/${id}` } }));
const permissions = ['products.view', 'products.manage', 'settings.manage', 'customers.view', 'customers.manage', 'purchases.view', 'purchases.manage', 'cash.open', 'payments.record', 'team.view', 'team.manage'];
const owner = { id: 1, name: 'Owner', permissions, active_tenant: { id: 1, name: 'Test Shop', currency_code: 'PKR' } };
const page = data => ({ data, total: data.length, last_page: 1 });
let product;
function open(path) { return render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><AuthProvider><App /></AuthProvider></MemoryRouter>); }
beforeEach(() => {
  vi.resetAllMocks(); authApi.me.mockResolvedValue(owner);
  product = { id: 7, name: 'Cotton shirt', description: 'Original description', product_type: 'simple', track_inventory: true, active: true, category_id: null, brand_id: null, tax_class_id: null, variants: [{ id: 9, sku: 'SHIRT', base_unit_id: 4, default_cost: '1.2500', default_price: '12.3456', tracking_mode: 'none', active: true }] };
  workspaceApi.get.mockImplementation(async path => path === 'products/7' ? { data: { ...product } } : path.startsWith('products?') ? page([{ ...product }]) : path === 'catalog/units' ? page([{ id: 4, name: 'Each' }]) : page([]));
  workspaceApi.save.mockResolvedValue({ data: { id: 7 } });
  workspaceApi.remove.mockResolvedValue({});
});

describe('record action icons and shared editors', () => {
  it('opens the same product popup with current values and patches without creating a variant', async () => {
    open('/catalog'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Edit product' }, { timeout: 5000 }));
    const dialog = screen.getByRole('dialog', { name: 'Edit product' });
    const name = await within(dialog).findByLabelText('Product name');
    expect(name).toHaveValue('Cotton shirt'); expect(within(dialog).getByLabelText('Description')).toHaveValue('Original description');
    expect(within(dialog).queryByLabelText('SKU')).not.toBeInTheDocument();
    await user.clear(name); await user.type(name, 'Updated shirt');
    workspaceApi.save.mockImplementation(async (path, data) => { Object.assign(product, data); return { data: product }; });
    await user.click(within(dialog).getByRole('button', { name: 'Save product' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(await screen.findByText('Updated shirt')).toBeInTheDocument();
    expect(workspaceApi.save).toHaveBeenCalledWith('products/7', expect.objectContaining({ name: 'Updated shirt', description: 'Original description', active: true }), true);
    expect(workspaceApi.save.mock.calls[0][1]).not.toHaveProperty('variant');
  });

  it('requires confirmation, cancels without deleting and archives after confirmation', async () => {
    open('/catalog'); const user = userEvent.setup(); const trigger = await screen.findByRole('button', { name: 'Delete product' });
    expect(trigger.querySelector('svg')).toHaveAttribute('aria-hidden', 'true');
    await user.click(trigger); await user.click(screen.getByRole('button', { name: 'Cancel' }));
    expect(workspaceApi.remove).not.toHaveBeenCalled(); expect(trigger).toHaveFocus();
    workspaceApi.remove.mockImplementation(async () => { product.active = false; });
    await user.click(trigger); await user.click(screen.getByRole('button', { name: 'Archive product' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(workspaceApi.remove).toHaveBeenCalledExactlyOnceWith('products/7');
    expect(screen.getByText('Archived')).toBeInTheDocument(); expect(screen.getByRole('button', { name: 'Delete product' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Edit product' })).toBeEnabled();
  });

  it('retains the delete dialog and displays a server refusal at the top', async () => {
    workspaceApi.remove.mockRejectedValue({ response: { status: 409, data: { message: 'Close the open register shift first.' } } });
    workspaceApi.get.mockResolvedValue(page([{ id: 5, name: 'Counter', active: true }]));
    open('/operations/registers'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Delete' }));
    const dialog = screen.getByRole('dialog'); await user.click(within(dialog).getByRole('button', { name: 'Deactivate record' }));
    expect(await within(dialog).findByRole('alert')).toHaveTextContent('Close the open register shift first.');
    expect(dialog.querySelector('fieldset').firstElementChild).toHaveClass('validation-summary');
    expect(within(dialog).getByRole('button', { name: 'Deactivate record' })).toBeEnabled();
  });

  it.each([
    ['customers', { phone: '123', email: 'client@example.test', notes: 'Customer notes' }],
    ['suppliers', { phone: '123', email: 'supplier@example.test', payment_terms_days: 30, address: 'Address' }],
    ['payment-methods', { code: 'CARD', method_type: 'card' }],
    ['registers', { branch_id: 1, location_id: 1 }],
  ])('edits %s in the Add popup and deactivates the selected row', async (resource, fields) => {
    const row = { id: 5, name: 'Original record', active: true, ...fields };
    workspaceApi.get.mockImplementation(async path => path.startsWith('operations/') ? page([{ ...row }]) : page([{ id: 1, name: 'Main' }]));
    workspaceApi.save.mockImplementation(async (path, values) => { Object.assign(row, values); return { data: row }; });
    workspaceApi.remove.mockImplementation(async () => { row.active = false; });
    open(`/operations/${resource}`); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Edit' }));
    const dialog = screen.getByRole('dialog'); expect(within(dialog).getByLabelText('Name')).toHaveValue('Original record');
    fireEvent.change(within(dialog).getByLabelText('Name'), { target: { value: 'Updated record' } });
    await user.click(within(dialog).getByRole('button', { name: 'Save changes' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(workspaceApi.save).toHaveBeenCalledWith(`operations/${resource}/5`, expect.objectContaining({ name: 'Updated record', active: true }), true);
    expect(await screen.findByText('Updated record')).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'Delete' })); await user.click(screen.getByRole('button', { name: 'Deactivate record' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    await waitFor(() => expect(screen.getByRole('button', { name: 'Delete' })).toBeDisabled());
    expect(workspaceApi.remove).toHaveBeenCalledExactlyOnceWith(`operations/${resource}/5`);
  });

  it('edits and archives a variant while retaining its decimal prices', async () => {
    open('/catalog/7'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Edit variant' }));
    const dialog = screen.getByRole('dialog'); expect(within(dialog).getByLabelText('Default price')).toHaveValue('12.3456');
    await user.click(within(dialog).getByRole('button', { name: 'Save changes' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(workspaceApi.save).toHaveBeenCalledWith('products/7/variants/9', expect.objectContaining({ sku: 'SHIRT', default_price: '12.3456' }), true);
    workspaceApi.remove.mockImplementation(async () => { product.variants[0].active = false; });
    await user.click(screen.getByRole('button', { name: 'Delete variant' })); await user.click(screen.getByRole('button', { name: 'Archive variant' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(workspaceApi.remove).toHaveBeenCalledExactlyOnceWith('products/7/variants/9');
    expect(screen.getByRole('button', { name: 'Delete variant' })).toBeDisabled();
  });

  it('moves back to the last available page after deleting its final barcode', async () => {
    let removed = false;
    workspaceApi.get.mockImplementation(async path => path === 'variants' ? page([]) : path.includes('page=2') ? { data: removed ? [] : [{ id: 51, barcode: 'LAST' }], total: removed ? 50 : 51, last_page: removed ? 1 : 2 } : { data: [{ id: 1, barcode: 'FIRST' }], total: removed ? 50 : 51, last_page: removed ? 1 : 2 });
    workspaceApi.remove.mockImplementation(async () => { removed = true; });
    open('/setup/barcodes'); const user = userEvent.setup(); await user.click(await screen.findByRole('button', { name: 'Next' }));
    await screen.findByText('LAST'); await user.click(screen.getByRole('button', { name: 'Delete' }));
    await user.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete record' }));
    expect(await screen.findByText('Page 1 of 1')).toBeInTheDocument(); expect(await screen.findByText('FIRST')).toBeInTheDocument();
    expect(workspaceApi.remove).toHaveBeenCalledExactlyOnceWith('catalog/barcodes/51');
  });

  it('keeps retained tax rates editable and prevents their deletion', async () => {
    workspaceApi.get.mockResolvedValue(page([{ id: 1, jurisdiction_code: 'LOCAL', rate_percent: '5', valid_from: '2026-01-01' }]));
    open('/setup/tax-rates');
    expect(await screen.findByRole('button', { name: 'Delete' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Delete' })).toHaveAttribute('title', expect.stringContaining('retained for history'));
    expect(screen.getByRole('button', { name: 'Edit' })).toBeEnabled();
  });

  it('hides write actions from readers', async () => {
    authApi.me.mockResolvedValue({ ...owner, permissions: ['products.view'] }); open('/catalog');
    await screen.findByText('Cotton shirt'); expect(screen.queryByRole('button', { name: /Edit|Delete/ })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: /Open product/ })).toBeInTheDocument();
  });

  it('omits the catalog cost column for readers even if a stale response contains cost fields', async () => {
    authApi.me.mockResolvedValue({ ...owner, permissions: ['products.view'] }); open('/catalog/7');
    await screen.findByRole('heading', { name: 'Cotton shirt' });
    expect(screen.getByRole('columnheader', { name: 'Price' })).toBeInTheDocument();
    expect(screen.queryByRole('columnheader', { name: 'Cost' })).not.toBeInTheDocument();
    expect(screen.queryByText('1.2500')).not.toBeInTheDocument();
  });

  it('removes business access and reuses its access popup to restore a member', async () => {
    const member = { id: 3, name: 'Assistant', email: 'assistant@example.test', role_id: 2, role: 'Viewer', status: 'active' };
    workspaceApi.get.mockImplementation(async path => path === 'team' ? { data: [{ ...member }] } : { data: [{ id: 2, name: 'Viewer', is_system: true, permissions: ['products.view'] }], meta: { permission_codes: permissions } });
    workspaceApi.remove.mockImplementation(async () => { member.status = 'suspended'; });
    workspaceApi.save.mockImplementation(async (path, data) => { Object.assign(member, data); return { data: member }; });
    open('/team'); const user = userEvent.setup(); await user.click(await screen.findByRole('button', { name: 'Remove access' }));
    await user.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Remove access' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(workspaceApi.remove).toHaveBeenCalledExactlyOnceWith('team/3'); expect(screen.getByRole('button', { name: 'Remove access' })).toBeDisabled();
    await user.click(screen.getByRole('button', { name: 'Edit access' }));
    const dialog = screen.getByRole('dialog'); expect(within(dialog).getByLabelText('Status')).toHaveValue('suspended');
    await user.selectOptions(within(dialog).getByLabelText('Status'), 'active');
    await user.click(within(dialog).getByRole('button', { name: 'Save changes' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(workspaceApi.save).toHaveBeenCalledWith('team/3', { role_id: '2', status: 'active' }, true);
  });

  it('offers custom role editing and deletion while retaining system roles', async () => {
    let removed = false;
    workspaceApi.get.mockImplementation(async path => path === 'team' ? { data: [] } : { data: [{ id: 1, name: 'Owner', permissions, is_system: true }, ...removed ? [] : [{ id: 2, name: 'Assistant', code: 'custom-assistant', permissions: [], is_system: false }]], meta: { permission_codes: permissions } });
    workspaceApi.remove.mockImplementation(async () => { removed = true; });
    open('/team'); const user = userEvent.setup(); await user.click(await screen.findByRole('button', { name: 'Edit role' }));
    expect(screen.getByLabelText('Role code')).toHaveValue('custom-assistant'); await user.click(screen.getByRole('button', { name: 'Cancel' }));
    expect(screen.getAllByRole('button', { name: 'Delete role' })).toHaveLength(1);
    await user.click(screen.getByRole('button', { name: 'Delete role' })); await user.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete role' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(workspaceApi.remove).toHaveBeenCalledExactlyOnceWith('roles/2'); expect(screen.queryByRole('button', { name: 'Delete role' })).not.toBeInTheDocument();
  });

  it('uses accurate global success messages for archive, deactivation and access removal', () => {
    for (const [url, message] of [['catalog/categories/1', 'Category archived successfully.'], ['operations/customers/1', 'Customer deactivated successfully.'], ['products/1/variants/2', 'Product variant archived successfully.'], ['team/1', 'Team access removed for this business.'], ['catalog/barcodes/1', 'Barcode deleted successfully.']]) {
      expect(successNotification({ method: 'delete', url }, {})).toEqual({ type: 'success', message });
    }
  });
});
