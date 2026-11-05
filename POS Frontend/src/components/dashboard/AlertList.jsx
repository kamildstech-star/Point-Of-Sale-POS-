import { Link } from 'react-router-dom';
import Icon from '../Icon';
import LoadingState from '../LoadingState';
import { ErrorNotice } from '../WorkspaceForm';
import { formatUpdatedAt } from './format';

export default function AlertList({ title = 'Operational alerts', alerts, timezone, loading = false, error, onRetry }) {
  const priority = { danger: 0, warning: 1, info: 2, success: 3 };
  const ordered = Array.isArray(alerts) ? [...alerts].sort((a, b) => (priority[a.severity] ?? 2) - (priority[b.severity] ?? 2)) : alerts;
  return <section className="card dashboard-alerts"><h2>{title}</h2>
    <LoadingState busy={loading} label="Loading alerts…" variant="inline"/><ErrorNotice error={error} onRetry={onRetry}/>
    {!loading && !error && (!Array.isArray(ordered) ? <p className="muted">Alerts are unavailable.</p> : !ordered.length ? <p className="muted">No alerts for this scope.</p> : <ul>{ordered.map(alert => {
      const severity = ['danger', 'warning', 'success'].includes(alert.severity) ? alert.severity : 'info';
      const updated = formatUpdatedAt(alert.updated_at, timezone);
      return <li key={alert.id} className={`dashboard-alert dashboard-alert--${severity}`}>
        <Icon name={severity === 'danger' ? 'error' : severity} size={20}/><div><strong>{alert.title}</strong><p>{alert.message}</p>
          {updated && <small>{updated} · {timezone}</small>}{alert.to && <Link to={alert.to}>Review alert<Icon name="forward" size={14}/></Link>}
        </div>
      </li>;
    })}</ul>)}
  </section>;
}
