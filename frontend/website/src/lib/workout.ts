import type { ExerciseMovementDataOutput, ExerciseSetDataOutput } from '../api/types';

/**
 * One-line summary of an exercise set's current values: shows the achieved* values once the
 * set is complete, otherwise the planned* targets. The movement's tracking flags drive which
 * dimensions are listed (reps / weight / duration / distance / incline%/inclineMeters).
 *
 * Reused by every set-level surface (live editor row, planned editor row, read-only view).
 */
export function summarizeSet(
  set: ExerciseSetDataOutput,
  movement: ExerciseMovementDataOutput,
): string {
  const useAchieved = set.isComplete;
  const parts: string[] = [];
  const push = (planned: string | number | null, achieved: string | number | null, unit: string) => {
    const value = useAchieved ? achieved : planned;
    if (value === null) return;
    parts.push(`${value}${unit}`);
  };
  if (movement.tracksRepetitions) push(set.plannedReps, set.achievedReps, ' reps');
  if (movement.tracksWeight) push(set.plannedWeight, set.achievedWeight, ' kg');
  if (movement.tracksDuration) push(set.plannedDurationSeconds, set.achievedDurationSeconds, ' s');
  if (movement.tracksDistance) push(set.plannedDistanceMeters, set.achievedDistanceMeters, ' m');
  if (movement.tracksInclinePercent) push(set.plannedInclinePercent, set.achievedInclinePercent, ' %');
  if (movement.tracksInclineMeters) push(set.plannedInclineMeters, set.achievedInclineMeters, ' m+');
  return parts.length > 0 ? parts.join(' · ') : 'no values yet';
}
