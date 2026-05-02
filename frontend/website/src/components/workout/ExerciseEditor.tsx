import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type { ExerciseDetailsDataOutput, RemoveExerciseDataOutput } from '../../api/types';
import { TrashIcon } from '../icons';
import { ExerciseSetRow } from './ExerciseSetRow';
import { AddSetForm } from './AddSetForm';

interface Props {
  exercise: ExerciseDetailsDataOutput;
  workoutId: string;
  /** Which value group to write when the user adds a set. */
  mode: 'planned' | 'achieved';
  /**
   * In achieved mode, the workout-wide id of the first non-complete set. The matching row
   * auto-opens its achieved-values editor; ignored in planned mode.
   */
  currentSetId?: string | null;
}

export function ExerciseEditor({ exercise, workoutId, mode, currentSetId = null }: Props) {
  const { token } = useAuth();
  const queryClient = useQueryClient();

  const remove = useMutation({
    mutationFn: () =>
      apiRequest<RemoveExerciseDataOutput>(`/api/player/exercises/${exercise.id}`, {
        method: 'DELETE',
        token,
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['workout', workoutId] });
    },
  });

  return (
    <div className="card" style={{ padding: 0, overflow: 'hidden' }}>
      <div className="exercise-header">
        <div>
          <strong style={{ fontSize: '1.1em' }}>{exercise.movement.label}</strong>
          <div className="muted" style={{ fontSize: '0.85em' }}>
            Rest: {exercise.restDurationSeconds}s
          </div>
        </div>
        <button
          type="button"
          className="icon-button icon-button--danger"
          disabled={remove.isPending}
          aria-label={`Remove ${exercise.movement.label} from the workout`}
          title="Remove movement"
          onClick={() => {
            if (window.confirm(`Remove ${exercise.movement.label} from the workout?`)) {
              remove.mutate();
            }
          }}
        >
          <TrashIcon />
        </button>
      </div>
      {exercise.sets.map((set) => (
        <ExerciseSetRow
          key={set.id}
          set={set}
          movement={exercise.movement}
          workoutId={workoutId}
          mode={mode}
          isCurrent={currentSetId === set.id}
        />
      ))}
      <AddSetForm
        exerciseId={exercise.id}
        movement={exercise.movement}
        workoutId={workoutId}
        mode={mode}
        defaultOpen={exercise.sets.length === 0}
      />
    </div>
  );
}
