import { request } from '@/api/httpClient';
import { buildListQueryString, type ListQueryParams } from '@/api/listParams';
import type { Muscle, MuscleFormValues } from './types';

const RESOURCE = '/api/admin/muscles';

export const fetchMuscles = (params?: ListQueryParams, signal?: AbortSignal) =>
  request<Muscle[]>(
    `${RESOURCE}${buildListQueryString(params)}`,
    signal !== undefined ? { signal } : {},
  );

export const fetchMuscle = (id: string, signal?: AbortSignal) =>
  request<Muscle>(`${RESOURCE}/${id}`, signal !== undefined ? { signal } : {});

export const createMuscle = (values: MuscleFormValues) =>
  request<Muscle>(RESOURCE, { method: 'POST', body: values });

export const updateMuscle = (id: string, values: MuscleFormValues) =>
  request<Muscle>(`${RESOURCE}/${id}`, { method: 'PUT', body: values });

export const deleteMuscle = (id: string) =>
  request<void>(`${RESOURCE}/${id}`, { method: 'DELETE' });
