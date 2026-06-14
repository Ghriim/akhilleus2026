import { apiRequest } from '../client';
import type { EarnedExperienceJournalDataOutput } from '../types';

export function listJournal(
  page: number = 1,
  perPage: number = 20,
): Promise<EarnedExperienceJournalDataOutput> {
  return apiRequest<EarnedExperienceJournalDataOutput>('/api/player/leveling/journal', {
    query: { page, perPage },
  });
}
