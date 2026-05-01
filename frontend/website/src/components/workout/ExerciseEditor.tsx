import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type { ExerciseDetailsDataOutput, RemoveExerciseDataOutput } from '../../api/types';
import { ExerciseSetRow } from './ExerciseSetRow';
import { AddSetForm } from './AddSetForm';

interface Props {
  exercise: ExerciseDetailsDataOutput;
  workoutId: string;
}

export function ExerciseEditor({ exercise, workoutId }: Props) {
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
      <div
        style={{
          padding: 'var(--space-3)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
        }}
      >
        <div>
          <strong>{exercise.movement.label}</strong>
          <div className="muted" style={{ fontSize: '0.85em' }}>
            Rest: {exercise.restDurationSeconds}s · {exercise.sets.length} set{exercise.sets.length === 1 ? '' : 's'}
          </div>
        </div>
        <button
          type="button"
          className="danger"
          disabled={remove.isPending}
          onClick={() => {
            if (window.confirm(`Remove ${exercise.movement.label} from the workout?`)) {
              remove.mutate();
            }
          }}
        >
          Remove
        </button>
      </div>
      {exercise.sets.map((set) => (
        <ExerciseSetRow key={set.id} set={set} movement={exercise.movement} workoutId={workoutId} />
      ))}
      <AddSetForm exerciseId={exercise.id} movement={exercise.movement} workoutId={workoutId} />
    </div>
  );
}
