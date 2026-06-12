import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as workoutsApi from '@/api/endpoints/workouts';
import type {
  PlanWorkoutInput,
  StartEmptyWorkoutInput,
} from '@/api/endpoints/workouts';
import { workoutKeys } from './keys';

export function useUpcomingWorkouts() {
  return useQuery({
    queryKey: workoutKeys.upcoming(),
    queryFn: workoutsApi.listUpcoming,
  });
}

export function useWorkoutHistory(page: number, perPage: number = 20) {
  return useQuery({
    queryKey: workoutKeys.history(page, perPage),
    queryFn: () => workoutsApi.listHistory(page, perPage),
  });
}

export function useMonthWorkouts(year: number, month: number) {
  return useQuery({
    queryKey: workoutKeys.calendar(year, month),
    queryFn: () => workoutsApi.listByMonth(year, month),
  });
}

export function useWorkoutDetails(id: string | undefined) {
  return useQuery({
    queryKey: workoutKeys.details(id ?? '__missing__'),
    queryFn: () => workoutsApi.getDetails(id as string),
    enabled: !!id,
  });
}

export function useStartEmptyWorkout() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: StartEmptyWorkoutInput = {}) => workoutsApi.startEmpty(input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.all });
    },
  });
}

export function usePlanWorkout() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: PlanWorkoutInput) => workoutsApi.plan(input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: workoutKeys.all });
    },
  });
}

export function useStartPlannedWorkout() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => workoutsApi.startPlanned(id),
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: workoutKeys.all });
      qc.invalidateQueries({ queryKey: workoutKeys.details(id) });
    },
  });
}

export function useFinishWorkout() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => workoutsApi.finish(id),
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: workoutKeys.all });
      qc.invalidateQueries({ queryKey: workoutKeys.details(id) });
    },
  });
}

export function useCancelWorkout() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => workoutsApi.cancel(id),
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: workoutKeys.all });
      qc.invalidateQueries({ queryKey: workoutKeys.details(id) });
    },
  });
}
