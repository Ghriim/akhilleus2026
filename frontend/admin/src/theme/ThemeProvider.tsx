import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react';
import { ConfigProvider, theme as antdTheme } from 'antd';
import { ThemeContext, type ThemeContextValue } from './ThemeContext';
import { readStoredThemeMode, writeStoredThemeMode, type ThemeMode } from './themeStorage';

const detectInitialMode = (): ThemeMode => {
  const stored = readStoredThemeMode();
  if (stored !== null) {
    return stored;
  }
  if (typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    return 'dark';
  }
  return 'light';
};

interface ThemeProviderProps {
  children: ReactNode;
}

export const ThemeProvider = ({ children }: ThemeProviderProps) => {
  const [mode, setModeState] = useState<ThemeMode>(detectInitialMode);

  useEffect(() => {
    writeStoredThemeMode(mode);
    document.documentElement.dataset['theme'] = mode;
  }, [mode]);

  const setMode = useCallback((next: ThemeMode) => {
    setModeState(next);
  }, []);

  const toggle = useCallback(() => {
    setModeState((current) => (current === 'light' ? 'dark' : 'light'));
  }, []);

  const value = useMemo<ThemeContextValue>(() => ({ mode, toggle, setMode }), [mode, toggle, setMode]);

  return (
    <ThemeContext.Provider value={value}>
      <ConfigProvider
        theme={{
          algorithm: mode === 'dark' ? antdTheme.darkAlgorithm : antdTheme.defaultAlgorithm,
          cssVar: true,
        }}
      >
        {children}
      </ConfigProvider>
    </ThemeContext.Provider>
  );
};
