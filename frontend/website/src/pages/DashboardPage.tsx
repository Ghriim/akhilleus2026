import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { apiRequest } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type { WorkoutDataOutput } from '../api/types';
import { WorkoutListItem } from '../components/WorkoutListItem';
import { StartWorkoutButton } from '../components/StartWorkoutButton';

export function DashboardPage() {
  const { token } = useAuth();

  const upcoming = useQuery({
    queryKey: ['workouts', 'upcoming'],
    queryFn: () => apiRequest<WorkoutDataOutput[]>('/api/player/workouts/upcoming', { token }),
    enabled: token !== null,
  });

  const nextPlanned = upcoming.data?.find((w) => w.status === 'PLANNED') ?? null;

  return (
    <>
      <h1 style={{ marginTop: 0 }}>Dashboard</h1>

      <section>
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            gap: 'var(--space-4)',
            flexWrap: 'wrap',
            marginBottom: 'var(--space-3)',
          }}
        >
          <h2 style={{ margin: 0 }}>
            <Link
              to="/planning"
              style={{ color: 'inherit', textDecoration: 'none' }}
              aria-label="Open workout planning"
            >
              Next Workout
            </Link>
          </h2>
          <StartWorkoutButton />
        </div>

        {upcoming.isLoading && <p className="muted">Loading…</p>}
        {!upcoming.isLoading && nextPlanned === null && (
          <p className="muted">No planned workout.</p>
        )}
        {nextPlanned && <WorkoutListItem workout={nextPlanned} />}
      </section>
    </>
  );
}
