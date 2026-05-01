import { useQuery } from '@tanstack/react-query';
import { apiRequest } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type {
  PersonalBestType,
  PlayerMovementPersonalBestsDataOutput,
} from '../api/types';
import { formatDateTime } from '../lib/format';

const PB_LABELS: Record<PersonalBestType, string> = {
  HIGHEST_WEIGHT: 'Highest weight',
  HIGHEST_REPS: 'Highest reps',
  HIGHEST_VOLUME_ONE_SET: 'Best one-set volume',
  HIGHEST_VOLUME_WORKOUT: 'Best workout volume',
  HIGHEST_DURATION: 'Longest duration',
  HIGHEST_DISTANCE: 'Longest distance',
  HIGHEST_SPEED: 'Best speed',
};

const PB_UNITS: Record<PersonalBestType, string> = {
  HIGHEST_WEIGHT: ' kg',
  HIGHEST_REPS: ' reps',
  HIGHEST_VOLUME_ONE_SET: ' kg·reps',
  HIGHEST_VOLUME_WORKOUT: ' kg·reps',
  HIGHEST_DURATION: ' s',
  HIGHEST_DISTANCE: ' m',
  HIGHEST_SPEED: ' m/s',
};

function formatValue(value: string, type: PersonalBestType): string {
  // Trim trailing zeros after decimal point so "120.0000" displays as "120".
  const trimmed = value.includes('.') ? value.replace(/0+$/, '').replace(/\.$/, '') : value;
  return `${trimmed}${PB_UNITS[type]}`;
}

export function AchievementsPage() {
  const { token } = useAuth();
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['personal-bests'],
    queryFn: () =>
      apiRequest<PlayerMovementPersonalBestsDataOutput[]>('/api/player/personal-bests', { token }),
    enabled: token !== null,
  });

  return (
    <>
      <h1 style={{ marginTop: 0 }}>Achievements</h1>
      {isLoading && <p className="muted">Loading…</p>}
      {isError && (
        <p className="error" style={{ color: 'var(--color-danger)' }}>
          {error instanceof Error ? error.message : 'Unable to load personal bests.'}
        </p>
      )}
      {data && data.length === 0 && (
        <p className="muted">No personal bests yet — finish a workout to earn your first one.</p>
      )}
      {data?.map((bucket) => (
        <div key={bucket.movement.id} className="card">
          <h2 style={{ marginTop: 0, marginBottom: 'var(--space-1)' }}>{bucket.movement.label}</h2>
          {bucket.movement.mainMuscleSlug && (
            <div className="muted" style={{ fontSize: '0.85em', marginBottom: 'var(--space-3)' }}>
              {bucket.movement.mainMuscleSlug}
            </div>
          )}
          <ul style={{ paddingLeft: 'var(--space-4)', margin: 0 }}>
            {bucket.personalBests.map((pb) => (
              <li key={pb.type}>
                <strong>{PB_LABELS[pb.type]}</strong>: {formatValue(pb.value, pb.type)}
                <span className="muted" style={{ fontSize: '0.85em' }}>
                  {' '}
                  · {formatDateTime(pb.achievedAt)}
                </span>
              </li>
            ))}
          </ul>
        </div>
      ))}
    </>
  );
}
