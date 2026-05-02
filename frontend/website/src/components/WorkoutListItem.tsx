import { Link } from 'react-router-dom';
import type { WorkoutDataOutput, WorkoutStatus } from '../api/types';
import { formatDateTime, formatRelative } from '../lib/format';

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
}

export function WorkoutListItem({ workout }: Props) {
  const primaryDate =
    workout.status === 'COMPLETED'
      ? workout.dateEnd
      : workout.status === 'IN_PROGRESS'
        ? workout.dateStart
        : workout.plannedAt;

  const dateLabel =
    workout.status === 'COMPLETED'
      ? `Finished ${formatRelative(workout.dateEnd)}`
      : workout.status === 'IN_PROGRESS'
        ? `Started ${formatRelative(workout.dateStart)}`
        : workout.plannedAt
          ? `Planned for ${formatDateTime(workout.plannedAt)}`
          : '—';

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
        <div style={{ fontWeight: 600 }}>{dateLabel}</div>
        {primaryDate && (
          <div className="muted" style={{ fontSize: '0.85em' }}>
            {formatDateTime(primaryDate)}
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
