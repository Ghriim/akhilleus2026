import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createQuest, deleteQuest, fetchQuest, fetchQuests, updateQuest } from './api';
import type { QuestPayload } from './types';

const LIST_ROOT_KEY = ['quests', 'list'] as const;
const detailKey = (id: string) => ['quests', 'detail', id] as const;

export const useQuestsQuery = () =>
  useQuery({
    queryKey: LIST_ROOT_KEY,
    queryFn: ({ signal }) => fetchQuests(signal),
  });

export const useQuestQuery = (id: string | undefined) =>
  useQuery({
    queryKey: id !== undefined ? detailKey(id) : ['quests', 'detail', '__pending__'],
    queryFn: ({ signal }) => fetchQuest(id as string, signal),
    enabled: id !== undefined,
  });

export const useCreateQuestMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: QuestPayload) => createQuest(values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};

export const useUpdateQuestMutation = (id: string) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: QuestPayload) => updateQuest(id, values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
      void queryClient.invalidateQueries({ queryKey: detailKey(id) });
    },
  });
};

export const useDeleteQuestMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => deleteQuest(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: LIST_ROOT_KEY });
    },
  });
};
