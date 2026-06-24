import { createContext, useCallback, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { DEFAULT_THEME, isTheme, THEME_STORAGE_KEY, type Theme } from './constants';

interface ThemeContextValue {
  theme: Theme;
  setTheme: (theme: Theme) => void;
}

export const ThemeContext = createContext<ThemeContextValue | null>(null);

/**
 * Resolves the boot theme: the value the pre-paint script in index.html already wrote to
 * `<html data-theme>` wins (so React state matches the painted UI), then localStorage, then
 * the default. Persistence is device-local for now (will move to the player profile later).
 */
function readInitialTheme(): Theme {
  const fromDom = document.documentElement.dataset.theme;
  if (isTheme(fromDom)) return fromDom;
  try {
    const stored = localStorage.getItem(THEME_STORAGE_KEY);
    if (isTheme(stored)) return stored;
  } catch {
    /* localStorage unavailable — fall through to default */
  }
  return DEFAULT_THEME;
}

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [theme, setThemeState] = useState<Theme>(readInitialTheme);

  useEffect(() => {
    document.documentElement.dataset.theme = theme;
    try {
      localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch {
      /* ignore persistence failure */
    }
  }, [theme]);

  const setTheme = useCallback((next: Theme) => setThemeState(next), []);

  return <ThemeContext.Provider value={{ theme, setTheme }}>{children}</ThemeContext.Provider>;
}
