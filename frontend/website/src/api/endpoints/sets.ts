import { apiRequest } from '../client';
import type { ExerciseSetDataOutput } from '../types';

export interface AddSetInput {
  plannedReps?: number | null;
  plannedWeight?: string | null;
  plannedDurationSeconds?: number | null;
  plannedDistanceMeters?: string | null;
  plannedInclinePercent?: string | null;
  plannedInclineMeters?: string | null;
  achievedReps?: number | null;
  achievedWeight?: string | null;
  achievedDurationSeconds?: number | null;
  achievedDistanceMeters?: string | null;
  achievedInclinePercent?: string | null;
  achievedInclineMeters?: string | null;
}

export interface UpdatePlannedSetInput {
  plannedReps?: number | null;
  plannedWeight?: string | null;
  plannedDurationSeconds?: number | null;
  plannedDistanceMeters?: string | null;
  plannedInclinePercent?: string | null;
  plannedInclineMeters?: string | null;
}

export interface UpdateAchievedSetInput {
  achievedReps?: number | null;
  achievedWeight?: string | null;
  achievedDurationSeconds?: number | null;
  achievedDistanceMeters?: string | null;
  achievedInclinePercent?: string | null;
  achievedInclineMeters?: string | null;
}

export function add(exerciseId: string, input: AddSetInput): Promise<ExerciseSetDataOutput> {
  return apiRequest<ExerciseSetDataOutput>(`/api/player/exercises/${exerciseId}/sets`, {
    method: 'POST',
    body: input,
  });
}

export function updatePlanned(id: string, input: UpdatePlannedSetInput): Promise<ExerciseSetDataOutput> {
  return apiRequest<ExerciseSetDataOutput>(`/api/player/sets/${id}/planned`, {
    method: 'PUT',
    body: input,
  });
}

export function updateAchieved(id: string, input: UpdateAchievedSetInput): Promise<ExerciseSetDataOutput> {
  return apiRequest<ExerciseSetDataOutput>(`/api/player/sets/${id}/achieved`, {
    method: 'PUT',
    body: input,
  });
}

export function remove(id: string): Promise<void> {
  return apiRequest<void>(`/api/player/sets/${id}`, { method: 'DELETE' });
}
