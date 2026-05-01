import { useState } from 'react';
import type { FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type { ExerciseMovementDataOutput, ExerciseSetDataOutput } from '../../api/types';

interface Props {
  exerciseId: string;
  movement: ExerciseMovementDataOutput;
  workoutId: string;
}

export function AddSetForm({ exerciseId, movement, workoutId }: Props) {
  const { token } = useAuth();
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(false);
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
    if (movement.tracksRepetitions && reps !== '') body.plannedReps = parseInt(reps, 10);
    if (movement.tracksWeight && weight !== '') body.plannedWeight = weight;
    if (movement.tracksDuration && duration !== '') body.plannedDurationSeconds = parseInt(duration, 10);
    if (movement.tracksDistance && distance !== '') body.plannedDistanceMeters = distance;
    mutation.mutate(body);
  };

  if (!open) {
    return (
      <div style={{ padding: 'var(--space-2) var(--space-3)', borderTop: '1px solid var(--color-border)' }}>
        <button type="button" onClick={() => setOpen(true)}>
          + Add set
        </button>
      </div>
    );
  }

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
          Reps (planned)
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
        <button type="submit" className="primary" disabled={mutation.isPending}>
          {mutation.isPending ? 'Adding…' : 'Add'}
        </button>
        <button type="button" onClick={reset}>
          Cancel
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
