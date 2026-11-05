import { act, cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import DensityProvider from '../components/ui/DensityProvider';
import Modal from '../components/Modal';
import TableViewport from '../components/ui/TableViewport';
import KpiCard from '../components/dashboard/KpiCard';

afterEach(() => { cleanup(); vi.unstubAllGlobals(); vi.restoreAllMocks(); });
describe('compact UI interaction boundaries', () => {
  it('preserves a portal form and focus when its inherited density changes', async () => {
    const close = vi.fn();
    const form = density => <DensityProvider value={density}><Modal title="Hold cart" onClose={close}><label>Cart label<input aria-label="Cart label" defaultValue="Fitting room"/></label></Modal></DensityProvider>;
    const view = render(form('comfortable'));
    const input = screen.getByRole('textbox');
    expect(screen.getByRole('dialog')).toHaveAttribute('data-density', 'comfortable');
    await userEvent.type(input, ' A'); view.rerender(form('compact'));
    expect(screen.getByRole('dialog')).toHaveAttribute('data-density', 'compact');
    expect(input).toHaveValue('Fitting room A'); expect(input).toHaveFocus();
    await userEvent.keyboard('{Escape}'); expect(close).toHaveBeenCalledOnce();
  });

  it('retains exact KPI values while disclosing their definition and reporting period', async () => {
    render(<MemoryRouter><KpiCard title="Net sales" value="9007199254740993.15" currency="USD" format="money" locale="en-US" definition="Posted invoices less return credits." period={{ from: '2026-11-01', to: '2026-11-04' }} timezone="Asia/Karachi"/></MemoryRouter>);
    expect(screen.getByText(/USD.*9,007,199,254,740,993.15/)).toBeVisible();
    expect(screen.getByText('2026-11-01 – 2026-11-04')).toBeVisible();
    expect(screen.getByText('Posted invoices less return credits.')).not.toBeVisible();
    await userEvent.click(screen.getByText('About this metric'));
    expect(screen.getByText('Posted invoices less return credits.')).toBeVisible();
    expect(screen.getByText('Reporting timezone: Asia/Karachi')).toBeVisible();
  });

  it('announces horizontal overflow and makes the region keyboard reachable only when needed', () => {
    let resize;
    vi.stubGlobal('ResizeObserver', class { constructor(callback) { resize = callback; } observe() {} disconnect() {} });
    vi.spyOn(HTMLElement.prototype, 'scrollWidth', 'get').mockReturnValue(600);
    const width = vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(300);
    render(<TableViewport label="Stock balances"><table><tbody><tr><td>99999999.1234</td></tr></tbody></table></TableViewport>);
    const region = screen.getByRole('region', { name: 'Stock balances' });
    expect(region).toHaveAttribute('tabindex', '0');
    expect(region).toHaveAccessibleDescription('Scroll horizontally to view all columns.');
    expect(screen.getByText('99999999.1234')).toBeInTheDocument();
    width.mockReturnValue(800); act(() => resize());
    expect(region).not.toHaveAttribute('tabindex');
    expect(screen.queryByText('Scroll horizontally to view all columns.')).not.toBeInTheDocument();
  });
});
