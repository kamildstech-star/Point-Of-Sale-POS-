import { NavLink, useParams } from 'react-router-dom';
import { useAuth } from '../../auth/AuthProvider';
import OperationsReferencePage from './OperationsReferencePage';
import Icon from '../../components/Icon';

export function OperationsNav() {
  const { user } = useAuth();
  if (!user.active_tenant) return null;
  return <>{[
    ['sales.create', '/pos', 'Point of sale', 'pos'], ['sales.view', '/sales', 'Sales & receipts', 'sales'],
    ['inventory.view', '/inventory', 'Inventory', 'inventory'], ['purchases.view', '/purchasing', 'Purchasing', 'purchasing'],
    ['customers.view', '/operations/customers', 'Customers', 'customer'], ['cash.open', '/cash', 'Register shifts', 'cash'],
    ['settings.manage', '/pos-settings', 'POS activation', 'settings'], ['sales.return', '/returns', 'Returns & exchanges', 'returns'],
    ['expenses.view', '/expenses', 'Expenses', 'expense'], ['reports.view', '/reports', 'Reports', 'reports'],
    ['billing.manage', '/billing', 'Plans & billing', 'billing'], ['alerts.view', '/workspace-tools', 'Workspace tools', 'tools'],
  ].filter(([permission, path]) => path === '/workspace-tools' ? ['settings.manage', 'support.manage', 'alerts.view', 'imports.manage', 'exports.manage', 'audit.view', 'legacy.manage'].some(p => user.permissions.includes(p)) : user.permissions.includes(permission)).map(([, path, label, icon]) => <NavLink key={path} to={path}><Icon name={icon} />{label}</NavLink>)}</>;
}

export function OperationsReferenceAccess() {
  const { resource } = useParams(); const { user } = useAuth();
  const permission = { customers: 'customers.view', suppliers: 'purchases.view', registers: 'cash.open', 'payment-methods': 'payments.record' }[resource];
  if (!user.active_tenant || !permission || !user.permissions.includes(permission)) return <main className="content"><h1>Access restricted</h1></main>;
  return <OperationsReferencePage key={resource}/>;
}
