import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { apiRequest } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type { WorkoutDataOutput } from '../api/types';
import { WorkoutListItem } from '../components/WorkoutListItem';

export function UpcomingPage() {
  const { token } = useAuth();
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['workouts', 'upcoming'],
    queryFn: () => apiRequest<WorkoutDataOutput[]>('/api/player/workouts/upcoming', { token }),
    enabled: token !== null,
  });

  return (
    <>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <h1 style={{ marginTop: 0 }}>Upcoming workouts</h1>
        <Link to="/workouts/new">
          <button type="button" className="primary">
            New workout
          </button>
        </Link>
      </div>
      {isLoading && <p className="muted">Loading…</p>}
      {isError && (
        <p className="error" style={{ color: 'var(--color-danger)' }}>
          {error instanceof Error ? error.message : 'Unable to load upcoming workouts.'}
        </p>
      )}
      {data && data.length === 0 && (
        <p className="muted">No upcoming workouts. Plan or start a new one.</p>
      )}
      {data?.map((workout) => <WorkoutListItem key={workout.id} workout={workout} />)}
    </>
  );
}
