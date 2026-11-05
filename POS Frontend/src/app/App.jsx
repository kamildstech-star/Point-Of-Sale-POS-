import Icon from '../components/Icon';
import LoadingState from '../components/LoadingState';
import { Input, SaveForm } from "../components/WorkspaceForm";
import { lazy, Suspense, useEffect } from 'react';

import { Link, Navigate, Outlet, Route, Routes, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthProvider';
import { errorMessage } from '../api/client';
import BusinessPage from '../features/BusinessPage';
import RegistrationPage from '../features/RegistrationPage';
const TeamPage = lazy(() => import('../features/TeamPage'));
const CatalogPage = lazy(() => import('../features/CatalogPage'));
const ProductDetailsPage = lazy(() => import('../features/ProductPage'));
const ReferencePage = lazy(() => import('../features/ReferencePage'));
const LegacyPage = lazy(() => import('../features/LegacyPage'));
const InventoryPage = lazy(() => import('../features/operations/InventoryPage'));
const PurchasingPage = lazy(() => import('../features/operations/PurchasingPage'));
const SellingDesk = lazy(() => import('../features/operations/PosPage'));
const CashPage = lazy(() => import('../features/operations/CashPage'));
const SalesPage = lazy(() => import('../features/operations/SalesPage'));
const PosSettingsPage = lazy(() => import('../features/operations/PosSettingsPage'));
import { OperationsReferenceAccess } from '../features/operations/OperationsNav';
const ReturnsPage = lazy(() => import('../features/operations/ReturnsPage'));
const ReportsPage = lazy(() => import('../features/operations/ReportsPage'));
const ExpensesPage = lazy(() => import('../features/operations/ExpensesPage'));
const BillingPage = lazy(() => import('../features/operations/BillingPage'));
const WorkspaceToolsPage = lazy(() => import('../features/operations/WorkspaceToolsPage'));
import { ThemeProvider } from '../theme/ThemeProvider';
import TenantLayout from '../layouts/TenantLayout';
import WorkspaceOverview from '../features/dashboard/WorkspaceOverview';
function RoutePage({ label, children }) {
  return <Suspense fallback={<main className="content" aria-busy="true"><LoadingState label={`Loading ${label}…`} variant="form" /></main>}>{children}</Suspense>;
}
function ProductPage() {
  return <RoutePage label="product"><ProductDetailsPage /></RoutePage>;
}
function PosPage() {
  return <RoutePage label="selling desk"><SellingDesk /></RoutePage>;
}
function Notice({
  title,
  children
}) {
  return <main className="notice card"><div className="brand-mark">U</div><h1>{title}</h1>{children}</main>;
}
function Login() {
  const {
    user,
    login
  } = useAuth();
  const location = useLocation();
  if (user) return <Navigate to={location.state?.from || '/'} replace />;
  async function submit(form) {
    await login({ email: form.get('email'), password: form.get('password') });
  }
  return <div className="login-page"><section className="login-story"><p className="wordmark">UNIVERSAL <span>POS</span></p><div><p className="eyebrow">YOUR RETAIL WORKSPACE</p><h1>A clear start.<br />Room to grow.</h1><p>One workspace for your team and the next chapter of your business.</p></div><small>Best Uniform · Retail operations</small></section><main className="login-panel"><div className="login-form"><div className="brand-mark">U</div><h2>Welcome back</h2><p className="muted">Sign in with your existing staff account.</p><SaveForm onSave={submit} label="Sign in to workspace"><Input name="email" label="Email address" type="email" autoComplete="username" maxLength={255}/><Input name="password" label="Password" type="password" autoComplete="current-password" maxLength={1024}/></SaveForm><p className="footnote">New here? <Link to="/register">Create your business account</Link>.</p></div></main></div>;
}
function Protected() {
  const {
    user
  } = useAuth();
  const location = useLocation();
  return user ? <Outlet /> : <Navigate to="/login" state={{
    from: location.pathname
  }} replace />;
}
function Permission({
  permission,
  children
}) {
  const {
    user
  } = useAuth();
  if (!user.active_tenant) return <Navigate to="/workspace" replace />;
  return user.permissions.includes(permission) ? children : <Notice title="Access restricted"><p>Your role does not allow access to this page.</p><Link to="/workspace">Choose a business</Link></Notice>;
}
export default function App() {
  const { user } = useAuth();
  return <ThemeProvider userId={user?.id}><WorkspaceApplication /></ThemeProvider>;
}
function WorkspaceApplication() {
  const {
    loading,
    error,
    refresh,
    user
  } = useAuth();
  const location = useLocation();
  useEffect(() => {
    if (location.pathname === '/login') document.title = 'Sign in · Universal POS';
    else if (location.pathname === '/register') document.title = 'Create business account · Universal POS';
    else if (!user) document.title = 'Universal POS';
  }, [location.pathname, user?.id]);
  if (loading) return <Notice title="Opening your workspace"><LoadingState label="Checking your session…" variant="inline" /></Notice>;
  if (error) return <Notice title="Unable to open workspace"><p role="alert">{errorMessage(error)}</p><button onClick={refresh}><Icon name="refresh" />Try again</button></Notice>;
  return <Routes><Route path="/login" element={<Login />} /><Route path="/register" element={<RegistrationPage />} /><Route element={<Protected />}><Route element={<TenantLayout />}><Route index element={<WorkspaceOverview />} /><Route path="workspace" element={<BusinessPage />} /><Route path="select-business" element={<Navigate to="/workspace" replace />} /><Route path="catalog" element={<Permission permission="products.view"><RoutePage label="catalog"><CatalogPage /></RoutePage></Permission>} /><Route path="catalog/new" element={<Permission permission="products.manage"><ProductPage /></Permission>} /><Route path="catalog/:productId" element={<Permission permission="products.view"><ProductPage /></Permission>} /><Route path="setup/:resource" element={<Permission permission="products.view"><RoutePage label="catalog setup"><ReferencePage /></RoutePage></Permission>} /><Route path="team" element={<Permission permission="team.view"><RoutePage label="team"><TeamPage /></RoutePage></Permission>} /><Route path="legacy" element={<Permission permission="legacy.view"><RoutePage label="legacy records"><LegacyPage /></RoutePage></Permission>} /><Route path="inventory" element={<Permission permission="inventory.view"><RoutePage label="inventory"><InventoryPage /></RoutePage></Permission>} /><Route path="purchasing" element={<Permission permission="purchases.view"><RoutePage label="purchasing"><PurchasingPage /></RoutePage></Permission>} /><Route path="pos" element={<Permission permission="sales.create"><PosPage /></Permission>} /><Route path="cash" element={<Permission permission="cash.open"><RoutePage label="register shifts"><CashPage /></RoutePage></Permission>} /><Route path="sales" element={<Permission permission="sales.view"><RoutePage label="sales and receipts"><SalesPage /></RoutePage></Permission>} /><Route path="sales/:saleId" element={<Permission permission="sales.view"><RoutePage label="sales and receipts"><SalesPage /></RoutePage></Permission>} /><Route path="pos-settings" element={<Permission permission="settings.manage"><RoutePage label="POS settings"><PosSettingsPage /></RoutePage></Permission>} /><Route path="operations/:resource" element={<OperationsReferenceAccess />} /><Route path="returns" element={<Permission permission="sales.view"><RoutePage label="returns"><ReturnsPage /></RoutePage></Permission>} /><Route path="returns/new/:originalId" element={<Permission permission="sales.return"><RoutePage label="returns"><ReturnsPage /></RoutePage></Permission>} /><Route path="returns/:returnId" element={<Permission permission="sales.view"><RoutePage label="returns"><ReturnsPage /></RoutePage></Permission>} /><Route path="expenses" element={<Permission permission="expenses.view"><RoutePage label="expenses"><ExpensesPage /></RoutePage></Permission>} /><Route path="reports" element={<Permission permission="reports.view"><RoutePage label="reports"><ReportsPage /></RoutePage></Permission>} /><Route path="billing/*" element={<Permission permission="billing.manage"><RoutePage label="billing"><BillingPage /></RoutePage></Permission>} /><Route path="pricing" element={<Permission permission="billing.manage"><RoutePage label="billing"><BillingPage /></RoutePage></Permission>} /><Route path="workspace-tools" element={<RoutePage label="workspace tools"><WorkspaceToolsPage /></RoutePage>} /><Route path="forbidden" element={<Notice title="Access restricted"><Link to="/">Back to overview</Link></Notice>} /><Route path="*" element={<Notice title="Page not found"><p>This page is not available.</p><Link to="/">Back to overview</Link></Notice>} /></Route></Route></Routes>;
}
