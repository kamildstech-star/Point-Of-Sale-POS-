import { useEffect, useRef, useState } from 'react';

const dialogOpen = () => Boolean(document.querySelector('[role="dialog"][aria-modal="true"]'));
const editable = target => target?.closest?.('input, select, textarea, [contenteditable]:not([contenteditable="false"]), [role="textbox"], [role="combobox"]');

export function usePosSearch({ searchRef, ready, locked, initialRecall }) {
  const attempted = useRef(false);
  function focusSearch() {
    if (locked || dialogOpen() || !searchRef.current || searchRef.current.disabled) return;
    searchRef.current.focus({ preventScroll: true });
    searchRef.current.select();
    searchRef.current.scrollIntoView?.({ block: 'center' });
  }
  useEffect(() => {
    if (!ready || attempted.current) return;
    attempted.current = true;
    // Avoid opening a touch keyboard or interrupting an existing interaction.
    if (locked || initialRecall || window.matchMedia?.('(pointer: coarse)').matches) return;
    const frame = requestAnimationFrame(() => {
      if (!dialogOpen() && [document.body, document.documentElement].includes(document.activeElement) && !searchRef.current?.disabled) searchRef.current?.focus({ preventScroll: true });
    });
    return () => cancelAnimationFrame(frame);
  }, [ready, locked, initialRecall]);
  useEffect(() => {
    function shortcut(event) {
      if (event.key !== 'F2' || event.repeat || event.ctrlKey || event.altKey || event.metaKey || event.shiftKey || editable(event.target) || dialogOpen() || locked) return;
      event.preventDefault(); focusSearch();
    }
    document.addEventListener('keydown', shortcut);
    return () => document.removeEventListener('keydown', shortcut);
  }, [locked]);
  return focusSearch;
}

export function isQuoteExpired(expiresAt) {
  const expiry = Date.parse(expiresAt);
  return Number.isFinite(expiry) && Date.now() >= expiry;
}

export function useVisiblePaymentPanel(panelRef) {
  useEffect(() => {
    const panel = panelRef.current;
    let frame;
    function measure() {
      frame = undefined;
      panel.style.setProperty('--pos-visible-top', `${Math.max(0, panel.getBoundingClientRect().top)}px`);
    }
    function schedule() { if (frame === undefined) frame = requestAnimationFrame(measure); }
    measure();
    const observer = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(schedule);
    observer?.observe(panel); observer?.observe(panel.closest('main'));
    window.addEventListener('scroll', schedule, { passive: true }); window.addEventListener('resize', schedule);
    return () => { observer?.disconnect(); cancelAnimationFrame(frame); window.removeEventListener('scroll', schedule); window.removeEventListener('resize', schedule); };
  }, []);
}

// Expiry changes presentation only. Laravel still validates the authoritative quote.
export function useQuoteExpiry(expiresAt) {
  const [expired, setExpired] = useState(() => isQuoteExpired(expiresAt));
  useEffect(() => {
    let timer;
    function update() {
      clearTimeout(timer);
      const expiry = Date.parse(expiresAt), ended = isQuoteExpired(expiresAt);
      setExpired(ended);
      if (Number.isFinite(expiry) && !ended) timer = setTimeout(update, Math.min(2147483647, Math.max(1, expiry - Date.now() + 10)));
    }
    update(); window.addEventListener('focus', update); document.addEventListener('visibilitychange', update);
    return () => { clearTimeout(timer); window.removeEventListener('focus', update); document.removeEventListener('visibilitychange', update); };
  }, [expiresAt]);
  return expired;
}
