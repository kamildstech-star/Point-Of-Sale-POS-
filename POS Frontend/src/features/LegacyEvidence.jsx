import TableViewport from '../components/ui/TableViewport';
import Icon from '../components/Icon';
import { useEffect, useState } from 'react';
import { workspaceApi } from '../api/workspace';
import { ErrorNotice } from '../components/WorkspaceForm';

function Evidence({ value }) {
  const parsed = typeof value === 'string' ? JSON.parse(value) : value;
  return <details><summary>Original source evidence</summary><pre>{JSON.stringify(parsed, null, 2)}</pre></details>;
}

export default function LegacyEvidence({ report }) {
  const [mappings, setMappings] = useState(report.mappings);
  const [exceptions, setExceptions] = useState(report.exceptions);
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  useEffect(() => { setMappings(report.mappings); setExceptions(report.exceptions); }, [report]);
  async function page(kind, number) {
    setBusy(true); setError(null);
    try {
      const result = await workspaceApi.get(`legacy/catalog?${kind === 'mappings' ? 'mapping_page' : 'exception_page'}=${number}`);
      if (kind === 'mappings') setMappings(result.data.mappings); else setExceptions(result.data.exceptions);
    } catch (failure) { setError(failure); }
    finally { setBusy(false); }
  }
  function controls(kind, result) {
    if (!result || result.last_page <= 1) return null;
    return <div className="pagination"><button className="secondary" disabled={busy || result.current_page <= 1} onClick={() => page(kind, result.current_page - 1)}><Icon name="previous" />Previous</button><span>Page {result.current_page} of {result.last_page}</span><button className="secondary" disabled={busy || result.current_page >= result.last_page} onClick={() => page(kind, result.current_page + 1)}><Icon name="next" />Next</button></div>;
  }
  return <><ErrorNotice error={error}/><section className="card editor-card"><h2>Mapped source rows</h2>{mappings.data.length === 0 ? <p>No source rows have been mapped yet.</p> : <TableViewport className="table-wrap"><table><thead><tr><th>Legacy stock</th><th>Variant reference</th><th>Preserved source</th></tr></thead><tbody>{mappings.data.map(row => <tr key={row.id}><td>{row.source_pk}</td><td>{row.target_pk}</td><td><Evidence value={row.mapping_json}/></td></tr>)}</tbody></table></TableViewport>}{controls('mappings', mappings)}</section><section className="card editor-card"><h2>Exception queue</h2>{exceptions.data.length === 0 ? <p>No unresolved exceptions.</p> : <TableViewport className="table-wrap"><table><thead><tr><th>Legacy stock</th><th>Issue</th><th>Review evidence</th></tr></thead><tbody>{exceptions.data.map(row => <tr key={row.id}><td>{row.source_pk}</td><td>{row.issue_code.replaceAll('_', ' ')}</td><td><Evidence value={row.details_json}/></td></tr>)}</tbody></table></TableViewport>}{controls('exceptions', exceptions)}<p className="footnote">Correct ambiguous source records through the existing POS, then rerun reconciliation. Changes to established mappings require review.</p></section></>;
}
