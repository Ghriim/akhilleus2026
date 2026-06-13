import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  createLevelBracket,
  deleteLevelBracket,
  fetchLevelBracket,
  fetchLevelBrackets,
  updateLevelBracket,
} from './api';
import type { LevelBracketFormValues } from './types';

const LIST_ROOT_KEY = ['levelBrackets', 'list'] as const;
const detailKey = (id: string) => ['levelBrackets', 'detail', id] as const;

export const useLevelBracketsQuery = () =>
  useQuery({
    queryKey: LIST_ROOT_KEY,
    queryFn: ({ signal }) => fetchLevelBrackets(signal),
  });

export const useLevelBracketQuery = (id: string | undefined) =>
  useQuery({
    queryKey: id !== undefined ? detailKey(id) : ['levelBrackets', 'detail', '__pending__'],
    queryFn: ({ signal }) => fetchLevelBracket(id as string, signal),
    enabled: id !== undefined,
  });

export const useCreateLevelBracketMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: LevelBracketFormValues) => createLevelBracket(values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};

export const useUpdateLevelBracketMutation = (id: string) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: LevelBracketFormValues) => updateLevelBracket(id, values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
      void queryClient.invalidateQueries({ queryKey: detailKey(id) });
    },
  });
};

export const useDeleteLevelBracketMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => deleteLevelBracket(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};
