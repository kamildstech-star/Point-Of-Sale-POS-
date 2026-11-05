import { Link } from 'react-router-dom';
import Icon from '../Icon';
import LoadingState from '../LoadingState';
import { ErrorNotice } from '../WorkspaceForm';
import { formatMoney, formatNumber } from './format';

export default function KpiCard({ title, value, currency, format = 'number', icon = 'overview', definition, period, timezone,
  trend, to, loading = false, error, onRetry, unavailable = 'Unavailable', locale, scope }) {
  const formatted = format === 'money' ? formatMoney(value, currency, locale) : formatNumber(value, locale);
  const comparison = trend?.comparisonLabel && formatNumber(trend.value, locale);
  return <article className="card kpi-card" data-state={loading ? 'loading' : error ? 'error' : formatted === null ? 'unavailable' : 'ready'}>
    <div className="kpi-card__heading"><h3>{title}</h3><span className="tile-icon"><Icon name={icon}/></span></div>
    <LoadingState busy={loading} label={`Loading ${title.toLowerCase()}…`} variant="inline"/>
    <ErrorNotice error={error} onRetry={onRetry}/>
    {!loading && !error && <>
      <p className="kpi-card__value money-text">{formatted ?? unavailable}</p>
      {(scope || period?.from && period?.to) && <p className="kpi-card__context">{scope && <span>{scope}</span>}{period?.from && period?.to && <span>{period.from} – {period.to}</span>}</p>}
      {comparison && <p className={`kpi-comparison kpi-comparison--${['success', 'warning', 'danger'].includes(trend.tone) ? trend.tone : 'neutral'}`}>
        <Icon name="info" size={14}/>{comparison}% · {trend.comparisonLabel}
      </p>}
      {(definition || period?.from && period?.to) && <details className="kpi-card__details">
        <summary>About this metric<span className="sr-only">: {title}</span></summary>
        {definition && <p className="kpi-card__definition">{definition}</p>}
        {period?.from && period?.to && timezone && <p className="dashboard-metadata">Reporting timezone: {timezone}</p>}
      </details>}
      {to && <Link className="kpi-card__link" to={to}>View {title.toLowerCase()}<Icon name="forward" size={16}/></Link>}
    </>}
  </article>;
}
