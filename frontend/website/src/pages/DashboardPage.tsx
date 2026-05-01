import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { apiRequest } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type { WorkoutDataOutput, WorkoutHistoryDataOutput } from '../api/types';
import { WorkoutListItem } from '../components/WorkoutListItem';
import { StartWorkoutButton } from '../components/StartWorkoutButton';

export function DashboardPage() {
  const { token } = useAuth();

  const upcoming = useQuery({
    queryKey: ['workouts', 'upcoming'],
    queryFn: () => apiRequest<WorkoutDataOutput[]>('/api/player/workouts/upcoming', { token }),
    enabled: token !== null,
  });

  const history = useQuery({
    queryKey: ['workouts', 'history', 1, 3],
    queryFn: () =>
      apiRequest<WorkoutHistoryDataOutput>('/api/player/workouts/history?page=1&perPage=3', {
        token,
      }),
    enabled: token !== null,
  });

  const upcomingTop3 = upcoming.data?.slice(0, 3) ?? [];
  const historyTop3 = history.data?.items ?? [];

  return (
    <>
      <h1 style={{ marginTop: 0 }}>Dashboard</h1>

      <section
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
          gap: 'var(--space-4)',
          marginBottom: 'var(--space-6)',
        }}
      >
        <StartWorkoutButton style={{ padding: 'var(--space-4)' }} />
        <Link to="/workouts/new" style={{ textDecoration: 'none' }}>
          <button type="button" className="primary" style={{ width: '100%', padding: 'var(--space-4)' }}>
            Plan workout
          </button>
        </Link>
      </section>

      <section style={{ marginBottom: 'var(--space-8)' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}>
          <h2>Upcoming</h2>
          <Link to="/upcoming">View all</Link>
        </div>
        {upcoming.isLoading && <p className="muted">Loading…</p>}
        {upcomingTop3.length === 0 && !upcoming.isLoading && (
          <p className="muted">No upcoming workouts.</p>
        )}
        {upcomingTop3.map((workout) => (
          <WorkoutListItem key={workout.id} workout={workout} />
        ))}
      </section>

      <section>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}>
          <h2>Recent history</h2>
          <Link to="/history">View all</Link>
        </div>
        {history.isLoading && <p className="muted">Loading…</p>}
        {historyTop3.length === 0 && !history.isLoading && (
          <p className="muted">No completed workouts yet.</p>
        )}
        {historyTop3.map((workout) => (
          <WorkoutListItem key={workout.id} workout={workout} />
        ))}
      </section>
    </>
  );
}
