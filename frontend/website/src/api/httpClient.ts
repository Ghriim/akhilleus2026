import type { ApiViolation } from './types';

const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL as string | undefined) ?? 'https://127.0.0.1:8000';

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

  /**
   * @returns the per-field violations (or {} if the body wasn't a ValidationException-shape).
   */
  violations(): Record<string, string[]> {
    return this.body?.violations ?? {};
  }

  errorCode(): string | null {
    return this.body?.errorCode ?? null;
  }
}

let unauthorizedHandler: (() => void) | null = null;

export function setUnauthorizedHandler(handler: () => void): void {
  unauthorizedHandler = handler;
}

export interface RequestOptions {
  method?: HttpMethod;
  body?: unknown;
  token?: string | null;
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, token } = options;
  const headers: Record<string, string> = {};
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const init: RequestInit = { method, headers };
  if (body !== undefined) {
    init.body = JSON.stringify(body);
  }
  const response = await fetch(`${API_BASE_URL}${path}`, init);

  if (response.status === 401) {
    unauthorizedHandler?.();
  }

  if (response.status === 204) {
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
    // Fallback message: Lexik's 401 returns {code, message} (not our ValidationException shape),
    // so fall through to the parsed body's message before the HTTP-status default.
    const fallback =
      parsed && typeof parsed === 'object' && 'message' in parsed && typeof (parsed as { message: unknown }).message === 'string'
        ? (parsed as { message: string }).message
        : `HTTP ${response.status}`;
    const message = apiBody?.message ?? fallback;
    throw new HttpError(response.status, apiBody, message);
  }

  return parsed as T;
}
