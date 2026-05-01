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
