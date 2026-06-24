import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { PageHeader } from '@/components/ui/PageHeader';
import { Spinner } from '@/components/ui/Spinner';
import { WorkoutStatusBadge } from '@/components/workout/WorkoutStatusBadge';
import { useMonthWorkouts } from '@/hooks/workout/useWorkouts';
import { cn } from '@/lib/cn';
import { formatDurationSeconds, formatNumber } from '@/lib/format';
import type { WorkoutDataOutput } from '@/api/types';

function refDate(w: WorkoutDataOutput): Date | null {
  const iso = w.dateEnd ?? w.dateStart ?? w.plannedAt;
  if (!iso) return null;
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? null : d;
}

/** Non-zero workout metrics for the calendar cell: duration (only once completed) + volume/distance/D+. */
function workoutMetrics(w: WorkoutDataOutput): string[] {
  const out: string[] = [];
  if ('COMPLETED' === w.status && w.duration) out.push(formatDurationSeconds(w.duration));
  if (null != w.volume && 0 !== Number(w.volume)) out.push(`Vol ${formatNumber(w.volume)}`);
  if (null != w.distance && 0 !== Number(w.distance)) out.push(`Dist ${formatNumber(w.distance)}`);
  if (null != w.inclineMeters && 0 !== Number(w.inclineMeters)) {
    out.push(`D+ ${formatNumber(w.inclineMeters)}`);
  }
  return out;
}

const WEEKDAYS = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

export function PlanningPage() {
  const today = new Date();
  const [cursor, setCursor] = useState({ year: today.getFullYear(), month: today.getMonth() + 1 });
  const { data, isLoading, isError, error } = useMonthWorkouts(cursor.year, cursor.month);

  const byDay = useMemo(() => {
    const map = new Map<string, WorkoutDataOutput[]>();
    if (!data) return map;
    for (const w of data) {
      const d = refDate(w);
      if (!d) continue;
      const key = `${d.getFullYear()}-${d.getMonth() + 1}-${d.getDate()}`;
      if (!map.has(key)) map.set(key, []);
      map.get(key)!.push(w);
    }
    return map;
  }, [data]);

  const firstOfMonth = new Date(cursor.year, cursor.month - 1, 1);
  const startWeekday = (firstOfMonth.getDay() + 6) % 7; // Monday-first
  const daysInMonth = new Date(cursor.year, cursor.month, 0).getDate();
  const cells: (number | null)[] = [];
  for (let i = 0; i < startWeekday; i++) cells.push(null);
  for (let d = 1; d <= daysInMonth; d++) cells.push(d);
  while (0 !== cells.length % 7) cells.push(null);

  const move = (delta: number) => {
    const m = cursor.month + delta;
    if (m < 1) setCursor({ year: cursor.year - 1, month: 12 });
    else if (m > 12) setCursor({ year: cursor.year + 1, month: 1 });
    else setCursor({ year: cursor.year, month: m });
  };

  const monthLabel = firstOfMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

  return (
    <>
      <PageHeader
        title="Planning"
        actions={
          <>
            <Button variant="secondary" onClick={() => move(-1)}>
              ←
            </Button>
            <Button variant="secondary" onClick={() => move(1)}>
              →
            </Button>
          </>
        }
      />
      <div className="mb-3 text-(length:--text-lg) font-medium capitalize">{monthLabel}</div>
      {isLoading ? (
        <Spinner />
      ) : isError ? (
        <Alert tone="danger">{(error as Error).message}</Alert>
      ) : (
        <div className="rounded-(--radius-lg) bg-(--color-surface) border border-(--color-border) p-2">
          <div className="grid grid-cols-7 gap-1 text-(length:--text-xs) text-(--color-text-muted) px-1 pb-1">
            {WEEKDAYS.map((w, i) => (
              <div key={i} className="text-center">
                {w}
              </div>
            ))}
          </div>
          <div className="grid grid-cols-7 gap-1">
            {cells.map((d, i) => {
              if (null === d) return <div key={i} />;
              const key = `${cursor.year}-${cursor.month}-${d}`;
              const items = byDay.get(key) ?? [];
              const isToday =
                today.getFullYear() === cursor.year &&
                today.getMonth() + 1 === cursor.month &&
                today.getDate() === d;
              return (
                <div
                  key={i}
                  className={cn(
                    'min-h-20 rounded-(--radius-sm) border border-(--color-border) p-1 text-(length:--text-xs)',
                    isToday && 'border-(--color-primary) bg-(--color-primary-soft)',
                  )}
                >
                  <div className="font-mono text-(--color-text-muted) mb-1">{d}</div>
                  <div className="space-y-1">
                    {items.map((w) => {
                      const metrics = workoutMetrics(w);
                      return (
                        <Link
                          key={w.id}
                          to={`/workouts/${w.id}`}
                          className="block rounded-(--radius-sm) bg-(--color-surface-muted) px-1 py-0.5 hover:bg-(--color-border) text-(--color-text)"
                          title={w.name}
                        >
                          <div className="flex items-center gap-1">
                            <WorkoutStatusBadge status={w.status} className="!px-1 !py-0" />
                            <span className="truncate">{w.name}</span>
                          </div>
                          {metrics.length > 0 && (
                            <div className="mt-0.5 flex flex-wrap gap-x-1.5 text-(--color-text-muted)">
                              {metrics.map((m, idx) => (
                                <span key={idx}>{m}</span>
                              ))}
                            </div>
                          )}
                        </Link>
                      );
                    })}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}
    </>
  );
}
