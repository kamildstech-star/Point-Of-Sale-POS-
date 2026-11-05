import Icon from '../Icon';

// Display only context already resolved for this dashboard. No global branch state.
export default function DashboardContext({ scope, salesScope, currency, period }) {
  return <div className="dashboard-context" aria-label="Dashboard context">
    {scope && <span><Icon name="branch" size={14}/>{scope}</span>}
    {salesScope && <span><Icon name="team" size={14}/>{salesScope}</span>}
    {period?.from && period?.to && <span><Icon name="calendar" size={14}/><time dateTime={period.from}>{period.from}</time> – <time dateTime={period.to}>{period.to}</time></span>}
    {currency && <span className="dashboard-context__currency">{currency}</span>}
  </div>;
}
