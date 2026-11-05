let host = null;
let activePanel = null;
function moveHost() {
  if (!host) return;
  const target = activePanel || document.body;
  if (host.parentNode !== target) target.appendChild(host);
}
export function setNotificationPanel(panel) {
  activePanel = panel;
  moveHost();
}
export function registerNotificationHost(element) {
  host = element;
  moveHost();
  return () => { if (host === element) host = null; element.remove(); };
}
