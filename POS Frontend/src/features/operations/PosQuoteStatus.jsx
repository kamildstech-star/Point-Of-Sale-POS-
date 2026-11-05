import Icon from '../../components/Icon';

export default function PosQuoteStatus({ quote, expired, locked, timezone, onRefresh }) {
  const expiry = Date.parse(quote?.expires_at);
  return <div className={`pos-quote-status${expired ? ' is-expired' : ''}`} role="status">
    <div><strong><Icon name={expired ? 'time' : 'check'} />{expired ? 'Quote expired' : 'Ready for payment'}</strong>
      <p>{expired ? 'Request a fresh quote before completing this sale.' : Number.isFinite(expiry) ? `Valid until ${new Date(expiry).toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short', timeZone: timezone })} (${timezone}).` : 'Prices and tax were calculated by the server.'}</p></div>
    {expired && <button type="button" className="secondary" disabled={locked} onClick={onRefresh}><Icon name="refresh" />Refresh quote</button>}
  </div>;
}
