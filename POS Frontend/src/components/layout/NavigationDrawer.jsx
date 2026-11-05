import Modal from '../Modal';
import AppSidebar from './AppSidebar';

export default function NavigationDrawer({ user, items, onClose, businessSwitcher }) {
  return <Modal title="Navigation" size="navigation" onClose={onClose}>
    <AppSidebar user={user} items={items} drawer onNavigate={onClose} businessSwitcher={businessSwitcher}/>
  </Modal>;
}
