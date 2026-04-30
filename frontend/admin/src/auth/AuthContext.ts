import { createContext } from 'react';

export interface AuthIdentity {
  username: string;
  roles: string[];
}

export interface AuthContextValue {
  identity: AuthIdentity | null;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextValue | null>(null);
