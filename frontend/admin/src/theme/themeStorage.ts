const STORAGE_KEY = 'akhilleus-admin:theme';

export type ThemeMode = 'light' | 'dark';

export const readStoredThemeMode = (): ThemeMode | null => {
  const value = window.localStorage.getItem(STORAGE_KEY);
  return value === 'light' || value === 'dark' ? value : null;
};

export const writeStoredThemeMode = (mode: ThemeMode): void => {
  window.localStorage.setItem(STORAGE_KEY, mode);
};
