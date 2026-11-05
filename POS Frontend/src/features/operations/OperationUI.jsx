import { columnClass } from '../../components/ui/tableColumns';
import TableViewport from '../../components/ui/TableViewport';
import { ListEmptyState, ListPagination } from '../../components/ui/ListPatterns';
import Icon from '../../components/Icon';
import LoadingState from '../../components/LoadingState';
import { useEffect, useRef, useState } from 'react';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, ValidatedControl } from '../../components/WorkspaceForm';
export const requestKey = () => crypto.randomUUID();
export function useResources(paths, revision = 0) {
  const [state, setState] = useState({
    loading: true,
    data: {}
  });
  const signature = paths.join('|');
  useEffect(() => {
    let alive = true;
    setState(old => ({
      ...old,
      loading: true,
      error: null
    }));
    Promise.all(paths.map(async path => [path, await workspaceApi.get(path)])).then(entries => {
      if (alive) setState({
        loading: false,
        data: Object.fromEntries(entries)
      });
    }).catch(error => {
      if (alive) setState(old => ({
        ...old,
        loading: false,
        error
      }));
    });
    return () => {
      alive = false;
    };
  }, [signature, revision]);
  return state;
}
export function RemoteTable(props) {
  // A changed filter/resource starts at page one and cannot show another scope's rows.
  return <RemoteRecords key={props.path} {...props} />;
}
function RemoteRecords({
  path,
  columns,
  onSelect,
  renderActions,
  revision = 0,
  label = 'Records',
  filtered
}) {
  const [page, setPage] = useState(1);
  const [retry, setRetry] = useState(0);
  const result = useResources([`${path}${path.includes('?') ? '&' : '?'}page=${page}`], `${revision}:${retry}`);
  const data = Object.values(result.data)[0];
  const hasFilters = filtered ?? [...new URLSearchParams(path.split('?')[1])].some(([, value]) => value && value !== '0');
  useEffect(() => {
    if (!result.loading && data?.last_page && page > data.last_page) setPage(data.last_page);
  }, [result.loading, data?.last_page, page]);
  return <div className="remote-table">
    <ErrorNotice error={result.error} onRetry={() => setRetry(value => value + 1)} />
    <LoadingState busy={Boolean(result.loading)} label="Loading records…" variant={data ? 'inline' : 'table'} columns={columns.length + (onSelect || renderActions ? 1 : 0)} />
    <TableViewport label={label} className="table-scroll" stickyHeader aria-busy={Boolean(result.loading)} inert={result.loading || result.error ? '' : undefined}>
      <table><thead><tr>{columns.map(([key, title]) => <th key={key} scope="col" className={columnClass(key)}>{title}</th>)}{(onSelect || renderActions) && <th className="table-actions" scope="col">Actions</th>}</tr></thead>
        <tbody>{data?.data?.map(row => <tr key={row.id}>{columns.map(([key]) => <td key={key} className={columnClass(key)}>{String(row[key] ?? '—')}</td>)}{(onSelect || renderActions) && <td className="table-actions">{renderActions ? renderActions(row) : <button type="button" className="secondary" onClick={() => onSelect(row)}><Icon name="view" />Open</button>}</td>}</tr>)}</tbody>
      </table>
    </TableViewport>
    {!result.loading && !result.error && data?.data?.length === 0 && <ListEmptyState filtered={hasFilters} />}
    <ListPagination label={label} data={data} busy={Boolean(result.loading)} failed={Boolean(result.error)} onPageChange={setPage} />
  </div>;
}
export function ProductFinder({
  onAdd,
  disabled = false,
  inputRef,
  hint
}) {
  const [search, setSearch] = useState('');
  const [rows, setRows] = useState([]);
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  const [searching, setSearching] = useState(true);
  const [scanNotice, setScanNotice] = useState('');
  const scanInFlight = useRef(false);
  const localInput = useRef(null), restoreScanFocus = useRef(false);
  const scanner = inputRef || localInput;
  const disabledRef = useRef(disabled); disabledRef.current = disabled;
  const generation = useRef(0);
  useEffect(() => {
    const current = ++generation.current;
    if (disabled) { setSearching(false); return; }
    setSearching(true);
    const timer = setTimeout(async () => {
      try {
        const result = await workspaceApi.get(`pos/products?search=${encodeURIComponent(search)}`);
        if (generation.current === current) {
          setRows(result.data);
          setError(null);
          setSearching(false);
        }
      } catch (failure) {
        if (generation.current === current) { setError(failure); setSearching(false); }
      }
    }, 200);
    return () => {
      clearTimeout(timer);
      generation.current++;
    };
  }, [search, disabled]);
  useEffect(() => {
    if (!busy && restoreScanFocus.current) {
      restoreScanFocus.current = false;
      if (!disabled && scanner.current?.isConnected && !document.querySelector('[role="dialog"][aria-modal="true"]') && document.activeElement === document.body) scanner.current.focus({ preventScroll: true });
    }
  }, [busy, disabled]);
  async function scan(event) {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    if (!search.trim() || disabled || scanInFlight.current) return;
    const current = ++generation.current;
    scanInFlight.current = true;
    restoreScanFocus.current = document.activeElement === event.currentTarget;
    setBusy(true);
    setSearching(false);
    setError(null);
    setScanNotice('');
    try {
      const result = await workspaceApi.get(`pos/products?search=${encodeURIComponent(search.trim())}&scan=1`);
      if (generation.current !== current || disabledRef.current || scanner.current?.closest('fieldset[disabled]')) return;
      if (result.data.length !== 1) { setScanNotice('Scan an exact SKU or unique barcode, or choose a product below.'); return; }
      onAdd(result.data[0]);
      setSearch('');
    } catch (failure) {
      if (generation.current === current && !disabledRef.current) setError(failure);
    } finally {
      scanInFlight.current = false;
      setBusy(false);
    }
  }
  return <section className="product-finder"><label>Scan barcode or search products<input ref={scanner} aria-label="Scan barcode or search products" value={search} maxLength={100} disabled={disabled || busy} onChange={e => { setSearch(e.target.value); setScanNotice(''); }} onKeyDown={scan} placeholder="Scan, then press Enter" autoComplete="off" /></label>
    {hint && <p className="pos-search-hint">{hint}</p>}
    <ErrorNotice error={error} />{scanNotice && <p className="error" role="alert">{scanNotice}</p>}
    <LoadingState busy={searching || busy} label={busy ? 'Finding scanned item…' : 'Searching products…'} variant="inline" />
    {!searching && !busy && !error && rows.length === 0 && <p className="muted">No matching products. Try a different name or exact SKU.</p>}
    <div className="product-results" aria-busy={searching || busy}>{rows.map(row => <button type="button" className="product-choice secondary" disabled={disabled || busy || searching} key={row.id} onClick={() => onAdd(row)}><strong>{row.name}</strong><span>{row.variant_name || row.sku}</span><small>{row.sku} · {row.default_price}</small></button>)}</div></section>;
}
export function LineEditor({
  lines,
  setLines,
  purchasing = false,
  count = false,
  signed = false,
  disabled = false
}) {
  function add(product) {
    if (lines.some(l => l.variant_id === product.id)) return;
    setLines([...lines, {
      variant_id: product.id,
      name: `${product.name} · ${product.sku}`,
      quantity: '1',
      unit_cost: '0',
      tax_rate: '0'
    }]);
  }
  function change(index, field, value) {
    setLines(lines.map((line, i) => i === index ? {
      ...line,
      [field]: value
    } : line));
  }
  return <fieldset disabled={disabled}><ProductFinder onAdd={add} disabled={disabled} />{lines.map((line, index) => <div className="operation-line" key={line.variant_id}>
    <strong>{line.name}</strong><label>{count ? 'Counted quantity' : signed ? 'Stock change (+ / −)' : 'Quantity'}
      <ValidatedControl><input name={`items.${index}.${purchasing ? 'ordered_qty' : count ? 'counted_qty' : signed ? 'quantity_delta' : 'quantity'}`} data-decimal data-signed={signed} data-positive={!count && !signed} data-nonzero={signed} required inputMode="decimal" aria-label={`Quantity for ${line.name}`} value={line.quantity} onChange={e => change(index, 'quantity', e.target.value)} /></ValidatedControl>
    </label>{purchasing && <><label>Unit cost<ValidatedControl><input name={`items.${index}.unit_cost`} data-decimal min="0" required inputMode="decimal" value={line.unit_cost} onChange={e => change(index, 'unit_cost', e.target.value)} aria-label={"Unit cost"} /></ValidatedControl></label>
      <label>Tax %<ValidatedControl><input name={`items.${index}.tax_rate`} data-decimal min="0" max="100" required inputMode="decimal" value={line.tax_rate} onChange={e => change(index, 'tax_rate', e.target.value)} aria-label={"Tax %"} /></ValidatedControl></label></>}
    <button type="button" className="secondary" onClick={() => setLines(lines.filter((_, i) => i !== index))}><Icon name="delete" />Remove</button></div>)}</fieldset>;
}
export function PaymentFields({
  methods,
  payments,
  setPayments,
  disabled = false
}) {
  function change(index, field, value) {
    setPayments(payments.map((p, i) => i === index ? {
      ...p,
      [field]: value
    } : p));
  }
  return <fieldset disabled={disabled}><p className="muted">Record cash applied after change, or a confirmed external card/bank payment. This screen does not charge cards.</p>{payments.map((payment, index) => <div className="operation-line" key={index}>
    <label>Payment method<ValidatedControl><select name={`payments.${index}.method_id`} required aria-label={`Payment method ${index + 1}`} value={payment.method_id} onChange={e => change(index, 'method_id', e.target.value)}><option value="">Choose method</option>{methods.filter(m => m.active).map(method => <option key={method.id} value={method.id}>{method.name}</option>)}</select></ValidatedControl></label>
    <label>Amount applied<ValidatedControl><input name={`payments.${index}.amount`} data-decimal data-positive="true" required inputMode="decimal" aria-label={`Payment amount ${index + 1}`} value={payment.amount} onChange={e => change(index, 'amount', e.target.value)} /></ValidatedControl></label>
    <label>External reference<ValidatedControl><input name={`payments.${index}.reference`} required={Boolean(payment.method_id && methods.find(m => String(m.id) === String(payment.method_id))?.method_type !== 'cash')} maxLength={150} aria-label={`Payment reference ${index + 1}`} value={payment.reference || ''} onChange={e => change(index, 'reference', e.target.value)} /></ValidatedControl></label>
    <button type="button" className="secondary" onClick={() => setPayments(payments.filter((_, i) => i !== index))}><Icon name="delete" />Remove payment</button></div>)}<button type="button" className="secondary" disabled={payments.length >= 10} onClick={() => setPayments([...payments, {
      method_id: '',
      amount: '',
      reference: ''
    }])}><Icon name="add" />Add split payment</button></fieldset>;
}
