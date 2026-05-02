import type { ReactNode } from 'react';

export interface MonthCalendarProps<TEvent> {
  /** 4-digit year, e.g. 2026. */
  year: number;
  /** 1-based month (1 = January, 12 = December). */
  month: number;
  /** Events to lay out in the month grid. */
  events: readonly TEvent[];
  /** Returns the local-day date used to bucket the event into a cell. */
  getDate: (event: TEvent) => Date;
  /** Returns a stable key for the event (used as React key). */
  getEventKey: (event: TEvent) => string;
  /** Renders the event inside its day cell. The caller controls colors / links / labels. */
  renderEvent: (event: TEvent) => ReactNode;
  /** ISO weekday on which the week starts. 1 = Monday (default), 0 = Sunday. */
  weekStartsOn?: 0 | 1;
  /** Optional override for the weekday header labels (length 7, in display order). */
  weekdayLabels?: readonly string[];
}

const DEFAULT_WEEKDAYS_MONDAY = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as const;
const DEFAULT_WEEKDAYS_SUNDAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as const;

function dayKey(date: Date): string {
  const y = date.getFullYear();
  const m = (date.getMonth() + 1).toString().padStart(2, '0');
  const d = date.getDate().toString().padStart(2, '0');
  return `${y}-${m}-${d}`;
}

/**
 * A pure-presentation month grid. Lays out events keyed by their local-day date into 7-column
 * weeks. The component is generic so it can host any kind of event, not just workouts.
 *
 * Navigation (prev / next / today) is intentionally NOT included — it is the caller's job to
 * own the `(year, month)` state and decide the navigation UX.
 */
export function MonthCalendar<TEvent>({
  year,
  month,
  events,
  getDate,
  getEventKey,
  renderEvent,
  weekStartsOn = 1,
  weekdayLabels,
}: MonthCalendarProps<TEvent>) {
  const firstOfMonth = new Date(year, month - 1, 1);
  const lastOfMonth = new Date(year, month, 0);
  const daysInMonth = lastOfMonth.getDate();

  const firstWeekday = firstOfMonth.getDay(); // 0 = Sun … 6 = Sat
  const leadingBlank =
    weekStartsOn === 1 ? (firstWeekday + 6) % 7 : firstWeekday;
  const totalCells = Math.ceil((leadingBlank + daysInMonth) / 7) * 7;

  const eventsByDay = new Map<string, TEvent[]>();
  for (const event of events) {
    const key = dayKey(getDate(event));
    let bucket = eventsByDay.get(key);
    if (!bucket) {
      bucket = [];
      eventsByDay.set(key, bucket);
    }
    bucket.push(event);
  }

  const todayKey = dayKey(new Date());
  const labels = weekdayLabels
    ?? (weekStartsOn === 1 ? DEFAULT_WEEKDAYS_MONDAY : DEFAULT_WEEKDAYS_SUNDAY);

  const cells: ReactNode[] = [];
  for (let i = 0; i < totalCells; i++) {
    const dayOffset = i - leadingBlank;
    const cellDate = new Date(year, month - 1, 1 + dayOffset);
    const cellKey = dayKey(cellDate);
    const inMonth = cellDate.getMonth() === month - 1;
    const isToday = cellKey === todayKey;
    const cellEvents = eventsByDay.get(cellKey) ?? [];

    cells.push(
      <div
        key={cellKey}
        className={[
          'month-calendar__day',
          inMonth ? '' : 'month-calendar__day--out-of-month',
          isToday ? 'month-calendar__day--today' : '',
        ]
          .filter(Boolean)
          .join(' ')}
      >
        <div className="month-calendar__day-number">{cellDate.getDate()}</div>
        {cellEvents.length > 0 && (
          <div className="month-calendar__day-events">
            {cellEvents.map((event) => (
              <div key={getEventKey(event)} className="month-calendar__event">
                {renderEvent(event)}
              </div>
            ))}
          </div>
        )}
      </div>,
    );
  }

  return (
    <div className="month-calendar">
      <div className="month-calendar__weekdays">
        {labels.map((label) => (
          <div key={label} className="month-calendar__weekday">
            {label}
          </div>
        ))}
      </div>
      <div className="month-calendar__grid">{cells}</div>
    </div>
  );
}
