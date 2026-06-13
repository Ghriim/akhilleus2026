import { request } from '@/api/httpClient';
import type { LevelBracket, LevelBracketFormValues } from './types';

const RESOURCE = '/api/admin/level-brackets';

export const fetchLevelBrackets = (signal?: AbortSignal) =>
  request<LevelBracket[]>(RESOURCE, signal !== undefined ? { signal } : {});

export const fetchLevelBracket = (id: string, signal?: AbortSignal) =>
  request<LevelBracket>(`${RESOURCE}/${id}`, signal !== undefined ? { signal } : {});

export const createLevelBracket = (values: LevelBracketFormValues) =>
  request<LevelBracket>(RESOURCE, { method: 'POST', body: values });

export const updateLevelBracket = (id: string, values: LevelBracketFormValues) =>
  request<LevelBracket>(`${RESOURCE}/${id}`, { method: 'PUT', body: values });

export const deleteLevelBracket = (id: string) =>
  request<void>(`${RESOURCE}/${id}`, { method: 'DELETE' });
