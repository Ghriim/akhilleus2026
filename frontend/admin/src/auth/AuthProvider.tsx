import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react';
import { request, registerAuthFailureHandler } from '@/api/httpClient';
import { AuthContext, type AuthContextValue, type AuthIdentity } from './AuthContext';
import {
  clearJwt,
  isJwtExpired,
  readJwt,
  readJwtRoles,
  readJwtUsername,
  writeJwt,
} from './jwtStorage';

interface LoginResponse {
  token: string;
}

const identityFromToken = (token: string): AuthIdentity | null => {
  const username = readJwtUsername(token);
  if (username === null) {
    return null;
  }
  return { username, roles: readJwtRoles(token) };
};

const readInitialIdentity = (): AuthIdentity | null => {
  const token = readJwt();
  if (token === null || isJwtExpired(token)) {
    if (token !== null) {
      clearJwt();
    }
    return null;
  }
  return identityFromToken(token);
};

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider = ({ children }: AuthProviderProps) => {
  const [identity, setIdentity] = useState<AuthIdentity | null>(readInitialIdentity);

  useEffect(() => {
    registerAuthFailureHandler(() => setIdentity(null));
    return () => registerAuthFailureHandler(null);
  }, []);

  const login = useCallback(async (email: string, password: string): Promise<void> => {
    const response = await request<LoginResponse>('/api/security/login', {
      method: 'POST',
      body: { email, password },
      skipAuthFailureHandling: true,
    });
    writeJwt(response.token);
    setIdentity(identityFromToken(response.token));
  }, []);

  const logout = useCallback(async (): Promise<void> => {
    try {
      await request('/api/security/logout', { method: 'POST', skipAuthFailureHandling: true });
    } catch {
      // best effort — even if the logout call fails we still wipe local state
    }
    clearJwt();
    setIdentity(null);
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      identity,
      isAuthenticated: identity !== null,
      login,
      logout,
    }),
    [identity, login, logout],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
