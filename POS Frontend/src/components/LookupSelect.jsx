import Icon from './Icon';
import { useEffect, useState } from 'react';
import { workspaceApi } from '../api/workspace';
import { ErrorNotice, ValidatedControl } from './WorkspaceForm';
export default function LookupSelect({
  name,
  label,
  endpoint,
  value = '',
  options = [],
  required = true,
  onChange
}) {
  const [selected, setSelected] = useState(String(value ?? ''));
  const [selectedRow, setSelectedRow] = useState(options.find(row => String(row.id) === String(value)));
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);
  const [state, setState] = useState({
    rows: options,
    last: options.length >= 50 ? 2 : 1
  });
  const optionsKey = JSON.stringify(options);
  useEffect(() => {
    if (value && !options.some(row => String(row.id) === String(value))) {
      let alive = true;
      const [resource, filters] = endpoint.split('?');
      workspaceApi.get(`${resource}/${value}${filters ? `?${filters}` : ''}`).then(result => {
        if (alive) setSelectedRow(result.data);
      }).catch(error => {
        if (alive) setState(previous => ({
          ...previous,
          error
        }));
      });
      return () => {
        alive = false;
      };
    }
  }, [value, endpoint]);
  useEffect(() => {
    if (!query && page === 1) {
      setState({
        rows: options,
        last: options.length >= 50 ? 2 : 1
      });
      return;
    }
    let alive = true;
    const timer = setTimeout(async () => {
      try {
        const result = await workspaceApi.get(`${endpoint}${endpoint.includes('?') ? '&' : '?'}search=${encodeURIComponent(query)}&page=${page}`);
        if (alive) setState({
          rows: result.data,
          last: result.last_page
        });
      } catch (error) {
        if (alive) setState(previous => ({
          ...previous,
          error
        }));
      }
    }, 200);
    return () => {
      alive = false;
      clearTimeout(timer);
    };
  }, [query, page, endpoint, optionsKey]);
  const rows = selected && !state.rows.some(row => String(row.id) === selected) ? [...state.rows, selectedRow || {
    id: selected,
    name: 'Current selection'
  }] : state.rows;
  return <div className="lookup"><label>{label}<ValidatedControl><select name={name} required={required} value={selected} onChange={event => {
          setSelected(event.target.value);
          setSelectedRow(rows.find(row => String(row.id) === event.target.value));
          onChange?.(event.target.value);
        }} aria-label={label}><option value="">Choose {label.toLowerCase()}</option>{rows.map(row => <option key={row.id} value={row.id}>{row.sku || row.name || row.value}</option>)}</select></ValidatedControl></label><div className="lookup-search"><input type="search" aria-label={`Find ${label.toLowerCase()}`} placeholder={`Find ${label.toLowerCase()}`} value={query} maxLength={100} onChange={event => {
        setQuery(event.target.value);
        setPage(1);
      }} /><button type="button" className="secondary" aria-label={`Previous ${label.toLowerCase()} options`} disabled={page <= 1} onClick={() => setPage(page - 1)}><Icon name="previous" /></button><button type="button" className="secondary" aria-label={`Next ${label.toLowerCase()} options`} disabled={page >= state.last} onClick={() => setPage(page + 1)}><Icon name="next" /></button></div><ErrorNotice error={state.error} /></div>;
}
