import { useState } from 'react';
import type { FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type { ExerciseMovementDataOutput, ExerciseSetDataOutput } from '../../api/types';

interface Props {
  set: ExerciseSetDataOutput;
  movement: ExerciseMovementDataOutput;
  onDone: () => void;
}

export function AchievedForm({ set, movement, onDone }: Props) {
  const { token } = useAuth();
  const queryClient = useQueryClient();

  const [reps, setReps] = useState(set.achievedReps?.toString() ?? set.plannedReps?.toString() ?? '');
  const [weight, setWeight] = useState(set.achievedWeight ?? set.plannedWeight ?? '');
  const [duration, setDuration] = useState(
    set.achievedDurationSeconds?.toString() ?? set.plannedDurationSeconds?.toString() ?? '',
  );
  const [distance, setDistance] = useState(set.achievedDistanceMeters ?? set.plannedDistanceMeters ?? '');
  const [inclinePercent, setInclinePercent] = useState(
    set.achievedInclinePercent ?? set.plannedInclinePercent ?? '',
  );
  const [inclineMeters, setInclineMeters] = useState(
    set.achievedInclineMeters ?? set.plannedInclineMeters ?? '',
  );

  const mutation = useMutation({
    mutationFn: (body: Record<string, unknown>) =>
      apiRequest<ExerciseSetDataOutput>(`/api/player/sets/${set.id}/achieved`, {
        method: 'PUT',
        body,
        token,
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['workout', set.exerciseId] });
      void queryClient.invalidateQueries({ queryKey: ['workout'] });
      onDone();
    },
  });

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const body: Record<string, unknown> = {};
    if (movement.tracksRepetitions) body.achievedReps = reps === '' ? null : parseInt(reps, 10);
    if (movement.tracksWeight) body.achievedWeight = weight === '' ? null : weight;
    if (movement.tracksDuration) body.achievedDurationSeconds = duration === '' ? null : parseInt(duration, 10);
    if (movement.tracksDistance) body.achievedDistanceMeters = distance === '' ? null : distance;
    if (movement.tracksInclinePercent) body.achievedInclinePercent = inclinePercent === '' ? null : inclinePercent;
    if (movement.tracksInclineMeters) body.achievedInclineMeters = inclineMeters === '' ? null : inclineMeters;
    mutation.mutate(body);
  };

  return (
    <form onSubmit={handleSubmit} style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--space-2)' }}>
      {movement.tracksRepetitions && (
        <label style={{ flex: '1 1 100px' }}>
          Reps
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
      {movement.tracksInclinePercent && (
        <label style={{ flex: '1 1 100px' }}>
          Incline (%)
          <input type="text" inputMode="decimal" value={inclinePercent} onChange={(e) => setInclinePercent(e.target.value)} style={{ width: '100%' }} />
        </label>
      )}
      {movement.tracksInclineMeters && (
        <label style={{ flex: '1 1 100px' }}>
          Incline (m)
          <input type="text" inputMode="decimal" value={inclineMeters} onChange={(e) => setInclineMeters(e.target.value)} style={{ width: '100%' }} />
        </label>
      )}
      <div style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'flex-end' }}>
        <button type="submit" className="primary" disabled={mutation.isPending}>
          {mutation.isPending ? 'Saving…' : 'Save'}
        </button>
        <button type="button" onClick={onDone}>
          Cancel
        </button>
      </div>
      {mutation.isError && (
        <p className="error" style={{ flex: '1 1 100%', color: 'var(--color-danger)' }}>
          {mutation.error instanceof Error ? mutation.error.message : 'Unable to save.'}
        </p>
      )}
    </form>
  );
}
