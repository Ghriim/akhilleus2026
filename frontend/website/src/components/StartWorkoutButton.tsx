import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { CSSProperties } from 'react';
import { apiRequest } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type { WorkoutDataOutput } from '../api/types';

interface Props {
  style?: CSSProperties;
}

/**
 * Single button that starts a brand-new in-progress workout — unless one already exists, in
 * which case it morphs into "Resume workout" and navigates to the existing one. The rule is
 * "at most one in-progress workout per player" enforced client-side via the upcoming list.
 */
export function StartWorkoutButton({ style }: Props) {
  const { token } = useAuth();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const upcoming = useQuery({
    queryKey: ['workouts', 'upcoming'],
    queryFn: () => apiRequest<WorkoutDataOutput[]>('/api/player/workouts/upcoming', { token }),
    enabled: token !== null,
  });

  const inProgress = upcoming.data?.find((w) => w.status === 'IN_PROGRESS') ?? null;

  const startEmpty = useMutation({
    mutationFn: () =>
      apiRequest<WorkoutDataOutput>('/api/player/workouts', { method: 'POST', token }),
    onSuccess: (workout) => {
      void queryClient.invalidateQueries({ queryKey: ['workouts'] });
      navigate(`/workouts/${workout.id}`);
    },
  });

  const handleClick = () => {
    if (inProgress) {
      navigate(`/workouts/${inProgress.id}`);
      return;
    }
    startEmpty.mutate();
  };

  const label = startEmpty.isPending
    ? 'Starting…'
    : inProgress
      ? 'Resume workout'
      : 'Start empty workout';

  // Disable while loading the upcoming list — we don't want to call POST /workouts before we
  // know whether an in-progress one already exists.
  const disabled = startEmpty.isPending || upcoming.isLoading;

  return (
    <button
      type="button"
      className="primary"
      onClick={handleClick}
      disabled={disabled}
      style={style}
    >
      {label}
    </button>
  );
}
