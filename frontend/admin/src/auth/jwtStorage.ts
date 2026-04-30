const STORAGE_KEY = 'akhilleus-admin:jwt';

export const readJwt = (): string | null => window.localStorage.getItem(STORAGE_KEY);

export const writeJwt = (token: string): void => {
  window.localStorage.setItem(STORAGE_KEY, token);
};

export const clearJwt = (): void => {
  window.localStorage.removeItem(STORAGE_KEY);
};

interface JwtPayload {
  exp: number;
  iat: number;
  roles: string[];
  username: string;
}

const decodeJwtPayload = (token: string): JwtPayload | null => {
  const parts = token.split('.');
  if (parts.length !== 3) {
    return null;
  }
  try {
    const json = atob(parts[1]!.replace(/-/g, '+').replace(/_/g, '/'));
    return JSON.parse(json) as JwtPayload;
  } catch {
    return null;
  }
};

export const isJwtExpired = (token: string): boolean => {
  const payload = decodeJwtPayload(token);
  if (payload === null) {
    return true;
  }
  return payload.exp * 1000 < Date.now();
};

export const readJwtUsername = (token: string): string | null => decodeJwtPayload(token)?.username ?? null;

export const readJwtRoles = (token: string): string[] => decodeJwtPayload(token)?.roles ?? [];
