import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { ListQueryParams } from '@/api/listParams';
import {
  createMuscle,
  deleteMuscle,
  fetchMuscle,
  fetchMuscles,
  updateMuscle,
} from './api';
import type { MuscleFormValues } from './types';

const LIST_ROOT_KEY = ['muscles', 'list'] as const;
const listKey = (params: ListQueryParams) => [...LIST_ROOT_KEY, params] as const;
const detailKey = (id: string) => ['muscles', 'detail', id] as const;

const DEFAULT_LIST_PARAMS: ListQueryParams = { sort: 'label', direction: 'ASC' };

export const useMusclesQuery = (params: ListQueryParams = DEFAULT_LIST_PARAMS) =>
  useQuery({
    queryKey: listKey(params),
    queryFn: ({ signal }) => fetchMuscles(params, signal),
  });

export const useMuscleQuery = (id: string | undefined) =>
  useQuery({
    queryKey: id !== undefined ? detailKey(id) : ['muscles', 'detail', '__pending__'],
    queryFn: ({ signal }) => fetchMuscle(id as string, signal),
    enabled: id !== undefined,
  });

export const useCreateMuscleMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: MuscleFormValues) => createMuscle(values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};

export const useUpdateMuscleMutation = (id: string) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: MuscleFormValues) => updateMuscle(id, values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
      void queryClient.invalidateQueries({ queryKey: detailKey(id) });
    },
  });
};

export const useDeleteMuscleMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => deleteMuscle(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};
