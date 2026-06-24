import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  createFrontTheme,
  deleteFrontTheme,
  fetchFrontTheme,
  fetchFrontThemes,
  updateFrontTheme,
} from './api';

const LIST_ROOT_KEY = ['front-themes', 'list'] as const;
const detailKey = (id: string) => ['front-themes', 'detail', id] as const;

export const useFrontThemesQuery = () =>
  useQuery({
    queryKey: LIST_ROOT_KEY,
    queryFn: ({ signal }) => fetchFrontThemes(signal),
  });

export const useFrontThemeQuery = (id: string | undefined) =>
  useQuery({
    queryKey: id !== undefined ? detailKey(id) : ['front-themes', 'detail', '__pending__'],
    queryFn: ({ signal }) => fetchFrontTheme(id as string, signal),
    enabled: id !== undefined,
  });

export const useCreateFrontThemeMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: FormData) => createFrontTheme(body),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};

export const useUpdateFrontThemeMutation = (id: string) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: FormData) => updateFrontTheme(id, body),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
      void queryClient.invalidateQueries({ queryKey: detailKey(id) });
    },
  });
};

export const useDeleteFrontThemeMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => deleteFrontTheme(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};
