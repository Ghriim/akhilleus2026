import { request } from '@/api/httpClient';
import { buildListQueryString, type ListQueryParams } from '@/api/listParams';
import type { Equipment, EquipmentFormValues } from './types';

const RESOURCE = '/api/admin/equipments';

export const fetchEquipments = (params?: ListQueryParams, signal?: AbortSignal) =>
  request<Equipment[]>(
    `${RESOURCE}${buildListQueryString(params)}`,
    signal !== undefined ? { signal } : {},
  );

export const fetchEquipment = (id: string, signal?: AbortSignal) =>
  request<Equipment>(`${RESOURCE}/${id}`, signal !== undefined ? { signal } : {});

export const createEquipment = (values: EquipmentFormValues) =>
  request<Equipment>(RESOURCE, { method: 'POST', body: values });

export const updateEquipment = (id: string, values: EquipmentFormValues) =>
  request<Equipment>(`${RESOURCE}/${id}`, { method: 'PUT', body: values });

export const deleteEquipment = (id: string) =>
  request<void>(`${RESOURCE}/${id}`, { method: 'DELETE' });
