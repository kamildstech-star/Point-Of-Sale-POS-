// Theme is a local presentation preference, never an authentication source.
export function themeStorageKey(userId) {
  return `universal-pos:theme:${userId == null ? 'guest' : `user:${userId}`}`;
}

export function readTheme(key) {
  try { return window.localStorage.getItem(key) === 'dark' ? 'dark' : 'light'; }
  catch { return 'light'; }
}

export function saveTheme(key, theme) {
  try { window.localStorage.setItem(key, theme); } catch { /* Storage may be blocked. */ }
}

export function applyTheme(theme) {
  document.documentElement.classList.toggle('dark', theme === 'dark');
  document.documentElement.dataset.theme = theme;
  document.documentElement.style.colorScheme = theme;
}

// Run before mounting React. The authenticated user's preference is applied
// in a layout effect once the server confirms their identity.
export function initializeTheme() {
  applyTheme(readTheme(themeStorageKey(null)));
}
