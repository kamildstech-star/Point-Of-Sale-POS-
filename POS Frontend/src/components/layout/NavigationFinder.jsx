import { useState } from 'react';
import { Link } from 'react-router-dom';
import Modal from '../Modal';
import Icon from '../Icon';

export default function NavigationFinder({ items }) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const results = items.filter(item => item.label.toLowerCase().includes(query.trim().toLowerCase()));
  function close() { setOpen(false); setQuery(''); }
  return <>
    <button type="button" className="secondary navigation-search" aria-label="Search pages" title="Search permitted pages" aria-haspopup="dialog" onClick={() => setOpen(true)}><Icon name="search" size={16}/><span>Search pages</span></button>
    {open && <Modal title="Go to page" onClose={close} size="small">
      <label>Search permitted pages<input aria-label="Search permitted pages" type="search" value={query} onChange={event => setQuery(event.target.value)} maxLength={100} autoComplete="off"/></label>
      <p className="muted">Search navigation by page name.</p>
      <nav aria-label="Page search results" className="navigation-results">{results.map(item => <Link key={item.to} to={item.to} onClick={close}><Icon name={item.icon}/>{item.label}<Icon name="forward" size={16}/></Link>)}</nav>
      {!results.length && <p role="status">No permitted pages match this search.</p>}
    </Modal>}
  </>;
}
