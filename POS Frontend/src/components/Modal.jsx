import { iconForAction } from '../icons/catalog';
import Icon from './Icon';
import { createContext, useContext, useId, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { setNotificationPanel } from '../notifications/host';
import { setLoadingPanel } from '../loading/host';
import { useDensity } from './ui/DensityProvider';

const SubmissionContext = createContext(null);
const layers = [];
const background = new Map();
let originalOverflow = '';

function restoreAttribute(element, name, value) {
  if (value === null) element.removeAttribute(name); else element.setAttribute(name, value);
}

function syncLayers() {
  const top = layers.at(-1);
  setNotificationPanel(top?.panel || null);
  setLoadingPanel(top?.panel || null);
  if (!top) {
    for (const [element, attributes] of background) {
      restoreAttribute(element, 'inert', attributes.inert);
      restoreAttribute(element, 'aria-hidden', attributes.hidden);
    }
    background.clear();
    document.body.style.overflow = originalOverflow;
    return;
  }
  for (const element of document.body.children) {
    if (['SCRIPT', 'STYLE', 'LINK'].includes(element.tagName)) continue;
    if (!background.has(element)) background.set(element, { inert: element.getAttribute('inert'), hidden: element.getAttribute('aria-hidden') });
    if (element === top.backdrop) {
      const attributes = background.get(element);
      restoreAttribute(element, 'inert', attributes.inert);
      restoreAttribute(element, 'aria-hidden', attributes.hidden);
    } else {
      element.setAttribute('inert', '');
      element.setAttribute('aria-hidden', 'true');
    }
  }
}

function focusable(panel) {
  return [...panel.querySelectorAll('button, a[href], input:not([type="hidden"]), select, textarea, summary, [tabindex]')]
    .filter(element => {
      if (element.matches(':disabled') || element.tabIndex < 0) return false;
      for (let parent = element; parent && parent !== panel; parent = parent.parentElement) {
        if (parent.matches('[hidden], [inert], [aria-hidden="true"]') || getComputedStyle(parent).display === 'none' || getComputedStyle(parent).visibility === 'hidden') return false;
        if (parent.tagName === 'DETAILS' && !parent.open) {
          const summary = [...parent.children].find(child => child.tagName === 'SUMMARY');
          if (!summary?.contains(element)) return false;
        }
      }
      return true;
    });
}

export function useModalSubmission() { return useContext(SubmissionContext); }

export default function Modal({ title, onClose, onSubmitSuccess, children, size = 'medium' }) {
  const density = useDensity();
  const id = useId();
  const panel = useRef(null); const backdrop = useRef(null);
  const callbacks = useRef({ onClose, onSubmitSuccess }); callbacks.current = { onClose, onSubmitSuccess };
  const saving = useRef(false); const [busy, setBusy] = useState(false);
  function setSaving(value) { saving.current = value; setBusy(value); }
  function close() { if (!saving.current) callbacks.current.onClose?.(); }

  useLayoutEffect(() => {
    const trigger = document.activeElement;
    const layer = { panel: panel.current, backdrop: backdrop.current };
    if (!layers.length) originalOverflow = document.body.style.overflow;
    layers.push(layer); document.body.style.overflow = 'hidden'; syncLayers();
    const first = focusable(layer.panel).find(element => element.matches('input, select, textarea'));
    (first || layer.panel).focus();
    // Forms may arrive after reference data or a lazy module. Focus the first
    // field once, provided the user has not already started navigating.
    const fields = first ? null : new MutationObserver(() => {
      if (layers.at(-1) !== layer) return;
      const ready = focusable(layer.panel).find(element => element.matches('input, select, textarea'));
      if (!ready) return;
      if (document.activeElement === layer.panel) ready.focus();
      stopWaiting();
    });
    function stopWaiting() {
      fields?.disconnect();
      document.removeEventListener('keydown', stopWaiting, true);
      document.removeEventListener('pointerdown', stopWaiting, true);
    }
    if (fields) {
      fields.observe(layer.panel, { childList: true, subtree: true });
      document.addEventListener('keydown', stopWaiting, true);
      document.addEventListener('pointerdown', stopWaiting, true);
    }
    function keydown(event) {
      if (layers.at(-1) !== layer) return;
      if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); close(); }
      if (event.key !== 'Tab') return;
      const items = focusable(layer.panel);
      if (!items.length) { event.preventDefault(); layer.panel.focus(); return; }
      if (event.shiftKey && (document.activeElement === items[0] || !items.includes(document.activeElement))) {
        event.preventDefault(); items.at(-1).focus();
      } else if (!event.shiftKey && (document.activeElement === items.at(-1) || !items.includes(document.activeElement))) {
        event.preventDefault(); items[0].focus();
      }
    }
    function focusin(event) {
      if (layers.at(-1) === layer && !layer.panel.contains(event.target)) layer.panel.focus();
    }
    document.addEventListener('keydown', keydown, true); document.addEventListener('focusin', focusin);
    return () => {
      stopWaiting();
      document.removeEventListener('keydown', keydown, true); document.removeEventListener('focusin', focusin);
      const index = layers.indexOf(layer); if (index !== -1) layers.splice(index, 1);
      syncLayers();
      if (trigger?.isConnected && !trigger.closest('[inert]')) trigger.focus();
      else if (layers.length) layers.at(-1).panel.focus();
    };
  }, []);

  return createPortal(<div className="modal-backdrop" ref={backdrop}>
    <section role="dialog" aria-modal="true" aria-labelledby={`${id}-title`} aria-busy={busy}
      className={`modal-panel modal-${size}`} data-density={density} ref={panel} tabIndex={-1}>
      <header className="modal-header"><h2 id={`${id}-title`}>{title}</h2>
        <button type="button" className="modal-close secondary" aria-label="Close dialog" onClick={close} disabled={busy}><Icon name="close" /></button>
      </header>
      <SubmissionContext.Provider value={{ busy, setBusy: setSaving, close, onSuccess: () => callbacks.current.onSubmitSuccess?.() }}>
        <div className="modal-body">{children}</div>
      </SubmissionContext.Provider>
    </section>
  </div>, document.body);
}

export function FormDialog({ title, trigger, children, size = 'medium', onClose, defaultOpen = false, ...buttonProps }) {
  const [open, setOpen] = useState(defaultOpen);
  function close() { setOpen(false); onClose?.(); }
  return <><button type="button" className="dialog-trigger" {...buttonProps} onClick={() => setOpen(true)}><Icon name={iconForAction(typeof trigger === "string" ? trigger : typeof title === "string" ? title : "", "add")} />{trigger || title}</button>
    {open && <Modal title={title} size={size} onClose={close} onSubmitSuccess={close}>{children}</Modal>}
  </>;
}
