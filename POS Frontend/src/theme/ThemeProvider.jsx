import { createContext, useContext, useEffect, useLayoutEffect, useState } from 'react';
import { applyTheme, readTheme, saveTheme, themeStorageKey } from './preference';

const ThemeContext = createContext(null);

export function ThemeProvider({ userId, children }) {
  const key = themeStorageKey(userId);
  const [selection, setSelection] = useState(() => ({ key, theme: readTheme(key) }));
  // Derive the correct preference immediately when users change. Never write
  // one user's preference into another user's storage during that transition.
  const theme = selection.key === key ? selection.theme : readTheme(key);
  useLayoutEffect(() => {
    applyTheme(theme);
    if (selection.key !== key) setSelection({ key, theme });
  }, [key, theme, selection.key]);
  useEffect(() => {
    const synchronize = event => {
      if (event.key === key || event.key === null) setSelection({ key, theme: readTheme(key) });
    };
    window.addEventListener('storage', synchronize);
    return () => window.removeEventListener('storage', synchronize);
  }, [key]);
  function setTheme(value) {
    if (!['light', 'dark'].includes(value)) return;
    saveTheme(key, value);
    setSelection({ key, theme: value });
  }
  return <ThemeContext.Provider value={{ theme, setTheme }}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (!context) throw new Error('useTheme must be used inside ThemeProvider');
  return context;
}
