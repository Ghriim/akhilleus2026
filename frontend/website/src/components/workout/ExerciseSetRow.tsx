import { useEffect, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type {
  ExerciseMovementDataOutput,
  ExerciseSetDataOutput,
  RemoveExerciseSetDataOutput,
} from '../../api/types';
import { summarizeSet } from '../../lib/workout';
import { PencilIcon, TrashIcon } from '../icons';
import { SetValuesForm } from './SetValuesForm';

interface Props {
  set: ExerciseSetDataOutput;
  movement: ExerciseMovementDataOutput;
  workoutId: string;
  /** PLANNED workouts edit planned*; IN_PROGRESS workouts edit achieved*. */
  mode: 'planned' | 'achieved';
  /**
   * In achieved mode, marks the row as the workout's "current" set (= the first
   * non-complete set in document order). Auto-opens the achieved-values form on this row,
   * and auto-closes it once isComplete becomes true (the next current row picks up).
   */
  isCurrent?: boolean;
}

export function ExerciseSetRow({ set, movement, workoutId, mode, isCurrent = false }: Props) {
  const { token } = useAuth();
  const queryClient = useQueryClient();
  const [editing, setEditing] = useState(false);

  useEffect(() => {
    if (mode !== 'achieved') return;
    if (set.isComplete) {
      setEditing(false);
      return;
    }
    if (isCurrent) {
      setEditing(true);
    }
  }, [mode, isCurrent, set.isComplete]);

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: ['workout', workoutId] });
  };

  const remove = useMutation({
    mutationFn: () =>
      apiRequest<RemoveExerciseSetDataOutput>(`/api/player/sets/${set.id}`, {
        method: 'DELETE',
        token,
      }),
    onSuccess: invalidate,
  });

  const editLabel = mode === 'planned' ? 'Update planned values' : 'Update achieved values';

  return (
    <div
      style={{
        padding: 'var(--space-2) var(--space-3)',
        borderTop: '1px solid var(--color-border)',
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 'var(--space-2)' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--space-2)' }}>
          <span style={{ fontWeight: 600 }}>{summarizeSet(set, movement)}</span>
          {set.isComplete && (
            <span style={{ color: 'var(--color-success)', fontSize: '0.85em' }}>
              ✓ completed
            </span>
          )}
        </div>
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          {!editing && (
            <button
              type="button"
              className="icon-button"
              onClick={() => setEditing(true)}
              aria-label={editLabel}
              title={editLabel}
            >
              <PencilIcon />
            </button>
          )}
          <button
            type="button"
            className="icon-button icon-button--danger"
            disabled={remove.isPending}
            aria-label="Delete this set"
            title="Delete set"
            onClick={() => {
              if (window.confirm('Delete this set?')) remove.mutate();
            }}
          >
            <TrashIcon />
          </button>
        </div>
      </div>
      {editing && (
        <div style={{ marginTop: 'var(--space-2)' }}>
          <SetValuesForm set={set} movement={movement} workoutId={workoutId} mode={mode} onDone={() => setEditing(false)} />
        </div>
      )}
    </div>
  );
}

