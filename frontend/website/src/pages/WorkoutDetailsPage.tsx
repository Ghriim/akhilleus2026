import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';
import { apiRequest, HttpError } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type { WorkoutDetailsDataOutput } from '../api/types';
import { PlannedWorkoutView } from '../components/workout/PlannedWorkoutView';
import { LiveWorkoutEditor } from '../components/workout/LiveWorkoutEditor';
import { ReadOnlyWorkoutView } from '../components/workout/ReadOnlyWorkoutView';

export function WorkoutDetailsPage() {
  const { id } = useParams<{ id: string }>();
  const { token } = useAuth();

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['workout', id],
    queryFn: () => apiRequest<WorkoutDetailsDataOutput>(`/api/player/workouts/${id}`, { token }),
    enabled: token !== null && id !== undefined,
  });

  if (isLoading) return <p className="muted">Loading…</p>;
  if (isError) {
    if (error instanceof HttpError && error.status === 404) {
      return <p className="error" style={{ color: 'var(--color-danger)' }}>Workout not found.</p>;
    }
    return (
      <p className="error" style={{ color: 'var(--color-danger)' }}>
        {error instanceof Error ? error.message : 'Unable to load workout.'}
      </p>
    );
  }
  if (!data) return null;

  switch (data.status) {
    case 'PLANNED':
      return <PlannedWorkoutView workout={data} />;
    case 'IN_PROGRESS':
      return <LiveWorkoutEditor workout={data} />;
    case 'COMPLETED':
    case 'CANCELED':
      return <ReadOnlyWorkoutView workout={data} />;
  }
}
