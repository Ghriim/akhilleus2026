/**
 * Lightweight date helpers. Intl-based so we get the user's locale for free.
 */

export function formatDateTime(iso: string | null): string {
  if (!iso) return '—';
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;
  return date.toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
}

export function formatDate(iso: string | null): string {
  if (!iso) return '—';
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;
  return date.toLocaleDateString(undefined, { dateStyle: 'medium' });
}

/**
 * Formats a number of seconds as "XhYmin" — or "Ymin" when X is zero. Returns null when the
 * input is null or non-positive.
 */
export function formatDurationSeconds(totalSeconds: number | null): string | null {
  if (totalSeconds === null || totalSeconds <= 0) return null;
  const totalMinutes = Math.round(totalSeconds / 60);
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;
  return hours === 0 ? `${minutes}min` : `${hours}h${minutes}min`;
}

/**
 * Same as `formatDurationSeconds` but takes two ISO datetimes — kept as a thin convenience
 * for callers that don't already have a stored duration in seconds.
 */
export function formatDuration(startIso: string | null, endIso: string | null): string | null {
  if (!startIso || !endIso) return null;
  const start = new Date(startIso);
  const end = new Date(endIso);
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return null;
  return formatDurationSeconds(Math.round((end.getTime() - start.getTime()) / 1000));
}

/**
 * Trims trailing zeros from a NUMERIC-string ("120.00" → "120", "12.50" → "12.5"). Returns
 * null on null input. Keeps the original string when it isn't a valid finite number.
 */
export function formatNumeric(value: string | null): string | null {
  if (value === null) return null;
  const parsed = Number.parseFloat(value);
  if (!Number.isFinite(parsed)) return value;
  return parsed.toString();
}

export function formatRelative(iso: string | null): string {
  if (!iso) return '—';
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;

  const diffMs = date.getTime() - Date.now();
  const absMin = Math.round(Math.abs(diffMs) / 60000);
  const future = diffMs >= 0;

  if (absMin < 1) return 'now';
  if (absMin < 60) return `${future ? 'in ' : ''}${absMin} min${future ? '' : ' ago'}`;
  const absHour = Math.round(absMin / 60);
  if (absHour < 24) return `${future ? 'in ' : ''}${absHour}h${future ? '' : ' ago'}`;
  const absDay = Math.round(absHour / 24);
  return `${future ? 'in ' : ''}${absDay}d${future ? '' : ' ago'}`;
}
