export type QuestPeriodicity = 'daily' | 'weekly' | 'monthly' | 'unique';

export const questKeys = {
  all: ['quests'] as const,
  byPeriodicity: (periodicity: QuestPeriodicity) => [...questKeys.all, periodicity] as const,
};
