import { apiRequest } from '../client';
import type { LoginResponse, RegisterPlayerResponse } from '../types';

export interface LoginInput {
  email: string;
  password: string;
}

export interface RegisterInput {
  email: string;
  plainPassword: string;
  displayName: string;
}

export function login(input: LoginInput): Promise<LoginResponse> {
  return apiRequest<LoginResponse>('/api/security/login', { method: 'POST', body: input });
}

export function logout(): Promise<void> {
  return apiRequest<void>('/api/security/logout', { method: 'POST' });
}

export function register(input: RegisterInput): Promise<RegisterPlayerResponse> {
  return apiRequest<RegisterPlayerResponse>('/api/player/registration', {
    method: 'POST',
    body: input,
  });
}
