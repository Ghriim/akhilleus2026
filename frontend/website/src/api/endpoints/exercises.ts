import { apiRequest } from '../client';
import type { ExerciseDataOutput } from '../types';

export interface AddExerciseInput {
  movementId: string;
  restDurationSeconds?: number | undefined;
}

export interface ReorderExercisesInput {
  exerciseIds: string[];
}

export interface UpdateRestInput {
  restDurationSeconds: number;
}

export function add(workoutId: string, input: AddExerciseInput): Promise<ExerciseDataOutput> {
  return apiRequest<ExerciseDataOutput>(`/api/player/workouts/${workoutId}/exercises`, {
    method: 'POST',
    body: input,
  });
}

export function remove(id: string): Promise<void> {
  return apiRequest<void>(`/api/player/exercises/${id}`, { method: 'DELETE' });
}

export function reorder(workoutId: string, input: ReorderExercisesInput): Promise<void> {
  return apiRequest<void>(`/api/player/workouts/${workoutId}/exercises/reorder`, {
    method: 'POST',
    body: input,
  });
}

export function updateRest(id: string, input: UpdateRestInput): Promise<ExerciseDataOutput> {
  return apiRequest<ExerciseDataOutput>(`/api/player/exercises/${id}/rest-duration`, {
    method: 'PUT',
    body: input,
  });
}
