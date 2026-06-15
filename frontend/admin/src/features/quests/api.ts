import { request } from '@/api/httpClient';
import type { Quest, QuestPayload } from './types';

const RESOURCE = '/api/admin/quests';

export const fetchQuests = (signal?: AbortSignal) =>
  request<Quest[]>(RESOURCE, signal !== undefined ? { signal } : {});

export const fetchQuest = (id: string, signal?: AbortSignal) =>
  request<Quest>(`${RESOURCE}/${id}`, signal !== undefined ? { signal } : {});

export const createQuest = (values: QuestPayload) =>
  request<Quest>(RESOURCE, { method: 'POST', body: values });

export const updateQuest = (id: string, values: QuestPayload) =>
  request<Quest>(`${RESOURCE}/${id}`, { method: 'PUT', body: values });

export const deleteQuest = (id: string) =>
  request<void>(`${RESOURCE}/${id}`, { method: 'DELETE' });
