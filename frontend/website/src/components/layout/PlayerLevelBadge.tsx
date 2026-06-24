import { useProfile } from '@/hooks/profile/useProfile';

interface PlayerLevelBadgeProps {
  compact?: boolean;
}

/** Placeholder avatar until real avatars exist: initials derived from the display name. */
function initialsOf(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (0 === parts.length) return '?';
  if (1 === parts.length) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

export function PlayerLevelBadge({ compact = false }: PlayerLevelBadgeProps) {
  const { data } = useProfile();
  if (!data) return null;

  const pct =
    data.xpToNextLevel > 0
      ? Math.min(100, Math.round((data.currentXp / data.xpToNextLevel) * 100))
      : 0;
  const initials = initialsOf(data.displayName);
  const title = `${data.displayName} — Niveau ${data.level} — ${data.currentXp} / ${data.xpToNextLevel} XP`;

  if (compact) {
    return (
      <span
        className="grid h-9 w-9 place-items-center bg-(--color-primary-soft) text-(length:--text-sm) font-semibold text-(--color-primary)"
        title={title}
        aria-label={title}
      >
        {initials}
      </span>
    );
  }

  return (
    <div
      className="border border-(--color-border) bg-(--color-surface) p-3 [filter:var(--glow)]"
      title={title}
    >
      <div className="flex items-center gap-3">
        <span
          className="grid h-10 w-10 shrink-0 place-items-center bg-(--color-primary-soft) text-(length:--text-sm) font-semibold text-(--color-primary)"
          aria-hidden="true"
        >
          {initials}
        </span>
        <div className="min-w-0">
          <div className="truncate text-(length:--text-sm) font-semibold text-(--color-text)">
            {data.displayName}
          </div>
          <div className="text-(length:--text-xs) text-(--color-text-muted)">
            Niveau {data.level}
          </div>
        </div>
      </div>
      <div
        className="mt-3 h-1.5 w-full overflow-hidden rounded-(--radius-sm) bg-(--color-surface-muted)"
        role="progressbar"
        aria-valuenow={data.currentXp}
        aria-valuemin={0}
        aria-valuemax={data.xpToNextLevel}
      >
        <div className="h-full bg-(--color-primary) shadow-(--bar-glow)" style={{ width: `${pct}%` }} />
      </div>
      <div className="mt-1.5 text-(length:--text-xs) text-(--color-text-muted)">
        {data.currentXp} / {data.xpToNextLevel} XP
      </div>
    </div>
  );
}
