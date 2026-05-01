import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { apiRequest, setUnauthorizedHandler } from '../api/httpClient';
import type { LoginResponse } from '../api/types';

const TOKEN_STORAGE_KEY = 'akhilleus.player.jwt';

interface AuthContextValue {
  token: string | null;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  setToken: (token: string) => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

interface AuthProviderProps {
  children: ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
  const [token, setTokenState] = useState<string | null>(() =>
    localStorage.getItem(TOKEN_STORAGE_KEY),
  );

  const setToken = useCallback((next: string) => {
    localStorage.setItem(TOKEN_STORAGE_KEY, next);
    setTokenState(next);
  }, []);

  const logout = useCallback(() => {
    localStorage.removeItem(TOKEN_STORAGE_KEY);
    setTokenState(null);
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(() => {
      localStorage.removeItem(TOKEN_STORAGE_KEY);
      setTokenState(null);
    });
  }, []);

  const login = useCallback(
    async (email: string, password: string) => {
      const response = await apiRequest<LoginResponse>('/api/security/login', {
        method: 'POST',
        body: { email, password },
      });
      setToken(response.token);
    },
    [setToken],
  );

  const value = useMemo<AuthContextValue>(
    () => ({
      token,
      isAuthenticated: token !== null,
      login,
      logout,
      setToken,
    }),
    [token, login, logout, setToken],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return ctx;
}
