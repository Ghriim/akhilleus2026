import { apiRequest } from '../client';
import type { ClaimQuestRewardDataOutput, QuestProgressionDataOutput } from '../types';

export function listDailyQuests(): Promise<QuestProgressionDataOutput[]> {
  return apiRequest<QuestProgressionDataOutput[]>('/api/player/quests/daily');
}

export function listWeeklyQuests(): Promise<QuestProgressionDataOutput[]> {
  return apiRequest<QuestProgressionDataOutput[]>('/api/player/quests/weekly');
}

export function listMonthlyQuests(): Promise<QuestProgressionDataOutput[]> {
  return apiRequest<QuestProgressionDataOutput[]>('/api/player/quests/monthly');
}

export function listUniqueQuests(): Promise<QuestProgressionDataOutput[]> {
  return apiRequest<QuestProgressionDataOutput[]>('/api/player/quests/unique');
}

export function claimQuest(progressionId: string): Promise<ClaimQuestRewardDataOutput> {
  return apiRequest<ClaimQuestRewardDataOutput>(`/api/player/quests/${progressionId}/claim`, {
    method: 'POST',
  });
}
