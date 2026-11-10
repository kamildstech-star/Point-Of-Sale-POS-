import { Link } from 'react-router-dom';
import { useAuth } from '../../auth/AuthProvider';
import Icon from '../../components/Icon';
import PageHeader from '../../components/dashboard/PageHeader';
import QuickActionGrid from '../../components/dashboard/QuickActionGrid';
import { lazy, Suspense, useState } from 'react';
import LoadingState from '../../components/LoadingState';
import { ErrorNotice } from '../../components/WorkspaceForm';
import useBusinessDashboard from './useBusinessDashboard';
import { sameDashboardFilters } from './dashboardData';
const BusinessDashboard = lazy(() => import('./BusinessDashboard'));
const StaffDashboard = lazy(() => import('./StaffDashboard'));
const CapabilityDashboard = lazy(() => import('./CapabilityDashboard'));
const SalesAssociateDashboard = lazy(() => import('./SalesAssociateDashboard'));
const firstPages = { branches: 1, receipts: 1, staff: 1, units: 1, orders: 1, suppliers: 1, exceptions: 1 };

export const welcomeActions = [
  { label: 'New sale', to: '/pos', permission: 'sales.create', icon: 'pos', description: 'Open the selling desk' },
  { label: 'Add product', to: '/catalog/new', permission: 'products.manage', icon: 'product', description: 'Create a catalog item' },
  { label: 'Inventory', to: '/inventory', permission: 'inventory.view', icon: 'inventory', description: 'Review stock and movements' },
  { label: 'Purchasing', to: '/purchasing', permission: 'purchases.view', icon: 'purchasing', description: 'Orders and receiving' },
  { label: 'Customers', to: '/operations/customers', permission: 'customers.view', icon: 'customer', description: 'Find a customer' },
  { label: 'Reports', to: '/reports', permission: 'reports.view', icon: 'reports', description: 'Review financial reports' },
];

export default function WorkspaceOverview() {
  const { user } = useAuth();
  if (!user.permissions.includes('dashboard.view')) return <main className="notice card"><div className="brand-mark">U</div><h1>Access restricted</h1><p>Your account does not have permission to view the overview.</p><Link to="/workspace">View business context</Link></main>;
  if (!user.active_tenant || import.meta.env.VITE_OWNER_ADMIN_DASHBOARD_ENABLED === 'false') return <WelcomeOverview user={user}/>;
  return <DashboardEntry key={`${user.active_tenant.id}:${JSON.stringify(user.permissions)}`} user={user}/>;
}

function DashboardEntry({ user }) {
  const [filters, setFilters] = useState({});
  const [revision, setRevision] = useState(0);
  const [pages, setPages] = useState(firstPages);
  function apply(next) { setFilters(next); setPages(firstPages); }
  function onPage(kind, page) { setPages(previous => ({ ...previous, [kind]: page })); }
  return <DashboardRequest key={`${JSON.stringify(filters)}:${revision}:${JSON.stringify(pages)}`} user={user} filters={filters} revision={revision} pages={pages}
    onFilters={apply} onRefresh={() => setRevision(value => value + 1)} onPage={onPage}/>;
}

function DashboardRequest({ user, filters, revision, pages, onFilters, onRefresh, onPage }) {
  const state = useBusinessDashboard(user.active_tenant.id, filters, revision, pages);
  if (state.loading) return <main className="content" aria-busy="true"><PageHeader eyebrow="OVERVIEW" title="Business dashboard" description="Loading your permitted business view."/><LoadingState busy label="Loading business dashboard…" variant="cards" rows={4}/></main>;
  if (state.error) return <main className="content"><PageHeader title="Business dashboard"/><ErrorNotice error={state.error} onRetry={onRefresh}/><Link to="/workspace">Review business access</Link></main>;
  if (state.legacy) return <WelcomeOverview user={{ ...user, permissions: state.tenant.permissions }}/>;
  const apply = next => { if (!sameDashboardFilters(state.filters, next)) onFilters(next); };
  return <Suspense fallback={<main className="content" aria-busy="true"><LoadingState busy label="Opening dashboard…" variant="cards" rows={4}/></main>}>{['manager', 'cashier'].includes(state.tenant.dashboard.profile)
    ? <StaffDashboard state={state} onFilters={apply} onRefresh={onRefresh} onPage={onPage}/>
    : ['owner', 'admin'].includes(state.plan.kind)
      ? <BusinessDashboard state={state} onFilters={apply} onRefresh={onRefresh} onBranchPage={page => onPage('branches', page)}/>
      : state.plan.kind === 'associate'
        ? <SalesAssociateDashboard state={state} onFilters={apply} onRefresh={onRefresh} onPage={onPage}/>
        : <CapabilityDashboard state={state} onFilters={apply} onRefresh={onRefresh} onPage={onPage}/>}</Suspense>;
}

export function WelcomeOverview({ user }) {
  return <main className="content">
    <PageHeader eyebrow="OVERVIEW" title={`Welcome, ${user.name.split(' ')[0]}.`} description="Your retail workspace, in one place."/>
    <section className="welcome-banner"><div><span className="pill">RETAIL WORKSPACE</span>
      <h2>A connected workspace,<br/>built around your business.</h2>
      <p>Choose a business, review your catalog and open the tools available to your role.</p>
      <Link className="button-link" to="/workspace">View business context<Icon name="forward"/></Link>
    </div><div className="abstract-grid" aria-hidden="true"><i/><i/><i/><i/></div></section>
    <div className="cards">
      <article className="card"><span className="tile-icon"><Icon name="overview"/></span><h3>Staff access</h3><p>Signed in as {user.role?.name || 'Staff'}.</p><span className="tag">Connected</span></article>
      <article className="card"><span className="tile-icon"><Icon name="businesses"/></span><h3>Business workspace</h3><p>Manage your business memberships, branches and stock locations.</p><span className="tag neutral">Available now</span></article>
      <article className="card"><span className="tile-icon"><Icon name="catalog"/></span><h3>Retail operations</h3><p>Use inventory, purchasing, checkout, returns and reports according to your access.</p><span className="tag neutral">Available now</span></article>
    </div>
    {user.active_tenant && <QuickActionGrid actions={welcomeActions} permissions={user.permissions}/>}
  </main>;
}
