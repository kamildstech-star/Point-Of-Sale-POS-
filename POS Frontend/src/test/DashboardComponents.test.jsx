import { fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import KpiCard from '../components/dashboard/KpiCard';
import ChartCard from '../components/dashboard/ChartCard';
import TrendChart from '../components/dashboard/TrendChart';
import DateRangeFilter from '../components/dashboard/DateRangeFilter';
import BranchSwitcher from '../components/dashboard/BranchSwitcher';
import QuickActionGrid from '../components/dashboard/QuickActionGrid';
import AlertList from '../components/dashboard/AlertList';

const open = children => render(<MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>{children}</MemoryRouter>);
describe('D1 dashboard foundations', () => {
  it('shows urgent real alerts first without mutating the supplied records', () => {
    const alerts = [{ id: 1, title: 'Information', severity: 'info' }, { id: 2, title: 'Low stock', severity: 'warning' }, { id: 3, title: 'Out of stock', severity: 'danger' }];
    open(<AlertList alerts={alerts}/>);
    expect(screen.getAllByRole('listitem').map(item => item.textContent)).toEqual(['Out of stock', 'Low stock', 'Information']);
    expect(alerts.map(item => item.id)).toEqual([1, 2, 3]);
  });
  it('distinguishes zero, unavailable and exact large decimal money without fake comparisons', () => {
    open(<><KpiCard title="Zero sales" value="0.00" currency="PKR" format="money" locale="en-US"/>
      <KpiCard title="Unverified profit" currency="PKR" format="money"/>
      <KpiCard title="Large total" value="9007199254740993.15" currency="USD" format="money" locale="en-US"/>
      <KpiCard title="Invalid currency" value="12" format="money"/></>);
    expect(screen.getByText(/PKR.*0\.00/)).toBeInTheDocument();
    expect(screen.getAllByText('Unavailable')).toHaveLength(2);
    expect(screen.getByText(/9,007,199,254,740,993\.15/)).toBeInTheDocument();
    expect(document.querySelector('.kpi-comparison')).not.toBeInTheDocument();
  });

  it('requires a supplied comparison basis and keeps attention semantics explicit', () => {
    open(<KpiCard title="Returns" value="3" trend={{ value: '12.5', comparisonLabel: 'More returns than prior period', tone: 'warning' }}/>);
    expect(screen.getByText(/12\.5%.*More returns/)).toHaveClass('kpi-comparison--warning');
  });

  it('shows missing chart values as unavailable and does not connect a line across a gap', async () => {
    open(<TrendChart title="Net sales excluding tax" unit="PKR" currency="PKR" locale="en-US" data={[
      { label: 'Nov 1', value: '12.25' }, { label: 'Nov 2', value: null }, { label: 'Nov 3', value: '0.00' },
    ]}/>);
    expect(screen.getByRole('img', { name: /Net sales excluding tax/ })).toBeInTheDocument();
    expect(document.querySelector('path').getAttribute('d').match(/M/g)).toHaveLength(2);
    expect(document.querySelectorAll('.trend-chart__point')).toHaveLength(2);
    await userEvent.click(screen.getByText('View chart data'));
    const table = screen.getByRole('table', { name: /Net sales excluding tax/ });
    expect(within(table).getByText('Unavailable')).toBeInTheDocument();
    expect(within(table).getByText(/PKR.*0\.00/)).toBeInTheDocument();
  });

  it('handles an empty series and a zero-only series without inventing data', () => {
    const view = open(<TrendChart title="Sales" data={[]}/>);
    expect(screen.getByText('No data for this period')).toBeInTheDocument();
    view.rerender(<MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><TrendChart title="Sales" data={[{ label: 'Today', value: '0' }]}/></MemoryRouter>);
    expect(screen.getByRole('img')).toBeInTheDocument();
    expect(document.querySelector('path').getAttribute('d')).not.toMatch(/NaN|Infinity/);
  });

  it('gives unavailable and retryable error states instead of a placeholder chart', async () => {
    const retry = vi.fn();
    const view = open(<ChartCard title="Gross profit" unavailable="Verified cost accounting is unavailable."/>);
    expect(screen.getByText('Verified cost accounting is unavailable.')).toBeInTheDocument();
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
    view.rerender(<MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><ChartCard title="Sales trend" error={new Error('Offline')} onRetry={retry}/></MemoryRouter>);
    await userEvent.click(screen.getByRole('button', { name: 'Try again' })); expect(retry).toHaveBeenCalledOnce();
  });

  it('validates date order before applying the real date strings', async () => {
    const apply = vi.fn(); open(<DateRangeFilter value={{ from: '2026-11-03', to: '2026-11-01' }} timezone="Asia/Karachi" onApply={apply}/>);
    await userEvent.click(screen.getByRole('button', { name: 'Apply period' }));
    expect(apply).not.toHaveBeenCalled(); expect(screen.getByLabelText('Through date')).toHaveAttribute('aria-invalid', 'true');
    fireEvent.change(screen.getByLabelText('Through date'), { target: { value: '2026-11-04' } });
    await userEvent.click(screen.getByRole('button', { name: 'Apply period' }));
    expect(apply).toHaveBeenCalledWith({ from: '2026-11-03', to: '2026-11-04' });
    expect(screen.getByText('Dates use Asia/Karachi.')).toBeInTheDocument();
  });

  it('does not render inert filters and accepts only a supplied branch option', async () => {
    const change = vi.fn();
    const view = open(<><DateRangeFilter/><BranchSwitcher/></>);
    expect(screen.queryByRole('combobox')).not.toBeInTheDocument(); expect(screen.queryByRole('button')).not.toBeInTheDocument();
    view.rerender(<MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><BranchSwitcher branches={[{ id: 2, name: 'Permitted branch' }]} onChange={change}/></MemoryRouter>);
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Branch' }), '2'); expect(change).toHaveBeenCalledWith('2');
    const select = screen.getByRole('combobox'); const injected = new Option('Other branch', '99'); select.add(injected);
    fireEvent.change(select, { target: { value: '99' } }); expect(change).toHaveBeenCalledTimes(1);
  });

  it('renders permitted real actions and drops unauthorized or inert ones', () => {
    open(<QuickActionGrid permissions={['sales.create']} actions={[
      { label: 'New sale', to: '/pos', permission: 'sales.create', icon: 'pos' },
      { label: 'Company profit', to: '/reports', permission: 'reports.view' }, { label: 'Coming soon' },
    ]}/>);
    expect(screen.getByRole('link', { name: 'New sale' })).toHaveAttribute('href', '/pos');
    expect(screen.queryByText('Company profit')).not.toBeInTheDocument(); expect(screen.queryByText('Coming soon')).not.toBeInTheDocument();
  });

  it('keeps alert context, non-color status cues and an actual destination', () => {
    open(<AlertList timezone="Asia/Karachi" alerts={[{ id: 1, title: 'Low stock', message: 'Review the available quantity.', severity: 'warning', to: '/inventory' }]}/>);
    expect(screen.getByText('Low stock')).toBeInTheDocument();
    expect(document.querySelector('[data-remix-icon="warning"]')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Review alert' })).toHaveAttribute('href', '/inventory');
  });
});
