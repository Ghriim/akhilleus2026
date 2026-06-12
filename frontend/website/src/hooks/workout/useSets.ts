import { useMutation, useQueryClient } from '@tanstack/react-query';
import * as setsApi from '@/api/endpoints/sets';
import type {
  AddSetInput,
  UpdateAchievedSetInput,
  UpdatePlannedSetInput,
} from '@/api/endpoints/sets';
import { workoutKeys } from './keys';

export function useAddSet(workoutId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ exerciseId, input }: { exerciseId: string; input: AddSetInput }) =>
      setsApi.add(exerciseId, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.details(workoutId) });
    },
  });
}

export function useUpdatePlannedSet(workoutId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, input }: { id: string; input: UpdatePlannedSetInput }) =>
      setsApi.updatePlanned(id, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.details(workoutId) });
    },
  });
}

export function useUpdateAchievedSet(workoutId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, input }: { id: string; input: UpdateAchievedSetInput }) =>
      setsApi.updateAchieved(id, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.details(workoutId) });
    },
  });
}

export function useRemoveSet(workoutId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => setsApi.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.details(workoutId) });
    },
  });
}
