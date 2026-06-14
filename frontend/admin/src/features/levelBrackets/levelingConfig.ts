import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { request } from '@/api/httpClient';

export interface LevelingConfig {
  id: string;
  xpPerWorkoutMinute: number;
}

export interface LevelingConfigFormValues {
  xpPerWorkoutMinute: number;
}

const RESOURCE = '/api/admin/leveling-config';

export const fetchLevelingConfig = (signal?: AbortSignal) =>
  request<LevelingConfig>(RESOURCE, signal !== undefined ? { signal } : {});

export const updateLevelingConfig = (values: LevelingConfigFormValues) =>
  request<LevelingConfig>(RESOURCE, { method: 'PUT', body: values });

const CONFIG_KEY = ['levelingConfig'] as const;

export const useLevelingConfigQuery = () =>
  useQuery({
    queryKey: CONFIG_KEY,
    queryFn: ({ signal }) => fetchLevelingConfig(signal),
  });

export const useUpdateLevelingConfigMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (values: LevelingConfigFormValues) => updateLevelingConfig(values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: CONFIG_KEY });
    },
  });
};
