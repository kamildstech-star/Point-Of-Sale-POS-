import { useLayoutEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { ToastContainer } from 'react-toastify';
import { registerNotificationHost } from '../notifications/host';
import Icon from './Icon';

export default function GlobalToaster() {
  const [host] = useState(() => {
    const element = document.createElement('div');
    element.id = 'pos-toast-root';
    return element;
  });
  useLayoutEffect(() => registerNotificationHost(host), [host]);
  // The portal target stays the same when a modal opens/closes, preserving
  // notifications while keeping their controls inside the active dialog.
  return createPortal(<ToastContainer position="top-right" autoClose={4500} limit={3}
    newestOnTop pauseOnHover pauseOnFocusLoss closeOnClick={false} draggable={false}
    icon={({ type }) => <Icon name={['success', 'error', 'warning', 'info'].includes(type) ? type : 'info'} size={22} />}
    closeButton={({ closeToast }) => <button type="button" className="Toastify__close-button Toastify__close-button--light" aria-label="close" onClick={event => { event.stopPropagation(); closeToast(event); }}><Icon name="close" size={16} /></button>}
    role="status" ariaLabel="Notifications (Alt+T)" theme="light"/>, host);
}
