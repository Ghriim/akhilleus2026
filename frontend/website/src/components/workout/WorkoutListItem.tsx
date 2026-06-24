import { Link } from 'react-router-dom';
import type { WorkoutDataOutput } from '@/api/types';
import { formatDateTime, formatDurationSeconds, formatNumber } from '@/lib/format';
import { WorkoutStatusBadge } from './WorkoutStatusBadge';

interface WorkoutListItemProps {
  workout: WorkoutDataOutput;
}

export function WorkoutListItem({ workout }: WorkoutListItemProps) {
  const refDate = workout.dateEnd ?? workout.dateStart ?? workout.plannedAt;
  return (
    <Link
      to={`/workouts/${workout.id}`}
      className="block rounded-(--radius-surface) bg-(--color-surface) border border-(--color-border) px-4 py-3 hover:border-(--color-border-strong) hover:shadow-(--shadow-sm) transition-colors"
    >
      <div className="flex items-center justify-between gap-3">
        <div>
          <div className="text-(length:--text-base) font-semibold text-(--color-text)">
            {workout.name || 'Workout sans nom'}
          </div>
          <div className="text-(length:--text-sm) text-(--color-text-muted)">
            {formatDateTime(refDate)}
          </div>
        </div>
        <WorkoutStatusBadge status={workout.status} />
      </div>
      {workout.status === 'COMPLETED' && (
        <div className="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2 text-(length:--text-xs) text-(--color-text-muted)">
          <Metric label="Durée" value={formatDurationSeconds(workout.duration)} />
          <Metric label="Volume" value={formatNumber(workout.volume)} />
          <Metric label="Distance" value={formatNumber(workout.distance)} />
          <Metric label="D+" value={formatNumber(workout.inclineMeters)} />
        </div>
      )}
    </Link>
  );
}

function Metric({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <div className="uppercase tracking-wide">{label}</div>
      <div className="text-(length:--text-sm) font-medium text-(--color-text)">{value}</div>
    </div>
  );
}
