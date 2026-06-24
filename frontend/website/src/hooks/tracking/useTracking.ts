import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as trackingApi from '@/api/endpoints/tracking';
import type { SleepInput } from '@/api/endpoints/tracking';
import { profileKeys } from '@/hooks/profile/keys';
import { trackingKeys } from './keys';

// --- Queries ---

export function useTodayHydration() {
  return useQuery({
    queryKey: trackingKeys.hydrationToday(),
    queryFn: trackingApi.getTodayHydration,
  });
}

export function useTodaySteps() {
  return useQuery({
    queryKey: trackingKeys.stepsToday(),
    queryFn: trackingApi.getTodaySteps,
  });
}

export function useStepsRange(from: string, to: string) {
  return useQuery({
    queryKey: trackingKeys.stepsRange(from, to),
    queryFn: () => trackingApi.listSteps(from, to),
  });
}

export function useSleepRange(from: string, to: string) {
  return useQuery({
    queryKey: trackingKeys.sleepRange(from, to),
    queryFn: () => trackingApi.listSleep(from, to),
  });
}

export function useWeightRange(from: string, to: string) {
  return useQuery({
    queryKey: trackingKeys.weightRange(from, to),
    queryFn: () => trackingApi.listWeight(from, to),
  });
}

// --- Mutations (each invalidates the whole tracking tree on success) ---

function useTrackingMutation<TArgs, TResult>(fn: (args: TArgs) => Promise<TResult>) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: fn,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: trackingKeys.all });
    },
  });
}

// Player-level goals live on the profile (not the tracking tree), so these refresh both.
function usePlayerTargetMutation<TArgs, TResult>(fn: (args: TArgs) => Promise<TResult>) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: fn,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: profileKeys.all });
      qc.invalidateQueries({ queryKey: trackingKeys.all });
    },
  });
}

export function useUpsertSteps() {
  return useTrackingMutation(({ date, count }: { date: string; count: number }) =>
    trackingApi.upsertSteps(date, count),
  );
}

export function useUpdateStepsTodayTarget() {
  return useTrackingMutation((target: number) => trackingApi.updateStepsTodayTarget(target));
}

export function useUpdateHydrationTodayTarget() {
  return useTrackingMutation((targetMl: number) => trackingApi.updateHydrationTodayTarget(targetMl));
}

export function useAddHydrationEntry() {
  return useTrackingMutation(({ loggedAt, valueMl }: { loggedAt: string; valueMl: number }) =>
    trackingApi.addHydrationEntry(loggedAt, valueMl),
  );
}

export function useUpdateHydrationEntry() {
  return useTrackingMutation(({ id, valueMl }: { id: string; valueMl: number }) =>
    trackingApi.updateHydrationEntry(id, valueMl),
  );
}

export function useDeleteHydrationEntry() {
  return useTrackingMutation((id: string) => trackingApi.deleteHydrationEntry(id));
}

export function useUpdatePlayerSleepTarget() {
  return usePlayerTargetMutation((targetMinutes: number) =>
    trackingApi.updatePlayerSleepTarget(targetMinutes),
  );
}

export function useUpdatePlayerWeightTarget() {
  return usePlayerTargetMutation((targetGrams: number) =>
    trackingApi.updatePlayerWeightTarget(targetGrams),
  );
}

export function useLogSleep() {
  return useTrackingMutation((input: SleepInput) => trackingApi.logSleep(input));
}

export function useUpdateSleep() {
  return useTrackingMutation(({ id, input }: { id: string; input: SleepInput }) =>
    trackingApi.updateSleep(id, input),
  );
}

export function useLogWeight() {
  return useTrackingMutation(({ loggedAt, valueGrams }: { loggedAt: string; valueGrams: number }) =>
    trackingApi.logWeight(loggedAt, valueGrams),
  );
}

export function useUpdateWeight() {
  return useTrackingMutation(
    ({ id, loggedAt, valueGrams }: { id: string; loggedAt: string; valueGrams: number }) =>
      trackingApi.updateWeight(id, loggedAt, valueGrams),
  );
}
