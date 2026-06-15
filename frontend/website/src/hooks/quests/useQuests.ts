import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as questsApi from '@/api/endpoints/quests';
import { levelingKeys } from '@/hooks/leveling/keys';
import { profileKeys } from '@/hooks/profile/keys';
import { questKeys } from './keys';

// --- Queries ---

export function useDailyQuests() {
  return useQuery({
    queryKey: questKeys.byPeriodicity('daily'),
    queryFn: questsApi.listDailyQuests,
  });
}

export function useWeeklyQuests() {
  return useQuery({
    queryKey: questKeys.byPeriodicity('weekly'),
    queryFn: questsApi.listWeeklyQuests,
  });
}

export function useMonthlyQuests() {
  return useQuery({
    queryKey: questKeys.byPeriodicity('monthly'),
    queryFn: questsApi.listMonthlyQuests,
  });
}

export function useUniqueQuests() {
  return useQuery({
    queryKey: questKeys.byPeriodicity('unique'),
    queryFn: questsApi.listUniqueQuests,
  });
}

// --- Mutations ---

// Claiming a reward grants XP, so we invalidate the quest tree plus the profile
// (header level badge) and the leveling (XP journal) trees.
export function useClaimQuest() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (progressionId: string) => questsApi.claimQuest(progressionId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: questKeys.all });
      qc.invalidateQueries({ queryKey: profileKeys.all });
      qc.invalidateQueries({ queryKey: levelingKeys.all });
    },
  });
}
