import { Link } from 'react-router-dom';
import Icon from '../Icon';

// The owning page supplies the clear action so personal/branch scope stays explicit.
export function AppliedFilters({ filters, clearTo, onClear }) {
  if (!filters.length) return null;
  return <aside className="applied-filters" aria-label="Applied list filters">
    <div><strong>Filtered view</strong><ul>{filters.map(({ label, value }, index) => <li key={`${label}-${index}`} className="filter-chip"><span>{label}: {value}</span></li>)}</ul></div>
    {clearTo ? <Link to={clearTo}>Clear filters</Link> : onClear && <button type="button" className="secondary" onClick={onClear}>Clear filters</button>}
  </aside>;
}

// Counts and boundaries come from the server paginator; no local slicing or totals.
export function ListPagination({ data, busy = false, failed = false, onPageChange, label = 'Records' }) {
  if (!data) return null;
  const page = data.current_page || 1, last = Math.max(1, data.last_page || 1);
  const count = data.data?.length || 0;
  const from = data.from ?? (data.per_page && count ? (page - 1) * data.per_page + 1 : null);
  const through = data.to ?? (from !== null ? from + count - 1 : null);
  const total = data.total;
  const range = !count ? '0 records on this page' : from !== null ? `Showing ${from}–${through}` : `${count} records on this page`;
  return <nav className="list-pagination" aria-label={`${label} pagination`}>
    <span className="list-record-count" role="status" aria-label={`${label} results`} aria-live="polite" aria-atomic="true" aria-busy={busy}>{busy ? 'Updating records…' : `${range}${total !== undefined ? ` · ${total} total` : ''}`}</span>
    <div className="list-page-controls"><button type="button" className="secondary" disabled={busy || failed || page <= 1} onClick={() => onPageChange(page - 1)}><Icon name="previous" />Previous</button>
      <span>Page {page} of {last}</span>
      <button type="button" className="secondary" disabled={busy || failed || page >= last} onClick={() => onPageChange(page + 1)}><Icon name="next" />Next</button></div>
  </nav>;
}

export function ListEmptyState({ filtered = false, title, description, children }) {
  return <div className="empty-state list-empty-state">
    <Icon name={filtered ? 'search' : 'list'} />
    <h2>{title || (filtered ? 'No matching records' : 'No records yet')}</h2>
    <p>{description || (filtered ? 'Try changing or clearing the filters for this view.' : 'Records will appear here when they are created.')}</p>
    {children && <div className="empty-state-actions">{children}</div>}
  </div>;
}
