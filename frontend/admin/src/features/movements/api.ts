import { request } from '@/api/httpClient';
import { buildListQueryString, type ListQueryParams } from '@/api/listParams';
import type { Movement, MovementFormValues, MovementListItem } from './types';

const RESOURCE = '/api/admin/movements';

export const fetchMovements = (params?: ListQueryParams, signal?: AbortSignal) =>
  request<MovementListItem[]>(
    `${RESOURCE}${buildListQueryString(params)}`,
    signal !== undefined ? { signal } : {},
  );

export const fetchMovement = (id: string, signal?: AbortSignal) =>
  request<Movement>(`${RESOURCE}/${id}`, signal !== undefined ? { signal } : {});

export const createMovement = (values: MovementFormValues) =>
  request<Movement>(RESOURCE, { method: 'POST', body: values });

export const updateMovement = (id: string, values: MovementFormValues) =>
  request<Movement>(`${RESOURCE}/${id}`, { method: 'PUT', body: values });

export const deleteMovement = (id: string) =>
  request<void>(`${RESOURCE}/${id}`, { method: 'DELETE' });
