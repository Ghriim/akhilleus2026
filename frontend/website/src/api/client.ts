import type { ApiViolation } from './types';

const API_BASE_URL =
  (import.meta.env.VITE_API_BASE_URL as string | undefined) ?? 'http://127.0.0.1:8000';

export const TOKEN_STORAGE_KEY = 'akhilleus:token';

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE';

export class HttpError extends Error {
  constructor(
    public readonly status: number,
    public readonly body: ApiViolation | null,
    message: string,
  ) {
    super(message);
    this.name = 'HttpError';
  }

  violations(): Record<string, string[]> {
    return this.body?.violations ?? {};
  }

  errorCode(): string | null {
    return this.body?.errorCode ?? null;
  }
}

function readStoredToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_STORAGE_KEY);
  } catch {
    return null;
  }
}

let unauthorizedHandler: (() => void) | null = null;

export function setUnauthorizedHandler(handler: () => void): void {
  unauthorizedHandler = handler;
}

export interface RequestOptions {
  method?: HttpMethod;
  body?: unknown;
  query?: Record<string, string | number | boolean | undefined | null>;
}

function buildUrl(path: string, query: RequestOptions['query']): string {
  if (!query) return `${API_BASE_URL}${path}`;
  const params = new URLSearchParams();
  for (const [k, v] of Object.entries(query)) {
    if (v === undefined || v === null) continue;
    params.set(k, String(v));
  }
  const qs = params.toString();
  return qs ? `${API_BASE_URL}${path}?${qs}` : `${API_BASE_URL}${path}`;
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, query } = options;
  const headers: Record<string, string> = {};
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }
  const token = readStoredToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const init: RequestInit = { method, headers };
  if (body !== undefined) {
    init.body = JSON.stringify(body);
  }

  const response = await fetch(buildUrl(path, query), init);

  if (401 === response.status) {
    unauthorizedHandler?.();
  }

  if (204 === response.status) {
    return undefined as T;
  }

  const text = await response.text();
  let parsed: unknown = null;
  if (text) {
    try {
      parsed = JSON.parse(text);
    } catch {
      // non-JSON body
    }
  }

  if (!response.ok) {
    const apiBody =
      parsed && typeof parsed === 'object' && 'errorCode' in parsed
        ? (parsed as ApiViolation)
        : null;
    const fallback =
      parsed &&
      typeof parsed === 'object' &&
      'message' in parsed &&
      typeof (parsed as { message: unknown }).message === 'string'
        ? (parsed as { message: string }).message
        : `HTTP ${response.status}`;
    const message = apiBody?.message ?? fallback;
    throw new HttpError(response.status, apiBody, message);
  }

  return parsed as T;
}
