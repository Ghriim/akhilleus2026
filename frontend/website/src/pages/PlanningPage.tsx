import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { apiRequest } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type { WorkoutDataOutput, WorkoutStatus } from '../api/types';
import { MonthCalendar } from '../components/calendar/MonthCalendar';

const MONTH_LABELS = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
] as const;

const STATUS_SHORT_LABEL: Record<WorkoutStatus, string> = {
  PLANNED: 'Planned',
  IN_PROGRESS: 'Live',
  COMPLETED: 'Done',
  CANCELED: 'Canceled',
};

function workoutReferenceDate(workout: WorkoutDataOutput): Date {
  const iso = workout.dateEnd ?? workout.dateStart ?? workout.plannedAt;
  if (!iso) {
    // Should never happen — every workout has at least one date set by the backend — but the
    // type system doesn't know that. Fall back to epoch so the cell still renders.
    return new Date(0);
  }
  return new Date(iso);
}

export function PlanningPage() {
  const { token } = useAuth();
  const today = useMemo(() => new Date(), []);
  const [year, setYear] = useState(today.getFullYear());
  const [month, setMonth] = useState(today.getMonth() + 1);

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['workouts', 'calendar', year, month],
    queryFn: () =>
      apiRequest<WorkoutDataOutput[]>(
        `/api/player/workouts/calendar?year=${year}&month=${month}`,
        { token },
      ),
    enabled: token !== null,
  });

  const goPrev = () => {
    if (month === 1) {
      setYear((y) => y - 1);
      setMonth(12);
    } else {
      setMonth((m) => m - 1);
    }
  };
  const goNext = () => {
    if (month === 12) {
      setYear((y) => y + 1);
      setMonth(1);
    } else {
      setMonth((m) => m + 1);
    }
  };
  const goToday = () => {
    setYear(today.getFullYear());
    setMonth(today.getMonth() + 1);
  };

  return (
    <>
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          flexWrap: 'wrap',
          gap: 'var(--space-3)',
        }}
      >
        <h1 style={{ marginTop: 0 }}>Planning</h1>
        <Link to="/workouts/new">
          <button type="button" className="primary">
            New workout
          </button>
        </Link>
      </div>

      <div className="planning-toolbar">
        <button type="button" onClick={goPrev} aria-label="Previous month">
          ‹
        </button>
        <strong className="planning-toolbar__title">
          {MONTH_LABELS[month - 1]} {year}
        </strong>
        <button type="button" onClick={goNext} aria-label="Next month">
          ›
        </button>
        <button type="button" onClick={goToday}>
          Today
        </button>
      </div>

      {isLoading && <p className="muted">Loading…</p>}
      {isError && (
        <p className="error" style={{ color: 'var(--color-danger)' }}>
          {error instanceof Error ? error.message : 'Unable to load planning.'}
        </p>
      )}

      <MonthCalendar<WorkoutDataOutput>
        year={year}
        month={month}
        events={data ?? []}
        getDate={workoutReferenceDate}
        getEventKey={(workout) => workout.id}
        renderEvent={(workout) => (
          <Link
            to={`/workouts/${workout.id}`}
            className={`calendar-pill calendar-pill--${workout.status.toLowerCase()}`}
          >
            {STATUS_SHORT_LABEL[workout.status]}
          </Link>
        )}
      />
    </>
  );
}
