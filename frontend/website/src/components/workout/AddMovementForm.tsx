import { useState } from 'react';
import type { FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type { ExerciseDataOutput, PlayerMovementListItemDataOutput } from '../../api/types';

interface Props {
  workoutId: string;
}

export function AddMovementForm({ workoutId }: Props) {
  const { token } = useAuth();
  const queryClient = useQueryClient();
  const [movementId, setMovementId] = useState('');
  const [restDuration, setRestDuration] = useState('60');

  const movements = useQuery({
    queryKey: ['movements'],
    queryFn: () =>
      apiRequest<PlayerMovementListItemDataOutput[]>('/api/player/movements', { token }),
    enabled: token !== null,
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
      setMovementId('');
      setRestDuration('60');
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

  return (
    <form
      onSubmit={handleSubmit}
      className="card"
      style={{ display: 'flex', gap: 'var(--space-2)', flexWrap: 'wrap', alignItems: 'flex-end' }}
    >
      <label style={{ flex: '2 1 200px' }}>
        Add movement
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
      <button type="submit" className="primary" disabled={mutation.isPending || !movementId}>
        {mutation.isPending ? 'Adding…' : 'Add'}
      </button>
      {mutation.isError && (
        <p className="error" style={{ flex: '1 1 100%', color: 'var(--color-danger)' }}>
          {mutation.error instanceof Error ? mutation.error.message : 'Unable to add movement.'}
        </p>
      )}
    </form>
  );
}
