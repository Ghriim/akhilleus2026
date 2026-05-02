import { useState } from 'react';
import type { FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type { ExerciseMovementDataOutput, ExerciseSetDataOutput } from '../../api/types';
import { CheckIcon, SaveIcon, XMarkIcon } from '../icons';

interface Props {
  exerciseId: string;
  movement: ExerciseMovementDataOutput;
  workoutId: string;
  /**
   * Which value group to write. PLANNED workouts → 'planned', IN_PROGRESS → 'achieved'.
   * The backend rejects the wrong group via STATUS_FIELD_MISMATCH.
   */
  mode: 'planned' | 'achieved';
  /** When true, the form is rendered open on first mount (e.g. for a freshly-added movement). */
  defaultOpen?: boolean;
}

export function AddSetForm({ exerciseId, movement, workoutId, mode, defaultOpen = false }: Props) {
  const { token } = useAuth();
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(defaultOpen);
  const [reps, setReps] = useState('');
  const [weight, setWeight] = useState('');
  const [duration, setDuration] = useState('');
  const [distance, setDistance] = useState('');

  const reset = () => {
    setReps('');
    setWeight('');
    setDuration('');
    setDistance('');
    setOpen(false);
  };

  const mutation = useMutation({
    mutationFn: (body: Record<string, unknown>) =>
      apiRequest<ExerciseSetDataOutput>(`/api/player/exercises/${exerciseId}/sets`, {
        method: 'POST',
        body,
        token,
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['workout', workoutId] });
      reset();
    },
  });

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const body: Record<string, unknown> = {};
    const fieldName = (suffix: string) =>
      mode === 'planned' ? `planned${suffix}` : `achieved${suffix}`;
    if (movement.tracksRepetitions && reps !== '') body[fieldName('Reps')] = parseInt(reps, 10);
    if (movement.tracksWeight && weight !== '') body[fieldName('Weight')] = weight;
    if (movement.tracksDuration && duration !== '') body[fieldName('DurationSeconds')] = parseInt(duration, 10);
    if (movement.tracksDistance && distance !== '') body[fieldName('DistanceMeters')] = distance;
    mutation.mutate(body);
  };

  if (!open) {
    return (
      <div
        style={{
          padding: 'var(--space-2) var(--space-3)',
          borderTop: '1px solid var(--color-border)',
          display: 'flex',
          justifyContent: 'center',
        }}
      >
        <button type="button" onClick={() => setOpen(true)}>
          + Add set
        </button>
      </div>
    );
  }

  const repsLabel = mode === 'planned' ? 'Reps (planned)' : 'Reps';

  return (
    <form
      onSubmit={handleSubmit}
      style={{
        padding: 'var(--space-2) var(--space-3)',
        borderTop: '1px solid var(--color-border)',
        display: 'flex',
        flexWrap: 'wrap',
        gap: 'var(--space-2)',
      }}
    >
      {movement.tracksRepetitions && (
        <label style={{ flex: '1 1 100px' }}>
          {repsLabel}
          <input type="number" min="0" value={reps} onChange={(e) => setReps(e.target.value)} style={{ width: '100%' }} />
        </label>
      )}
      {movement.tracksWeight && (
        <label style={{ flex: '1 1 100px' }}>
          Weight (kg)
          <input type="text" inputMode="decimal" value={weight} onChange={(e) => setWeight(e.target.value)} style={{ width: '100%' }} />
        </label>
      )}
      {movement.tracksDuration && (
        <label style={{ flex: '1 1 100px' }}>
          Duration (s)
          <input type="number" min="0" value={duration} onChange={(e) => setDuration(e.target.value)} style={{ width: '100%' }} />
        </label>
      )}
      {movement.tracksDistance && (
        <label style={{ flex: '1 1 100px' }}>
          Distance (m)
          <input type="text" inputMode="decimal" value={distance} onChange={(e) => setDistance(e.target.value)} style={{ width: '100%' }} />
        </label>
      )}
      <div style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'flex-end' }}>
        {/* Same rule as SetValuesForm: achieved-mode save validates the set (green check); planned-mode save is a regular save (floppy). */}
        <button
          type="submit"
          className={mode === 'achieved' ? 'icon-button icon-button--success' : 'icon-button'}
          disabled={mutation.isPending}
          aria-label={mode === 'achieved' ? 'Validate set' : 'Save planned set'}
          title={mode === 'achieved' ? 'Validate set' : 'Save'}
        >
          {mode === 'achieved' ? <CheckIcon /> : <SaveIcon />}
        </button>
        <button type="button" className="icon-button" onClick={reset} aria-label="Cancel" title="Cancel">
          <XMarkIcon />
        </button>
      </div>
      {mutation.isError && (
        <p className="error" style={{ flex: '1 1 100%', color: 'var(--color-danger)' }}>
          {mutation.error instanceof Error ? mutation.error.message : 'Unable to add set.'}
        </p>
      )}
    </form>
  );
}
