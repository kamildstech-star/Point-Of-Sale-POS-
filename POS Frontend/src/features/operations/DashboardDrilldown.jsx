import { AppliedFilters } from '../../components/ui/ListPatterns';

// URL parameters select a view. Server validation and scope remain authoritative.
export function drilldownQuery(search, keys) {
  const result = new URLSearchParams();
  keys.forEach(key => { const value = search.get(key); if (value) result.set(key, value); });
  return result.toString();
}

function personalClearTarget(query, clearTo) {
  if (new URLSearchParams(query).get('own') !== '1') return clearTo;
  const [pathname, search = ''] = clearTo.split('?');
  const params = new URLSearchParams(search); params.set('own', '1');
  return `${pathname}?${params}`;
}

export default function DashboardDrilldown({ query, clearTo }) {
  if (!query) return null;
  const labels = { branch_id: 'Branch', location_id: 'Location', variant_id: 'Variant', document_id: 'Document', stock_state: 'Stock state', status: 'Status', own: 'Personal scope', cashier_membership_id: 'Staff membership', cash_session_id: 'Shift', from: 'From', to: 'Through' };
  const entries = [...new URLSearchParams(query)];
  return <AppliedFilters filters={entries.map(([key, value]) => ({ label: labels[key] || key, value: key === 'own' && value === '1' ? 'Your records' : value }))} clearTo={entries.some(([key, value]) => key !== 'own' || value !== '1') ? personalClearTarget(query, clearTo) : undefined} />;
}
