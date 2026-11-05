// Keep the passive indicator inside the active dialog's accessible layer,
// using a stable portal target so opening a dialog does not reset its timers.
let host = null;
let activePanel = null;
function moveHost() {
  if (!host) return;
  const target = activePanel || document.body;
  if (host.parentNode !== target) target.appendChild(host);
}
export function setLoadingPanel(panel) { activePanel = panel; moveHost(); }
export function registerLoadingHost(element) {
  host = element; moveHost();
  return () => { if (host === element) host = null; element.remove(); };
}
