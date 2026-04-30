import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { ListQueryParams } from '@/api/listParams';
import {
  createEquipment,
  deleteEquipment,
  fetchEquipment,
  fetchEquipments,
  updateEquipment,
} from './api';
import type { EquipmentFormValues } from './types';

const LIST_ROOT_KEY = ['equipments', 'list'] as const;
const listKey = (params: ListQueryParams) => [...LIST_ROOT_KEY, params] as const;
const detailKey = (id: string) => ['equipments', 'detail', id] as const;

const DEFAULT_LIST_PARAMS: ListQueryParams = { sort: 'label', direction: 'ASC' };

export const useEquipmentsQuery = (params: ListQueryParams = DEFAULT_LIST_PARAMS) =>
  useQuery({
    queryKey: listKey(params),
    queryFn: ({ signal }) => fetchEquipments(params, signal),
  });

export const useEquipmentQuery = (id: string | undefined) =>
  useQuery({
    queryKey: id !== undefined ? detailKey(id) : ['equipments', 'detail', '__pending__'],
    queryFn: ({ signal }) => fetchEquipment(id as string, signal),
    enabled: id !== undefined,
  });

export const useCreateEquipmentMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: EquipmentFormValues) => createEquipment(values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};

export const useUpdateEquipmentMutation = (id: string) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: EquipmentFormValues) => updateEquipment(id, values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
      void queryClient.invalidateQueries({ queryKey: detailKey(id) });
    },
  });
};

export const useDeleteEquipmentMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => deleteEquipment(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};
