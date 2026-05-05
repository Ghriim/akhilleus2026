import type { WorkoutDetailsDataOutput } from '../../api/types';
import { formatDuration, formatDurationSeconds, formatNumeric } from '../../lib/format';
import { summarizeSet } from '../../lib/workout';
import { WorkoutStatusBadge } from '../WorkoutStatusBadge';
import { MovementMedia } from './MovementMedia';

interface Props {
  workout: WorkoutDetailsDataOutput;
}

export function ReadOnlyWorkoutView({ workout }: Props) {
  const duration =
    formatDurationSeconds(workout.duration) ?? formatDuration(workout.dateStart, workout.dateEnd);
  const volume = formatNumeric(workout.volume);
  const distance = formatNumeric(workout.distance);
  const inclineMeters = formatNumeric(workout.inclineMeters);

  const stats: { label: string; value: string }[] = [];
  if (duration) stats.push({ label: 'Duration', value: duration });
  if (volume !== null) stats.push({ label: 'Volume', value: `${volume} kg` });
  if (distance !== null) stats.push({ label: 'Distance', value: `${distance} m` });
  if (inclineMeters !== null) stats.push({ label: 'Elevation', value: `${inclineMeters} m` });

  return (
    <>
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          gap: 'var(--space-3)',
          flexWrap: 'wrap',
        }}
      >
        <h1 style={{ marginTop: 0 }}>{workout.name}</h1>
        <WorkoutStatusBadge status={workout.status} />
      </div>

      {stats.length > 0 && (
        <div className="card">
          <dl
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fit, minmax(120px, 1fr))',
              gap: 'var(--space-3)',
              margin: 0,
            }}
          >
            {stats.map((stat) => (
              <div key={stat.label}>
                <dt
                  className="muted"
                  style={{
                    fontSize: '0.75em',
                    letterSpacing: '0.06em',
                    textTransform: 'uppercase',
                  }}
                >
                  {stat.label}
                </dt>
                <dd style={{ margin: 0, fontWeight: 600, fontSize: '1.1em' }}>{stat.value}</dd>
              </div>
            ))}
          </dl>
        </div>
      )}

      {workout.exercises.length === 0 && <p className="muted">No movements were logged.</p>}
      {workout.exercises.map((exercise) => (
        <div key={exercise.id} className="card" style={{ padding: 0, overflow: 'hidden' }}>
          <div className="exercise-header">
            <strong style={{ fontSize: '1.1em' }}>{exercise.movement.label}</strong>
          </div>
          <div className="exercise-body">
            <aside className="exercise-media">
              <MovementMedia movement={exercise.movement} />
            </aside>
            <div className="exercise-sets">
              {exercise.sets.map((set) => (
                <div
                  key={set.id}
                  style={{
                    padding: 'var(--space-2) var(--space-3)',
                    borderTop: '1px solid var(--color-border)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: 'var(--space-2)',
                  }}
                >
                  <span style={{ fontWeight: 600 }}>{summarizeSet(set, exercise.movement)}</span>
                  {set.isComplete && (
                    <span style={{ color: 'var(--color-success)', fontSize: '0.85em' }}>
                      ✓ completed
                    </span>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>
      ))}
    </>
  );
}
