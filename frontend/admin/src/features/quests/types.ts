import type { Dayjs } from 'dayjs';

export type QuestKind = 'AUTOMATIC' | 'MANUAL';
export type QuestPeriodicity = 'UNIQUE' | 'DAILY' | 'WEEKLY' | 'MONTHLY';
export type QuestMetric =
  | 'STEPS_DAILY'
  | 'HYDRATION_ML_DAILY'
  | 'SLEEP_DURATION_MINUTES'
  | 'WORKOUT_COUNT'
  | 'WORKOUT_DURATION_MINUTES';

export interface Quest {
  id: string;
  label: string;
  kind: QuestKind;
  metric: QuestMetric | null;
  periodicity: QuestPeriodicity;
  targetValue: string | null;
  rewardedXp: number;
  dateStart: string;
  dateEnd: string | null;
}

/** The JSON body sent to the admin REST endpoints (dates as ISO strings, targetValue as a decimal string). */
export interface QuestPayload {
  label: string;
  kind: QuestKind;
  metric: QuestMetric | null;
  periodicity: QuestPeriodicity;
  targetValue: string | null;
  rewardedXp: number;
  dateStart: string;
  dateEnd: string | null;
}

/** The values held by the antd form (dates as Dayjs, targetValue as a number for InputNumber). */
export interface QuestFormValues {
  label: string;
  kind: QuestKind;
  metric: QuestMetric | null;
  periodicity: QuestPeriodicity;
  targetValue: number | null;
  rewardedXp: number;
  dateStart: Dayjs;
  dateEnd: Dayjs | null;
}
