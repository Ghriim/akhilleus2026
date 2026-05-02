import { useState } from 'react';
import type { FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type { ExerciseDataOutput, PlayerMovementListItemDataOutput } from '../../api/types';
import { CheckIcon, XMarkIcon } from '../icons';

interface Props {
  workoutId: string;
}

export function AddMovementForm({ workoutId }: Props) {
  const { token } = useAuth();
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(false);
  const [movementId, setMovementId] = useState('');
  const [restDuration, setRestDuration] = useState('60');

  const reset = () => {
    setMovementId('');
    setRestDuration('60');
    setOpen(false);
  };

  const movements = useQuery({
    queryKey: ['movements'],
    queryFn: () =>
      apiRequest<PlayerMovementListItemDataOutput[]>('/api/player/movements', { token }),
    enabled: token !== null && open,
    staleTime: 5 * 60 * 1000,
  });

  const mutation = useMutation({
    mutationFn: (body: { movementId: string; restDurationSeconds: number }) =>
      apiRequest<ExerciseDataOutput>(`/api/player/workouts/${workoutId}/exercises`, {
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
    if (!movementId) return;
    mutation.mutate({
      movementId,
      restDurationSeconds: parseInt(restDuration, 10) || 0,
    });
  };

  if (!open) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', margin: 'var(--space-3) 0' }}>
        <button type="button" onClick={() => setOpen(true)}>
          + Add exercise
        </button>
      </div>
    );
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="card"
      style={{ display: 'flex', gap: 'var(--space-2)', flexWrap: 'wrap', alignItems: 'flex-end' }}
    >
      <label style={{ flex: '2 1 200px' }}>
        Movement
        <select
          value={movementId}
          onChange={(e) => setMovementId(e.target.value)}
          required
          style={{ width: '100%' }}
        >
          <option value="">— pick a movement —</option>
          {movements.data?.map((m) => (
            <option key={m.id} value={m.id}>
              {m.label} ({m.mainMuscleSlug})
            </option>
          ))}
        </select>
      </label>
      <label style={{ flex: '1 1 100px' }}>
        Rest (s)
        <input
          type="number"
          min="0"
          value={restDuration}
          onChange={(e) => setRestDuration(e.target.value)}
          style={{ width: '100%' }}
        />
      </label>
      <div style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'flex-end' }}>
        <button
          type="submit"
          className="icon-button icon-button--success"
          disabled={mutation.isPending || !movementId}
          aria-label="Add exercise"
          title="Add exercise"
        >
          <CheckIcon />
        </button>
        <button type="button" className="icon-button" onClick={reset} aria-label="Cancel" title="Cancel">
          <XMarkIcon />
        </button>
      </div>
      {mutation.isError && (
        <p className="error" style={{ flex: '1 1 100%', color: 'var(--color-danger)' }}>
          {mutation.error instanceof Error ? mutation.error.message : 'Unable to add movement.'}
        </p>
      )}
    </form>
  );
}
