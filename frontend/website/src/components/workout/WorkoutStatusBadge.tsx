import type { WorkoutStatus } from '@/api/types';
import { cn } from '@/lib/cn';

const STATUS_STYLE: Record<WorkoutStatus, { label: string; classes: string }> = {
  PLANNED: {
    label: 'Planifié',
    classes: 'bg-(--color-status-planned-bg) text-(--color-status-planned)',
  },
  IN_PROGRESS: {
    label: 'En cours',
    classes: 'bg-(--color-status-in-progress-bg) text-(--color-status-in-progress)',
  },
  COMPLETED: {
    label: 'Terminé',
    classes: 'bg-(--color-status-completed-bg) text-(--color-status-completed)',
  },
  CANCELED: {
    label: 'Annulé',
    classes: 'bg-(--color-status-canceled-bg) text-(--color-status-canceled)',
  },
};

interface WorkoutStatusBadgeProps {
  status: WorkoutStatus;
  className?: string;
}

export function WorkoutStatusBadge({ status, className }: WorkoutStatusBadgeProps) {
  const { label, classes } = STATUS_STYLE[status];
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-(--radius-sm) px-2 py-0.5 text-(length:--text-xs) font-semibold uppercase tracking-wide',
        classes,
        className,
      )}
    >
      {label}
    </span>
  );
}
