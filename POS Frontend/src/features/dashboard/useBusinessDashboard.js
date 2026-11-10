import { useEffect, useState } from 'react';
import { workspaceApi } from '../../api/workspace';
import { dashboardError, defaultPeriod, destination, verifySnapshot } from './dashboardData';
import { dashboardPlan } from './dashboardRegistry';

export default function useBusinessDashboard(tenantId, filters, revision, pages) {
  const [state, setState] = useState({ loading: true });
  const signature = JSON.stringify(filters);
  const pageSignature = JSON.stringify(pages);
  useEffect(() => {
    const controller = new AbortController();
    const config = { signal: controller.signal, notifications: false };
    const active = () => !controller.signal.aborted;
    // Clear the previous scope before requesting the next one, including errors.
    setState({ loading: true });
    async function load() {
      const { data } = await workspaceApi.get('me/context', config);
      if (!active()) return;
      const tenant = data?.active_tenant;
      if (!tenant || String(tenant.id) !== String(tenantId)) throw dashboardError('Select your business again to refresh dashboard access.');
      if (!tenant.permissions.includes('dashboard.view')) throw dashboardError('Dashboard access is no longer available for this account.');
      if (!tenant.dashboard || !Array.isArray(tenant.dashboard.allowed_widgets)) return { loading: false, legacy: true, tenant };
      const plan = dashboardPlan(tenant);
      if (['manager', 'cashier'].includes(tenant.dashboard.profile) && import.meta.env.VITE_STAFF_DASHBOARD_ENABLED === 'false') return { loading: false, legacy: true, tenant };
      const selection = { ...defaultPeriod(tenant.timezone), ...filters };
      if (tenant.dashboard.profile !== 'cashier') delete selection.cash_session_id;
      if (tenant.dashboard.profile === 'cashier') selection.from = selection.to;
      if (selection.branch_id && !tenant.allowed_branches.some(branch => String(branch.id) === String(selection.branch_id))) selection.branch_id = '';
      if (tenant.dashboard.profile === 'manager' && !selection.branch_id) selection.branch_id = String(tenant.allowed_branches[0]?.id || '');
      const resetPagination = Boolean(filters.branch_id && String(selection.branch_id || '') !== String(filters.branch_id));
      if (resetPagination) delete selection.cash_session_id;
      if (tenant.dashboard.profile === 'manager' && !selection.branch_id) return { loading: false, tenant, plan, filters: selection, noBranch: true };
      const paths = plan.resources;
      // Retry once if audited writes occurred between widgets. Never display a
      // chart from one data revision beside totals from another.
      for (let attempt = 0; attempt < 2; attempt++) {
        const outcomes = await Promise.allSettled(paths.map(resource => workspaceApi.get(destination(`dashboard/${resource}`, {
          ...selection, compare: resource === 'summary' ? 1 : undefined,
          per_page: 5, limit: 5, page: resetPagination ? 1 : resource === 'branch-performance' ? pages.branches : resource === 'staff-activity' ? pages.staff : resource === 'inventory-trend' ? pages.units : resource === 'purchase-orders' ? pages.orders : resource === 'supplier-overview' ? pages.suppliers : resource === 'accounting-exceptions' ? pages.exceptions : ['recent-sales', 'cashier-summary', 'personal-sales'].includes(resource) ? pages.receipts : 1,
        }), config)));
        if (!active()) return;
        const responses = {}, errors = {};
        outcomes.forEach((outcome, index) => {
          if (outcome.status === 'fulfilled') { verifySnapshot(outcome.value, tenant, selection, ['cashier-summary', 'personal-sales'].includes(paths[index]) ? 'own' : tenant.dashboard.sales_scope); responses[paths[index]] = outcome.value; }
          else {
            if ([401, 403, 419].includes(outcome.reason?.response?.status) || index === 0) throw outcome.reason;
            errors[paths[index]] = outcome.reason;
          }
        });
        const revisions = new Set(Object.values(responses).map(response => response.meta.data_revision));
        if (revisions.size > 1) {
          if (!attempt) continue;
          throw dashboardError('Business activity changed while loading the dashboard. Please refresh to load a consistent view.');
        }
        const timestamps = Object.values(responses).map(response => response.meta.generated_at).sort();
        return { loading: false, tenant, plan, filters: selection, responses, errors, updatedAt: timestamps[0] };
      }
    }
    load().then(result => { if (active() && result) setState(result); }).catch(error => { if (active()) setState({ loading: false, error }); });
    return () => controller.abort();
  }, [tenantId, signature, revision, pageSignature]);
  // Effects run after render; keying the controller component handles scope
  // changes synchronously, so no old figures flash under a new filter heading.
  return state;
}
