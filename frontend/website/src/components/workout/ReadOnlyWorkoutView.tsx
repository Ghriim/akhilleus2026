import type { WorkoutDetailsDataOutput, WorkoutStatus } from '../../api/types';
import { formatDateTime } from '../../lib/format';

interface Props {
  workout: WorkoutDetailsDataOutput;
}

const STATUS_LABEL: Record<WorkoutStatus, string> = {
  PLANNED: 'Planned',
  IN_PROGRESS: 'In progress',
  COMPLETED: 'Completed',
  CANCELED: 'Canceled',
};

export function ReadOnlyWorkoutView({ workout }: Props) {
  return (
    <>
      <h1 style={{ marginTop: 0 }}>{STATUS_LABEL[workout.status]} workout</h1>
      <div className="card">
        {workout.dateStart && (
          <p style={{ marginTop: 0 }}>
            Started: <strong>{formatDateTime(workout.dateStart)}</strong>
          </p>
        )}
        {workout.dateEnd && (
          <p>
            Finished: <strong>{formatDateTime(workout.dateEnd)}</strong>
          </p>
        )}
        {workout.plannedAt && !workout.dateStart && (
          <p>
            Planned for: <strong>{formatDateTime(workout.plannedAt)}</strong>
          </p>
        )}
      </div>
      {workout.exercises.length === 0 && <p className="muted">No movements were logged.</p>}
      {workout.exercises.map((exercise) => (
        <div key={exercise.id} className="card" style={{ padding: 0, overflow: 'hidden' }}>
          <div style={{ padding: 'var(--space-3)' }}>
            <strong>{exercise.movement.label}</strong>
            <div className="muted" style={{ fontSize: '0.85em' }}>
              {exercise.sets.length} set{exercise.sets.length === 1 ? '' : 's'}
            </div>
          </div>
          {exercise.sets.map((set) => (
            <div
              key={set.id}
              style={{
                padding: 'var(--space-2) var(--space-3)',
                borderTop: '1px solid var(--color-border)',
                fontSize: '0.9em',
              }}
            >
              <span style={{ fontWeight: 600 }}>Set {set.position + 1}</span>
              {set.isComplete && (
                <span style={{ marginLeft: 'var(--space-2)', color: 'var(--color-success)' }}>
                  ✓
                </span>
              )}
              <div className="muted" style={{ fontSize: '0.9em', marginTop: 2 }}>
                {summarize(set, exercise.movement.tracksRepetitions, exercise.movement.tracksWeight, exercise.movement.tracksDuration, exercise.movement.tracksDistance)}
              </div>
            </div>
          ))}
        </div>
      ))}
    </>
  );
}

function summarize(
  set: { plannedReps: number | null; achievedReps: number | null; plannedWeight: string | null; achievedWeight: string | null; plannedDurationSeconds: number | null; achievedDurationSeconds: number | null; plannedDistanceMeters: string | null; achievedDistanceMeters: string | null },
  reps: boolean,
  weight: boolean,
  duration: boolean,
  distance: boolean,
): string {
  const parts: string[] = [];
  const pair = (planned: string | number | null, achieved: string | number | null, unit: string) => {
    if (planned === null && achieved === null) return null;
    if (achieved !== null) {
      return `${achieved}${unit}` + (planned !== null && planned !== achieved ? ` (planned ${planned}${unit})` : '');
    }
    return `planned ${planned}${unit} (no result)`;
  };
  if (reps) {
    const v = pair(set.plannedReps, set.achievedReps, ' reps');
    if (v) parts.push(v);
  }
  if (weight) {
    const v = pair(set.plannedWeight, set.achievedWeight, ' kg');
    if (v) parts.push(v);
  }
  if (duration) {
    const v = pair(set.plannedDurationSeconds, set.achievedDurationSeconds, ' s');
    if (v) parts.push(v);
  }
  if (distance) {
    const v = pair(set.plannedDistanceMeters, set.achievedDistanceMeters, ' m');
    if (v) parts.push(v);
  }
  return parts.length > 0 ? parts.join(' · ') : '—';
}
