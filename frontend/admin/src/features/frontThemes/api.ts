import { request } from '@/api/httpClient';
import type { FrontTheme } from './types';

const RESOURCE = '/api/admin/front-themes';

export const fetchFrontThemes = (signal?: AbortSignal) =>
  request<FrontTheme[]>(RESOURCE, signal !== undefined ? { signal } : {});

export const fetchFrontTheme = (id: string, signal?: AbortSignal) =>
  request<FrontTheme>(`${RESOURCE}/${id}`, signal !== undefined ? { signal } : {});

// Create & update send multipart/form-data (the image upload). PHP only exposes uploaded files on
// POST, so the update is a POST too (see FrontThemeAdminController).
export const createFrontTheme = (body: FormData) =>
  request<FrontTheme>(RESOURCE, { method: 'POST', body });

export const updateFrontTheme = (id: string, body: FormData) =>
  request<FrontTheme>(`${RESOURCE}/${id}`, { method: 'POST', body });

export const deleteFrontTheme = (id: string) =>
  request<void>(`${RESOURCE}/${id}`, { method: 'DELETE' });
