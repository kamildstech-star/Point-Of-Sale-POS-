import { Link, NavLink } from 'react-router-dom';
import Icon from '../Icon';
import { navigationSections } from './navigation';

export default function AppSidebar({ user, items, collapsed = false, onToggle, onNavigate, drawer = false, businessSwitcher }) {
  return <aside className={`sidebar app-sidebar${collapsed ? ' app-sidebar--collapsed' : ''}${drawer ? ' app-sidebar--drawer' : ''}`} aria-label="Workspace navigation">
    <Link className="wordmark sidebar-brand" to="/" onClick={onNavigate} aria-label="Universal POS home">
      <span className="sidebar-brand__icon"><Icon name="store" size={20}/></span>
      <span className="sidebar-label">Universal POS</span>
    </Link>
    <div className="sidebar-context">{businessSwitcher || <div className="sidebar-context-label sidebar-label"><strong>{user.active_tenant?.name || 'Choose a business'}</strong><small>Business workspace</small></div>}</div>
    <nav aria-label="Main navigation" id={drawer ? 'drawer-navigation' : 'workspace-navigation'}>
      {navigationSections(items).map(section => <section className="sidebar-group" key={section.id} aria-label={section.label}>
        <h2 className="sidebar-group-heading sidebar-label">{section.label}</h2>
        {section.items.map(item => <NavLink className="sidebar-link" key={item.to} to={item.to} end={item.end} onClick={onNavigate}
          title={item.label} aria-label={collapsed ? item.label : undefined}>
          <Icon name={item.icon} size={18}/><span className="sidebar-label">{item.label}</span>
        </NavLink>)}
      </section>)}
    </nav>
    <div className="sidebar-bottom">
      <div className="sidebar-business sidebar-label"><Icon name="team" size={18}/><div>
        <strong>{user.role?.name || 'Staff'}</strong><small>Your workspace access</small>
      </div></div>
      {!drawer && <button type="button" className="sidebar-collapse" aria-label={collapsed ? 'Expand navigation' : 'Collapse navigation'}
        title={collapsed ? 'Expand navigation' : 'Collapse navigation'} aria-expanded={!collapsed} aria-controls="workspace-navigation" onClick={onToggle}>
        <Icon name={collapsed ? 'next' : 'previous'} size={20}/>
      </button>}
    </div>
  </aside>;
}
