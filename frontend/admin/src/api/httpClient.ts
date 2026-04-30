import { clearJwt, readJwt } from '@/auth/jwtStorage';
import { API_BASE_URL } from './config';

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE';

export interface ApiViolation {
  [field: string]: string[];
}

export class ApiError extends Error {
  public readonly status: number;
  public readonly errorCode: string | null;
  public readonly violations: ApiViolation;

  constructor(status: number, message: string, errorCode: string | null, violations: ApiViolation) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errorCode = errorCode;
    this.violations = violations;
  }
}

interface ApiErrorPayload {
  message?: string;
  errorCode?: string | null;
  violations?: ApiViolation;
}

interface RequestOptions {
  method?: HttpMethod;
  body?: unknown;
  signal?: AbortSignal;
  /**
   * When true, skip auto-clearing the JWT on 401 (used by the login request itself).
   */
  skipAuthFailureHandling?: boolean;
}

let onAuthFailure: (() => void) | null = null;

export const registerAuthFailureHandler = (handler: (() => void) | null): void => {
  onAuthFailure = handler;
};

const handleAuthFailure = (): void => {
  clearJwt();
  if (onAuthFailure !== null) {
    onAuthFailure();
  }
};

export const request = async <TResponse = unknown>(
  path: string,
  options: RequestOptions = {},
): Promise<TResponse> => {
  const headers: HeadersInit = {
    Accept: 'application/json',
  };

  if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  const token = readJwt();
  if (token !== null) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const init: RequestInit = {
    method: options.method ?? 'GET',
    headers,
  };
  if (options.body !== undefined) {
    init.body = JSON.stringify(options.body);
  }
  if (options.signal !== undefined) {
    init.signal = options.signal;
  }

  const response = await fetch(`${API_BASE_URL}${path}`, init);

  if (response.status === 401 && options.skipAuthFailureHandling !== true) {
    handleAuthFailure();
  }

  if (response.status === 204) {
    return undefined as TResponse;
  }

  const text = await response.text();
  const data: unknown = text === '' ? null : JSON.parse(text);

  if (!response.ok) {
    const payload = (data ?? {}) as ApiErrorPayload;
    throw new ApiError(
      response.status,
      payload.message ?? `Request failed with status ${response.status}`,
      payload.errorCode ?? null,
      payload.violations ?? {},
    );
  }

  return data as TResponse;
};
