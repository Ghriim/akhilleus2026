import { useState } from 'react';
import type { FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiRequest, HttpError } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type { WorkoutDataOutput } from '../api/types';
import { StartWorkoutButton } from '../components/StartWorkoutButton';

export function WorkoutNewPage() {
  const { token } = useAuth();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [plannedAtLocal, setPlannedAtLocal] = useState('');
  const [plannedAtError, setPlannedAtError] = useState<string | null>(null);
  const [globalError, setGlobalError] = useState<string | null>(null);

  const planFuture = useMutation({
    mutationFn: (plannedAtIso: string) =>
      apiRequest<WorkoutDataOutput>('/api/player/workouts/planned', {
        method: 'POST',
        token,
        body: { plannedAt: plannedAtIso },
      }),
    onSuccess: (workout) => {
      void queryClient.invalidateQueries({ queryKey: ['workouts'] });
      navigate(`/workouts/${workout.id}`, { replace: true });
    },
    onError: (err) => {
      if (err instanceof HttpError && err.status === 422) {
        const violations = err.violations();
        if (violations.plannedAt) {
          setPlannedAtError(violations.plannedAt.join(' '));
          return;
        }
      }
      setGlobalError(err instanceof Error ? err.message : 'Unable to plan workout.');
    },
  });

  const handlePlan = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setPlannedAtError(null);
    setGlobalError(null);
    if (!plannedAtLocal) {
      setPlannedAtError('Pick a date and time.');
      return;
    }
    const local = new Date(plannedAtLocal);
    if (Number.isNaN(local.getTime())) {
      setPlannedAtError('Invalid date format.');
      return;
    }
    planFuture.mutate(local.toISOString());
  };

  return (
    <>
      <h1 style={{ marginTop: 0 }}>New workout</h1>

      {globalError && (
        <p className="error" style={{ color: 'var(--color-danger)' }}>
          {globalError}
        </p>
      )}

      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
          gap: 'var(--space-4)',
        }}
      >
        <div className="card">
          <h2 style={{ marginTop: 0 }}>Start now</h2>
          <p className="muted">
            Begin a workout right away. You can add movements as you go. If a workout is already in
            progress, this resumes it.
          </p>
          <StartWorkoutButton style={{ width: '100%' }} />
        </div>

        <form className="card" onSubmit={handlePlan}>
          <h2 style={{ marginTop: 0 }}>Plan for later</h2>
          <p className="muted">Pick a date in the future. You'll start it when the time comes.</p>
          <div className="field">
            <label htmlFor="plannedAt">Date &amp; time</label>
            <input
              id="plannedAt"
              type="datetime-local"
              value={plannedAtLocal}
              onChange={(e) => setPlannedAtLocal(e.target.value)}
              required
              style={{ width: '100%' }}
            />
            {plannedAtError && <p className="error">{plannedAtError}</p>}
          </div>
          <button
            type="submit"
            className="primary"
            disabled={planFuture.isPending}
            style={{ width: '100%' }}
          >
            {planFuture.isPending ? 'Planning…' : 'Plan workout'}
          </button>
        </form>
      </div>
    </>
  );
}
