import { apiRequest } from '../client';
import type {
  HydrationDayDataOutput,
  PlayerHydrationTargetDataOutput,
  PlayerSleepTargetDataOutput,
  PlayerStepsTargetDataOutput,
  PlayerWeightTargetDataOutput,
  SleepDailyEntryDataOutput,
  StepsDailyEntryDataOutput,
  WeightEntryDataOutput,
} from '../types';

// --- Steps ---

export function getTodaySteps(): Promise<StepsDailyEntryDataOutput> {
  return apiRequest<StepsDailyEntryDataOutput>('/api/player/tracking/steps/today');
}

export function upsertSteps(date: string, count: number): Promise<StepsDailyEntryDataOutput> {
  return apiRequest<StepsDailyEntryDataOutput>(`/api/player/tracking/steps/${date}`, {
    method: 'PUT',
    body: { count },
  });
}

export function updateStepsTodayTarget(target: number): Promise<StepsDailyEntryDataOutput> {
  return apiRequest<StepsDailyEntryDataOutput>('/api/player/tracking/steps/today/target', {
    method: 'PUT',
    body: { target },
  });
}

export function updatePlayerStepsTarget(target: number): Promise<PlayerStepsTargetDataOutput> {
  return apiRequest<PlayerStepsTargetDataOutput>('/api/player/tracking/steps/target', {
    method: 'PUT',
    body: { target },
  });
}

export function deleteSteps(date: string): Promise<void> {
  return apiRequest<void>(`/api/player/tracking/steps/${date}`, {
    method: 'DELETE',
  });
}

export function listSteps(from: string, to: string): Promise<StepsDailyEntryDataOutput[]> {
  return apiRequest<StepsDailyEntryDataOutput[]>('/api/player/tracking/steps', {
    query: { from, to },
  });
}

// --- Hydration ---

export function getTodayHydration(): Promise<HydrationDayDataOutput> {
  return apiRequest<HydrationDayDataOutput>('/api/player/tracking/hydration/today');
}

export function updateHydrationTodayTarget(targetMl: number): Promise<HydrationDayDataOutput> {
  return apiRequest<HydrationDayDataOutput>('/api/player/tracking/hydration/today/target', {
    method: 'PUT',
    body: { targetMl },
  });
}

export function updatePlayerHydrationTarget(
  targetMl: number,
): Promise<PlayerHydrationTargetDataOutput> {
  return apiRequest<PlayerHydrationTargetDataOutput>('/api/player/tracking/hydration/target', {
    method: 'PUT',
    body: { targetMl },
  });
}

export function addHydrationEntry(loggedAt: string, valueMl: number): Promise<HydrationDayDataOutput> {
  return apiRequest<HydrationDayDataOutput>('/api/player/tracking/hydration/entries', {
    method: 'POST',
    body: { loggedAt, valueMl },
  });
}

export function updateHydrationEntry(id: string, valueMl: number): Promise<HydrationDayDataOutput> {
  return apiRequest<HydrationDayDataOutput>(`/api/player/tracking/hydration/entries/${id}`, {
    method: 'PUT',
    body: { valueMl },
  });
}

export function deleteHydrationEntry(id: string): Promise<HydrationDayDataOutput> {
  return apiRequest<HydrationDayDataOutput>(`/api/player/tracking/hydration/entries/${id}`, {
    method: 'DELETE',
  });
}

// --- Sleep ---

export interface SleepInput {
  bedAt: string;
  wakeAt: string;
  quality?: number | null;
}

export function logSleep(input: SleepInput): Promise<SleepDailyEntryDataOutput> {
  return apiRequest<SleepDailyEntryDataOutput>('/api/player/tracking/sleep', {
    method: 'POST',
    body: input,
  });
}

export function updateSleep(id: string, input: SleepInput): Promise<SleepDailyEntryDataOutput> {
  return apiRequest<SleepDailyEntryDataOutput>(`/api/player/tracking/sleep/${id}`, {
    method: 'PUT',
    body: input,
  });
}

export function updatePlayerSleepTarget(
  targetMinutes: number,
): Promise<PlayerSleepTargetDataOutput> {
  return apiRequest<PlayerSleepTargetDataOutput>('/api/player/tracking/sleep/target', {
    method: 'PUT',
    body: { targetMinutes },
  });
}

export function deleteSleep(id: string): Promise<void> {
  return apiRequest<void>(`/api/player/tracking/sleep/${id}`, { method: 'DELETE' });
}

export function listSleep(from: string, to: string): Promise<SleepDailyEntryDataOutput[]> {
  return apiRequest<SleepDailyEntryDataOutput[]>('/api/player/tracking/sleep', {
    query: { from, to },
  });
}

// --- Weight ---

export function logWeight(loggedAt: string, valueGrams: number): Promise<WeightEntryDataOutput> {
  return apiRequest<WeightEntryDataOutput>('/api/player/tracking/weight', {
    method: 'POST',
    body: { loggedAt, valueGrams },
  });
}

export function updateWeight(
  id: string,
  loggedAt: string,
  valueGrams: number,
): Promise<WeightEntryDataOutput> {
  return apiRequest<WeightEntryDataOutput>(`/api/player/tracking/weight/${id}`, {
    method: 'PUT',
    body: { loggedAt, valueGrams },
  });
}

export function updatePlayerWeightTarget(
  targetGrams: number,
): Promise<PlayerWeightTargetDataOutput> {
  return apiRequest<PlayerWeightTargetDataOutput>('/api/player/tracking/weight/target', {
    method: 'PUT',
    body: { targetGrams },
  });
}

export function deleteWeight(id: string): Promise<void> {
  return apiRequest<void>(`/api/player/tracking/weight/${id}`, {
    method: 'DELETE',
  });
}

export function listWeight(from: string, to: string): Promise<WeightEntryDataOutput[]> {
  return apiRequest<WeightEntryDataOutput[]>('/api/player/tracking/weight', {
    query: { from, to },
  });
}
