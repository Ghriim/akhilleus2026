import { useState } from 'react';
import type { FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiRequest } from '../../api/httpClient';
import { useAuth } from '../../auth/AuthContext';
import type { ExerciseMovementDataOutput, ExerciseSetDataOutput } from '../../api/types';
import { CheckIcon, SaveIcon, XMarkIcon } from '../icons';

interface Props {
  set: ExerciseSetDataOutput;
  movement: ExerciseMovementDataOutput;
  workoutId: string;
  /**
   * Which value group to edit. PLANNED workouts → 'planned' (PUT /sets/:id/planned);
   * IN_PROGRESS workouts → 'achieved' (PUT /sets/:id/achieved). The endpoint and request
   * field names are derived from this; the form layout is identical otherwise.
   */
  mode: 'planned' | 'achieved';
  onDone: () => void;
}

/**
 * Generic editor for a set's planned* OR achieved* values. Replaces the previous
 * AchievedForm (achieved-only) so both PlannedWorkoutView and LiveWorkoutEditor can reuse it.
 */
export function SetValuesForm({ set, movement, workoutId, mode, onDone }: Props) {
  const { token } = useAuth();
  const queryClient = useQueryClient();

  // In planned mode the form is the source of truth — show only planned* values.
  // In achieved mode we let the user start from any prior value (achieved if it exists,
  // otherwise the planned target as a sensible default).
  const initialReps =
    mode === 'planned'
      ? (set.plannedReps?.toString() ?? '')
      : (set.achievedReps?.toString() ?? set.plannedReps?.toString() ?? '');
  const initialWeight =
    mode === 'planned'
      ? (set.plannedWeight ?? '')
      : (set.achievedWeight ?? set.plannedWeight ?? '');
  const initialDuration =
    mode === 'planned'
      ? (set.plannedDurationSeconds?.toString() ?? '')
      : (set.achievedDurationSeconds?.toString() ?? set.plannedDurationSeconds?.toString() ?? '');
  const initialDistance =
    mode === 'planned'
      ? (set.plannedDistanceMeters ?? '')
      : (set.achievedDistanceMeters ?? set.plannedDistanceMeters ?? '');
  const initialInclinePercent =
    mode === 'planned'
      ? (set.plannedInclinePercent ?? '')
      : (set.achievedInclinePercent ?? set.plannedInclinePercent ?? '');
  const initialInclineMeters =
    mode === 'planned'
      ? (set.plannedInclineMeters ?? '')
      : (set.achievedInclineMeters ?? set.plannedInclineMeters ?? '');

  const [reps, setReps] = useState(initialReps);
  const [weight, setWeight] = useState(initialWeight);
  const [duration, setDuration] = useState(initialDuration);
  const [distance, setDistance] = useState(initialDistance);
  const [inclinePercent, setInclinePercent] = useState(initialInclinePercent);
  const [inclineMeters, setInclineMeters] = useState(initialInclineMeters);

  const endpoint = mode === 'planned' ? `/api/player/sets/${set.id}/planned` : `/api/player/sets/${set.id}/achieved`;
  const fieldName = (suffix: string) => (mode === 'planned' ? `planned${suffix}` : `achieved${suffix}`);

  const mutation = useMutation({
    mutationFn: (body: Record<string, unknown>) =>
      apiRequest<ExerciseSetDataOutput>(endpoint, { method: 'PUT', body, token }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['workout', workoutId] });
      onDone();
    },
  });

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const body: Record<string, unknown> = {};
    if (movement.tracksRepetitions) body[fieldName('Reps')] = reps === '' ? null : parseInt(reps, 10);
    if (movement.tracksWeight) body[fieldName('Weight')] = weight === '' ? null : weight;
    if (movement.tracksDuration) body[fieldName('DurationSeconds')] = duration === '' ? null : parseInt(duration, 10);
    if (movement.tracksDistance) body[fieldName('DistanceMeters')] = distance === '' ? null : distance;
    if (movement.tracksInclinePercent) body[fieldName('InclinePercent')] = inclinePercent === '' ? null : inclinePercent;
    if (movement.tracksInclineMeters) body[fieldName('InclineMeters')] = inclineMeters === '' ? null : inclineMeters;
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
        {/*
          In achieved mode the submit *also* marks the set complete (isComplete is auto-derived
          server-side once every required achieved* is filled), so the icon is a green check —
          "validate this set". In planned mode it's a regular save → floppy disk.
        */}
        <button
          type="submit"
          className={mode === 'achieved' ? 'icon-button icon-button--success' : 'icon-button'}
          disabled={mutation.isPending}
          aria-label={mode === 'achieved' ? 'Validate set' : 'Save planned values'}
          title={mode === 'achieved' ? 'Validate set' : 'Save'}
        >
          {mode === 'achieved' ? <CheckIcon /> : <SaveIcon />}
        </button>
        <button type="button" className="icon-button" onClick={onDone} aria-label="Cancel" title="Cancel">
          <XMarkIcon />
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
