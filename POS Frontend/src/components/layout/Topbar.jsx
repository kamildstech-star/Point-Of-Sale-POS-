import { useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import Icon from '../Icon';
import ThemeToggle from '../../theme/ThemeToggle';
import TenantSwitcher from './TenantSwitcher';
import NavigationFinder from './NavigationFinder';

export default function Topbar({ user, items, title, compact, drawerOpen, onOpenNavigation, onSignOut, signingOut }) {
  const profile = useRef(null);
  useEffect(() => {
    function dismiss(event) {
      if (!profile.current?.open) return;
      if (event.type === 'keydown' && event.key === 'Escape') {
        profile.current.open = false; profile.current.querySelector('summary').focus();
      } else if (event.type === 'pointerdown' && !profile.current.contains(event.target)) profile.current.open = false;
    }
    document.addEventListener('pointerdown', dismiss); document.addEventListener('keydown', dismiss);
    return () => { document.removeEventListener('pointerdown', dismiss); document.removeEventListener('keydown', dismiss); };
  }, []);
  return <header className="topbar app-topbar">
    <div className="topbar-context">
      {compact && <button type="button" className="secondary shell-icon-button" aria-label="Open navigation" aria-expanded={drawerOpen} aria-haspopup="dialog" aria-controls={drawerOpen ? 'drawer-navigation' : undefined} onClick={onOpenNavigation}><Icon name="menu" size={22}/></button>}
      <nav className="page-breadcrumb" aria-label="Breadcrumb"><Link to="/workspace">Retail workspace</Link><strong aria-current="page">{title}</strong></nav>
    </div>
    <div className="topbar-actions">
      {compact && <TenantSwitcher/>}
      <NavigationFinder items={items}/>
      <ThemeToggle/>
      <details className="profile-menu" ref={profile}>
        <summary aria-label="User options" title={user.name}>
          <span className="staff-avatar" aria-hidden="true">{user.name.slice(0, 1).toUpperCase()}</span>
          <span className="staff-name">{user.name}</span><Icon name="down" size={14}/>
        </summary>
        <div className="profile-panel">
          <strong>{user.name}</strong>{user.email && <span>{user.email}</span>}<span>{user.role?.name || 'Staff'}</span>
          <Link to="/workspace" onClick={() => { profile.current.open = false; }}>Manage businesses</Link>
        </div>
      </details>
      <button type="button" className="secondary sign-out-button" onClick={onSignOut} disabled={signingOut} aria-label={signingOut ? 'Signing out…' : 'Sign out'} title="Sign out">
        <Icon name="logout" size={18}/><span>{signingOut ? 'Signing out…' : 'Sign out'}</span>
      </button>
    </div>
  </header>;
}
