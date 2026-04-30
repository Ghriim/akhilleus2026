import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { ListQueryParams } from '@/api/listParams';
import {
  createMovement,
  deleteMovement,
  fetchMovement,
  fetchMovements,
  updateMovement,
} from './api';
import type { MovementFormValues } from './types';

const LIST_ROOT_KEY = ['movements', 'list'] as const;
const listKey = (params: ListQueryParams) => [...LIST_ROOT_KEY, params] as const;
const detailKey = (id: string) => ['movements', 'detail', id] as const;

const DEFAULT_LIST_PARAMS: ListQueryParams = { sort: 'label', direction: 'ASC' };

export const useMovementsQuery = (params: ListQueryParams = DEFAULT_LIST_PARAMS) =>
  useQuery({
    queryKey: listKey(params),
    queryFn: ({ signal }) => fetchMovements(params, signal),
  });

export const useMovementQuery = (id: string | undefined) =>
  useQuery({
    queryKey: id !== undefined ? detailKey(id) : ['movements', 'detail', '__pending__'],
    queryFn: ({ signal }) => fetchMovement(id as string, signal),
    enabled: id !== undefined,
  });

export const useCreateMovementMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: MovementFormValues) => createMovement(values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};

export const useUpdateMovementMutation = (id: string) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: MovementFormValues) => updateMovement(id, values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
      void queryClient.invalidateQueries({ queryKey: detailKey(id) });
    },
  });
};

export const useDeleteMovementMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => deleteMovement(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};
