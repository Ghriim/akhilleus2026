export const THEMES = ['basic', 'system'] as const;

export type Theme = (typeof THEMES)[number];

/** Solo Leveling-inspired "System" is the default until a per-user preference is stored server-side. */
export const DEFAULT_THEME: Theme = 'system';

export const THEME_STORAGE_KEY = 'theme';

export function isTheme(value: unknown): value is Theme {
  return 'string' === typeof value && (THEMES as readonly string[]).includes(value);
}
