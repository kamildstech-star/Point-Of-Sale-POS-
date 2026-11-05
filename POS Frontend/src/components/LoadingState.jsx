import Icon from './Icon';
import { useDelayedBusy } from '../loading/useDelayedBusy';

const variants = new Set(['table', 'form', 'cards', 'inline']);

/** Shared, non-blocking feedback for an individual data region. */
export default function LoadingState({
  busy = true,
  label = 'Loading data…',
  variant = 'table',
  rows = 4,
  columns = 4,
}) {
  const visible = useDelayedBusy(Boolean(busy));
  if (!visible) return null;

  const layout = variants.has(variant) ? variant : 'table';
  const rowCount = Math.min(12, Math.max(1, Number(rows) || 4));
  const columnCount = Math.min(8, Math.max(1, Number(columns) || 4));

  return <div className={`loading-state loading-state--${layout}`} role="status" aria-label={label} aria-live="polite" aria-atomic="true">
    <div className="loading-state__label"><span className="loading-state__spinner" aria-hidden="true"><Icon name="loader" spin /></span><span>{label}</span></div>
    {layout !== 'inline' && <div className="loading-state__skeletons" aria-hidden="true" style={{ '--loading-columns': columnCount }}>
      {Array.from({ length: rowCount }, (_, row) => layout === 'table'
        ? <div className="loading-state__row" key={row}>{Array.from({ length: columnCount }, (_, column) => <span className="loading-skeleton" key={column} />)}</div>
        : layout === 'form'
          ? <div className="loading-state__field" key={row}><span className="loading-skeleton loading-skeleton--short" /><span className="loading-skeleton loading-skeleton--control" /></div>
          : <div className="loading-state__card" key={row}><span className="loading-skeleton loading-skeleton--short" /><span className="loading-skeleton" /><span className="loading-skeleton" /></div>)}
    </div>}
  </div>;
}
