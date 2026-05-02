import { useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { apiRequest, HttpError } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type {
  FinishWorkoutDataOutput,
  WorkoutDataOutput,
  WorkoutDetailsDataOutput,
} from '../../api/types';
import { formatRelative } from '../../lib/format';
import { ExerciseEditor } from './ExerciseEditor';
import { AddMovementForm } from './AddMovementForm';
import { FinishWorkoutModal } from './FinishWorkoutModal';

interface Props {
  workout: WorkoutDetailsDataOutput;
}

export function LiveWorkoutEditor({ workout }: Props) {
  const { token } = useAuth();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [finishResult, setFinishResult] = useState<FinishWorkoutDataOutput | null>(null);
  const [incompleteSetIds, setIncompleteSetIds] = useState<string[] | null>(null);
  const [finishError, setFinishError] = useState<string | null>(null);
  const [modalOpen, setModalOpen] = useState(false);

  // The "current" set is the first non-complete set in document order across all exercises.
  // Its row auto-opens its achieved-values editor; once isComplete is true (auto-derived from
  // the achieved* values matching the movement's tracking flags), the next one picks up.
  const currentSetId = useMemo(() => {
    for (const exercise of workout.exercises) {
      for (const set of exercise.sets) {
        if (!set.isComplete) return set.id;
      }
    }
    return null;
  }, [workout.exercises]);

  const finish = useMutation({
    mutationFn: () =>
      apiRequest<FinishWorkoutDataOutput>(`/api/player/workouts/${workout.id}/finish`, {
        method: 'POST',
        token,
      }),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['workout', workout.id] });
      void queryClient.invalidateQueries({ queryKey: ['workouts'] });
      setFinishResult(result);
      setIncompleteSetIds(null);
      setFinishError(null);
      setModalOpen(true);
    },
    onError: (err) => {
      setFinishResult(null);
      if (err instanceof HttpError && err.errorCode() === 'WORKOUT_HAS_INCOMPLETE_SETS') {
        const ids = err.violations()['exerciseSets'] ?? [];
        setIncompleteSetIds(ids);
        setFinishError(null);
      } else {
        setIncompleteSetIds(null);
        setFinishError(err instanceof Error ? err.message : 'Unable to finish workout.');
      }
      setModalOpen(true);
    },
  });

  const cancel = useMutation({
    mutationFn: () =>
      apiRequest<WorkoutDataOutput>(`/api/player/workouts/${workout.id}/cancel`, {
        method: 'POST',
        token,
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['workouts'] });
      navigate('/');
    },
  });

  const closeModal = () => {
    setModalOpen(false);
    if (finishResult) {
      // Workout is now COMPLETED — leave the live editor.
      navigate('/history');
    }
  };

  return (
    <>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <h1 style={{ marginTop: 0 }}>Workout in progress</h1>
        <span className="muted">Started {formatRelative(workout.dateStart)}</span>
      </div>

      {workout.exercises.length === 0 && (
        <p className="muted">No movements yet. Add one to start logging sets.</p>
      )}
      {workout.exercises.map((exercise) => (
        <ExerciseEditor
          key={exercise.id}
          exercise={exercise}
          workoutId={workout.id}
          mode="achieved"
          currentSetId={currentSetId}
        />
      ))}

      <AddMovementForm workoutId={workout.id} />

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
            if (window.confirm('Cancel this workout? Achievements will not be saved.')) {
              cancel.mutate();
            }
          }}
        >
          {cancel.isPending ? 'Canceling…' : 'Cancel'}
        </button>
        <button
          type="button"
          className="primary"
          disabled={finish.isPending}
          onClick={() => finish.mutate()}
        >
          {finish.isPending ? 'Finishing…' : 'Finish workout'}
        </button>
      </div>

      <FinishWorkoutModal
        open={modalOpen}
        onClose={closeModal}
        result={finishResult}
        incompleteSetIds={incompleteSetIds}
        errorMessage={finishError}
      />
    </>
  );
}
