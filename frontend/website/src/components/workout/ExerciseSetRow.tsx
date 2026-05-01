import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type {
  ExerciseMovementDataOutput,
  ExerciseSetDataOutput,
  RemoveExerciseSetDataOutput,
} from '../../api/types';
import { AchievedForm } from './AchievedForm';

interface Props {
  set: ExerciseSetDataOutput;
  movement: ExerciseMovementDataOutput;
  workoutId: string;
}

export function ExerciseSetRow({ set, movement, workoutId }: Props) {
  const { token } = useAuth();
  const queryClient = useQueryClient();
  const [editing, setEditing] = useState(false);

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: ['workout', workoutId] });
  };

  const markCompleted = useMutation({
    mutationFn: () =>
      apiRequest<ExerciseSetDataOutput>(`/api/player/sets/${set.id}/complete`, {
        method: 'POST',
        token,
      }),
    onSuccess: invalidate,
  });

  const remove = useMutation({
    mutationFn: () =>
      apiRequest<RemoveExerciseSetDataOutput>(`/api/player/sets/${set.id}`, {
        method: 'DELETE',
        token,
      }),
    onSuccess: invalidate,
  });

  return (
    <div
      style={{
        padding: 'var(--space-2) var(--space-3)',
        borderTop: '1px solid var(--color-border)',
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 'var(--space-2)' }}>
        <div>
          <span style={{ fontWeight: 600 }}>Set {set.position + 1}</span>
          {set.completed && (
            <span style={{ marginLeft: 'var(--space-2)', color: 'var(--color-success)', fontSize: '0.85em' }}>
              ✓ completed
            </span>
          )}
          <div className="muted" style={{ fontSize: '0.85em', marginTop: 2 }}>
            {summary(set, movement)}
          </div>
        </div>
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          {!editing && (
            <button type="button" onClick={() => setEditing(true)}>
              Update achieved
            </button>
          )}
          {!set.completed && (
            <button
              type="button"
              className="primary"
              disabled={markCompleted.isPending}
              onClick={() => markCompleted.mutate()}
            >
              {markCompleted.isPending ? '…' : 'Mark complete'}
            </button>
          )}
          <button
            type="button"
            className="danger"
            disabled={remove.isPending}
            onClick={() => {
              if (window.confirm('Delete this set?')) remove.mutate();
            }}
          >
            ×
          </button>
        </div>
      </div>
      {editing && (
        <div style={{ marginTop: 'var(--space-2)' }}>
          <AchievedForm set={set} movement={movement} onDone={() => setEditing(false)} />
        </div>
      )}
    </div>
  );
}

function summary(set: ExerciseSetDataOutput, movement: ExerciseMovementDataOutput): string {
  const parts: string[] = [];
  const fmt = (planned: string | number | null, achieved: string | number | null, unit: string) => {
    if (planned === null && achieved === null) return null;
    if (achieved !== null) return `${achieved}${unit}` + (planned !== null && planned !== achieved ? ` (planned ${planned}${unit})` : '');
    return `planned ${planned}${unit}`;
  };
  if (movement.tracksRepetitions) {
    const v = fmt(set.plannedReps, set.achievedReps, ' reps');
    if (v) parts.push(v);
  }
  if (movement.tracksWeight) {
    const v = fmt(set.plannedWeight, set.achievedWeight, ' kg');
    if (v) parts.push(v);
  }
  if (movement.tracksDuration) {
    const v = fmt(set.plannedDurationSeconds, set.achievedDurationSeconds, ' s');
    if (v) parts.push(v);
  }
  if (movement.tracksDistance) {
    const v = fmt(set.plannedDistanceMeters, set.achievedDistanceMeters, ' m');
    if (v) parts.push(v);
  }
  return parts.length > 0 ? parts.join(' · ') : 'no values yet';
}
