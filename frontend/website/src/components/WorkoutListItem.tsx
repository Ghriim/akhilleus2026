import { Link } from 'react-router-dom';
import type { WorkoutDataOutput, WorkoutStatus } from '../api/types';
import {
  formatDate,
  formatDateTime,
  formatDuration,
  formatDurationSeconds,
  formatNumeric,
  formatRelative,
} from '../lib/format';

const STATUS_LABEL: Record<WorkoutStatus, string> = {
  PLANNED: 'Planned',
  IN_PROGRESS: 'In progress',
  COMPLETED: 'Completed',
  CANCELED: 'Canceled',
};

const STATUS_COLOR: Record<WorkoutStatus, string> = {
  PLANNED: 'var(--color-iron)',
  IN_PROGRESS: 'var(--color-primary)',
  COMPLETED: 'var(--color-success)',
  CANCELED: 'var(--color-danger)',
};

interface Props {
  workout: WorkoutDataOutput;
  /**
   * `default` (Dashboard widget, etc.) shows a relative time ("Started 2h ago", "Finished
   * yesterday"). `history` shows the absolute date (no time-of-day) and the workout duration
   * (`dateEnd - dateStart`) — relative timing is irrelevant for past workouts.
   */
  variant?: 'default' | 'history';
}

export function WorkoutListItem({ workout, variant = 'default' }: Props) {
  const subtitle = variant === 'history' ? historySubtitle(workout) : defaultSubtitle(workout);

  return (
    <Link
      to={`/workouts/${workout.id}`}
      className="card"
      style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        textDecoration: 'none',
        color: 'inherit',
      }}
    >
      <div>
        <div style={{ fontWeight: 600 }}>{workout.name}</div>
        {subtitle && (
          <div className="muted" style={{ fontSize: '0.85em' }}>
            {subtitle}
          </div>
        )}
      </div>
      <span
        style={{
          padding: '2px 10px',
          borderRadius: 999,
          background: STATUS_COLOR[workout.status],
          color: 'var(--color-primary-text)',
          fontSize: '0.8em',
          letterSpacing: '0.02em',
        }}
      >
        {STATUS_LABEL[workout.status]}
      </span>
    </Link>
  );
}

function defaultSubtitle(workout: WorkoutDataOutput): string | null {
  if (workout.status === 'COMPLETED') {
    return `Finished ${formatRelative(workout.dateEnd)} · ${formatDateTime(workout.dateEnd)}`;
  }
  if (workout.status === 'IN_PROGRESS') {
    return `Started ${formatRelative(workout.dateStart)}`;
  }
  if (workout.plannedAt) {
    return `Planned for ${formatDateTime(workout.plannedAt)}`;
  }
  return null;
}

function historySubtitle(workout: WorkoutDataOutput): string | null {
  // History rows surface the calendar date (no time) plus the persisted aggregates that are
  // non-null. Duration is preferred from the stored `workout.duration` (seconds) and falls back
  // to a dateStart/dateEnd computation for legacy rows that completed before the migration.
  const referenceDate = workout.dateEnd ?? workout.dateStart ?? workout.plannedAt;
  const parts: string[] = [];

  if (referenceDate) parts.push(formatDate(referenceDate));

  const duration =
    formatDurationSeconds(workout.duration) ?? formatDuration(workout.dateStart, workout.dateEnd);
  if (duration) parts.push(duration);

  const volume = formatNumeric(workout.volume);
  if (volume !== null) parts.push(`${volume} kg`);

  const distance = formatNumeric(workout.distance);
  if (distance !== null) parts.push(`${distance} m`);

  const inclineMeters = formatNumeric(workout.inclineMeters);
  if (inclineMeters !== null) parts.push(`${inclineMeters} m elevation`);

  return parts.length > 0 ? parts.join(' · ') : null;
}
