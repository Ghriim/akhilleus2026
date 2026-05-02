import type { WorkoutStatus } from '../api/types';

interface Props {
  status: WorkoutStatus;
}

const STATUS_LABEL: Record<WorkoutStatus, string> = {
  PLANNED: 'Planned',
  IN_PROGRESS: 'In progress',
  COMPLETED: 'Completed',
  CANCELED: 'Canceled',
};

/**
 * Pill-shaped badge displaying a workout's status. Single source of truth for the
 * (label, color) mapping used across list rows, the workout details header, and any future
 * status surface. Colors are driven by `.status-badge--{status}` modifiers in `index.css`.
 */
export function WorkoutStatusBadge({ status }: Props) {
  return (
    <span className={`status-badge status-badge--${status.toLowerCase()}`}>
      {STATUS_LABEL[status]}
    </span>
  );
}
