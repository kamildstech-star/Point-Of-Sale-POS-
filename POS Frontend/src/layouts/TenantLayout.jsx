import { useEffect, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthProvider';
import { errorMessage } from '../api/client';
import AppSidebar from '../components/layout/AppSidebar';
import Topbar from '../components/layout/Topbar';
import NavigationDrawer from '../components/layout/NavigationDrawer';
import { navigationFor, pageTitle } from '../components/layout/navigation';
import DensityProvider from '../components/ui/DensityProvider';
import TenantSwitcher from '../components/layout/TenantSwitcher';
import RouteAccessibility from '../components/layout/RouteAccessibility';

function useCompactNavigation() {
  const query = '(max-width: 1279px)';
  const [compact, setCompact] = useState(() => window.matchMedia?.(query).matches ?? false);
  useEffect(() => {
    const media = window.matchMedia?.(query);
    if (!media) return;
    const update = () => setCompact(media.matches);
    update(); media.addEventListener('change', update);
    return () => media.removeEventListener('change', update);
  }, []);
  return compact;
}

export default function TenantLayout() {
  const { user, logout } = useAuth();
  const location = useLocation();
  const density = location.pathname === '/pos' ? 'comfortable' : 'compact';
  const compact = useCompactNavigation();
  const [drawerOpen, setDrawerOpen] = useState(false);
  const storageKey = `universal-pos:navigation:user:${user.id}`;
  const [collapsed, setCollapsed] = useState(() => {
    try { return localStorage.getItem(storageKey) === 'collapsed'; } catch { return false; }
  });
  const [error, setError] = useState('');
  const [signingOut, setSigningOut] = useState(false);
  const items = navigationFor(user);
  useEffect(() => { setDrawerOpen(false); }, [location.pathname, user.active_tenant?.id, compact]);
  function toggleNavigation() {
    setCollapsed(previous => {
      try { localStorage.setItem(storageKey, previous ? 'expanded' : 'collapsed'); } catch { /* Browser storage is optional. */ }
      return !previous;
    });
  }
  async function signOut() {
    setSigningOut(true); setError('');
    try { await logout(); }
    catch (failure) { setError(errorMessage(failure)); }
    finally { setSigningOut(false); }
  }
  return <DensityProvider value={density}><div data-density={density} className={`workspace app-shell${collapsed ? ' app-shell--collapsed' : ''}`}>
    <a className="skip-link" href="#workspace-content">Skip to content</a>
    <RouteAccessibility businessId={user.active_tenant?.id} pageLabel={pageTitle(location.pathname, items)}/>
    {!compact && <AppSidebar user={user} items={items} collapsed={collapsed} onToggle={toggleNavigation} businessSwitcher={<TenantSwitcher variant="sidebar" collapsed={collapsed}/>}/>}
    <div className="workspace-main">
      <Topbar user={user} items={items} title={pageTitle(location.pathname, items)} compact={compact} drawerOpen={drawerOpen}
        onOpenNavigation={() => setDrawerOpen(true)} onSignOut={signOut} signingOut={signingOut}/>
      {error && <p role="alert" className="error shell-error">{error}</p>}
      <div id="workspace-content" tabIndex={-1}><Outlet key={user.active_tenant?.id || 'no-business'}/></div>
    </div>
    {compact && drawerOpen && <NavigationDrawer user={user} items={items} onClose={() => setDrawerOpen(false)} businessSwitcher={<TenantSwitcher variant="sidebar"/>}/>}
  </div></DensityProvider>;
}
