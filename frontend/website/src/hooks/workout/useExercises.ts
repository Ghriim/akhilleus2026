import { useMutation, useQueryClient } from '@tanstack/react-query';
import * as exercisesApi from '@/api/endpoints/exercises';
import type {
  AddExerciseInput,
  ReorderExercisesInput,
  UpdateRestInput,
} from '@/api/endpoints/exercises';
import { workoutKeys } from './keys';

export function useAddExercise(workoutId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: AddExerciseInput) => exercisesApi.add(workoutId, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.details(workoutId) });
    },
  });
}

export function useRemoveExercise(workoutId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => exercisesApi.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.details(workoutId) });
    },
  });
}

export function useReorderExercises(workoutId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: ReorderExercisesInput) => exercisesApi.reorder(workoutId, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.details(workoutId) });
    },
  });
}

export function useUpdateExerciseRest(workoutId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, input }: { id: string; input: UpdateRestInput }) =>
      exercisesApi.updateRest(id, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.details(workoutId) });
    },
  });
}
