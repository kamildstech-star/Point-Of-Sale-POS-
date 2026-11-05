import { useId } from 'react';
import LoadingState from '../LoadingState';
import { ErrorNotice } from '../WorkspaceForm';
import EmptyState from './EmptyState';

export default function ChartCard({ title, description, period, timezone, unit, actions, loading = false, error, onRetry, unavailable, children }) {
  const id = useId();
  return <section className="card chart-card" aria-labelledby={id}>
    <header className="chart-card__header"><div><h2 id={id}>{title}</h2>{description && <p className="muted">{description}</p>}</div>{actions}</header>
    {(period || timezone || unit) && <p className="dashboard-metadata">{[period?.from && period?.to && `${period.from} – ${period.to}`, timezone, unit].filter(Boolean).join(' · ')}</p>}
    <LoadingState busy={loading} label={`Loading ${title.toLowerCase()}…`} variant="cards" rows={1}/>
    <ErrorNotice error={error} onRetry={onRetry}/>
    {!loading && !error && (unavailable ? <EmptyState title="Chart unavailable" description={unavailable}/> : children)}
  </section>;
}
