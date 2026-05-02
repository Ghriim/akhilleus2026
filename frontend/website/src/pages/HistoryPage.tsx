import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiRequest } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type { WorkoutHistoryDataOutput } from '../api/types';
import { WorkoutListItem } from '../components/WorkoutListItem';

const PER_PAGE = 10;

export function HistoryPage() {
  const { token } = useAuth();
  const [page, setPage] = useState(1);

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['workouts', 'history', page, PER_PAGE],
    queryFn: () =>
      apiRequest<WorkoutHistoryDataOutput>(
        `/api/player/workouts/history?page=${page}&perPage=${PER_PAGE}`,
        { token },
      ),
    enabled: token !== null,
  });

  const totalPages = data ? Math.max(1, Math.ceil(data.totalCount / data.perPage)) : 1;
  const hasPrev = page > 1;
  const hasNext = data ? page < totalPages : false;

  return (
    <>
      <h1 style={{ marginTop: 0 }}>Workout history</h1>
      {isLoading && <p className="muted">Loading…</p>}
      {isError && (
        <p className="error" style={{ color: 'var(--color-danger)' }}>
          {error instanceof Error ? error.message : 'Unable to load history.'}
        </p>
      )}
      {data && data.totalCount === 0 && (
        <p className="muted">No completed workouts yet.</p>
      )}
      {data?.items.map((workout) => (
        <WorkoutListItem key={workout.id} workout={workout} variant="history" />
      ))}
      {data && data.totalCount > 0 && (
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            marginTop: 'var(--space-4)',
          }}
        >
          <span className="muted" style={{ fontSize: '0.9em' }}>
            Page {page} of {totalPages} · {data.totalCount} workout{data.totalCount > 1 ? 's' : ''}
          </span>
          <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
            <button type="button" disabled={!hasPrev} onClick={() => setPage((p) => p - 1)}>
              Prev
            </button>
            <button type="button" disabled={!hasNext} onClick={() => setPage((p) => p + 1)}>
              Next
            </button>
          </div>
        </div>
      )}
    </>
  );
}
