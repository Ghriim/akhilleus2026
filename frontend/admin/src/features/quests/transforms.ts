import dayjs from 'dayjs';
import type { Quest, QuestFormValues, QuestPayload } from './types';

/**
 * Form → API. `metric`/`targetValue` are forced to null for MANUAL quests (the backend rejects them
 * otherwise); `targetValue` becomes the decimal string the backend expects; dates become ISO strings.
 */
export const toQuestPayload = (values: QuestFormValues): QuestPayload => {
  const isAutomatic = values.kind === 'AUTOMATIC';

  return {
    label: values.label,
    kind: values.kind,
    metric: isAutomatic ? values.metric : null,
    periodicity: values.periodicity,
    targetValue:
      isAutomatic && values.targetValue !== null && values.targetValue !== undefined
        ? String(values.targetValue)
        : null,
    rewardedXp: values.rewardedXp,
    dateStart: values.dateStart.toISOString(),
    // antd leaves an untouched optional DatePicker as `undefined`, not `null`.
    dateEnd: values.dateEnd != null ? values.dateEnd.toISOString() : null,
  };
};

/** API → form (for the edit page initial values). */
export const toQuestFormValues = (quest: Quest): QuestFormValues => ({
  label: quest.label,
  kind: quest.kind,
  metric: quest.metric,
  periodicity: quest.periodicity,
  targetValue: quest.targetValue !== null ? Number(quest.targetValue) : null,
  rewardedXp: quest.rewardedXp,
  dateStart: dayjs(quest.dateStart),
  dateEnd: quest.dateEnd !== null ? dayjs(quest.dateEnd) : null,
});
