import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { apiRequest, HttpError } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type { WorkoutDataOutput, WorkoutDetailsDataOutput } from '../../api/types';
import { formatDateTime } from '../../lib/format';
import { ExerciseEditor } from './ExerciseEditor';
import { AddMovementForm } from './AddMovementForm';

interface Props {
  workout: WorkoutDetailsDataOutput;
}

export function PlannedWorkoutView({ workout }: Props) {
  const { token } = useAuth();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const start = useMutation({
    mutationFn: () =>
      apiRequest<WorkoutDataOutput>(`/api/player/workouts/${workout.id}/start`, {
        method: 'POST',
        token,
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['workout', workout.id] });
      void queryClient.invalidateQueries({ queryKey: ['workouts'] });
    },
  });

  const cancel = useMutation({
    mutationFn: () =>
      apiRequest<WorkoutDataOutput>(`/api/player/workouts/${workout.id}/cancel`, {
        method: 'POST',
        token,
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['workout', workout.id] });
      void queryClient.invalidateQueries({ queryKey: ['workouts'] });
      navigate('/');
    },
  });

  const startError =
    start.error instanceof HttpError && start.error.errorCode() === 'WORKOUT_ALREADY_IN_PROGRESS'
      ? 'Another workout is already in progress. Finish or cancel it first.'
      : start.error instanceof Error
        ? start.error.message
        : null;

  return (
    <>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <h1 style={{ marginTop: 0 }}>Planned workout</h1>
        <span className="muted">Scheduled for {formatDateTime(workout.plannedAt)}</span>
      </div>

      {workout.exercises.length === 0 && (
        <p className="muted">No movements yet. Add one to start planning sets.</p>
      )}
      {workout.exercises.map((exercise) => (
        <ExerciseEditor key={exercise.id} exercise={exercise} workoutId={workout.id} mode="planned" />
      ))}

      <AddMovementForm workoutId={workout.id} />

      {startError && (
        <p className="error" style={{ color: 'var(--color-danger)', marginTop: 'var(--space-2)' }}>
          {startError}
        </p>
      )}

      <div
        style={{
          display: 'flex',
          gap: 'var(--space-2)',
          marginTop: 'var(--space-4)',
          justifyContent: 'flex-end',
        }}
      >
        <button
          type="button"
          className="danger"
          disabled={cancel.isPending}
          onClick={() => {
            if (window.confirm('Cancel this planned workout?')) cancel.mutate();
          }}
        >
          {cancel.isPending ? 'Canceling…' : 'Cancel'}
        </button>
        <button
          type="button"
          className="primary"
          disabled={start.isPending}
          onClick={() => start.mutate()}
        >
          {start.isPending ? 'Starting…' : 'Start now'}
        </button>
      </div>
    </>
  );
}
