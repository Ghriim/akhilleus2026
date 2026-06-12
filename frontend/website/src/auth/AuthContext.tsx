import { createContext, useCallback, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { TOKEN_STORAGE_KEY, setUnauthorizedHandler } from '@/api/client';
import * as authApi from '@/api/endpoints/auth';

export interface AuthContextValue {
  token: string | null;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  clearToken: () => void;
}

export const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_STORAGE_KEY));

  const persistToken = useCallback((value: string | null) => {
    if (value) {
      localStorage.setItem(TOKEN_STORAGE_KEY, value);
    } else {
      localStorage.removeItem(TOKEN_STORAGE_KEY);
    }
    setToken(value);
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(() => {
      persistToken(null);
    });
  }, [persistToken]);

  const login = useCallback(
    async (email: string, password: string) => {
      const { token: nextToken } = await authApi.login({ email, password });
      persistToken(nextToken);
    },
    [persistToken],
  );

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } finally {
      persistToken(null);
    }
  }, [persistToken]);

  const value = useMemo<AuthContextValue>(
    () => ({
      token,
      isAuthenticated: null !== token,
      login,
      logout,
      clearToken: () => persistToken(null),
    }),
    [token, login, logout, persistToken],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
