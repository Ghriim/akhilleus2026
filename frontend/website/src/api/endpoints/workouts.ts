import { apiRequest } from '../client';
import type {
  DeleteWorkoutDataOutput,
  FinishWorkoutDataOutput,
  WorkoutDataOutput,
  WorkoutDetailsDataOutput,
  WorkoutHistoryDataOutput,
} from '../types';

export interface StartEmptyWorkoutInput {
  name?: string | undefined;
}

export interface PlanWorkoutInput {
  name?: string | undefined;
  plannedAt: string;
}

export function startEmpty(input: StartEmptyWorkoutInput = {}): Promise<WorkoutDataOutput> {
  return apiRequest<WorkoutDataOutput>('/api/player/workouts', { method: 'POST', body: input });
}

export function plan(input: PlanWorkoutInput): Promise<WorkoutDataOutput> {
  return apiRequest<WorkoutDataOutput>('/api/player/workouts/planned', {
    method: 'POST',
    body: input,
  });
}

export function listUpcoming(): Promise<WorkoutDataOutput[]> {
  return apiRequest<WorkoutDataOutput[]>('/api/player/workouts/upcoming');
}

export function listHistory(page: number = 1, perPage: number = 20): Promise<WorkoutHistoryDataOutput> {
  return apiRequest<WorkoutHistoryDataOutput>('/api/player/workouts/history', {
    query: { page, perPage },
  });
}

export function listByMonth(year: number, month: number): Promise<WorkoutDataOutput[]> {
  return apiRequest<WorkoutDataOutput[]>('/api/player/workouts/calendar', {
    query: { year, month },
  });
}

export function getDetails(id: string): Promise<WorkoutDetailsDataOutput> {
  return apiRequest<WorkoutDetailsDataOutput>(`/api/player/workouts/${id}`);
}

export function startPlanned(id: string): Promise<WorkoutDataOutput> {
  return apiRequest<WorkoutDataOutput>(`/api/player/workouts/${id}/start`, { method: 'POST' });
}

export function finish(id: string): Promise<FinishWorkoutDataOutput> {
  return apiRequest<FinishWorkoutDataOutput>(`/api/player/workouts/${id}/finish`, {
    method: 'POST',
  });
}

export function cancel(id: string): Promise<WorkoutDataOutput> {
  return apiRequest<WorkoutDataOutput>(`/api/player/workouts/${id}/cancel`, { method: 'POST' });
}

export function remove(id: string): Promise<DeleteWorkoutDataOutput> {
  return apiRequest<DeleteWorkoutDataOutput>(`/api/player/workouts/${id}`, { method: 'DELETE' });
}
