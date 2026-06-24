import { useProfile } from '@/hooks/profile/useProfile';

interface PlayerLevelBadgeProps {
  compact?: boolean;
}

export function PlayerLevelBadge({ compact = false }: PlayerLevelBadgeProps) {
  const { data } = useProfile();
  if (!data) return null;

  const pct =
    data.xpToNextLevel > 0
      ? Math.min(100, Math.round((data.currentXp / data.xpToNextLevel) * 100))
      : 0;
  const title = `Niveau ${data.level} — ${data.currentXp} / ${data.xpToNextLevel} XP`;

  if (compact) {
    return (
      <span
        className="inline-flex h-8 min-w-8 items-center justify-center rounded-(--radius-md) bg-(--color-primary-soft) px-1 text-(length:--text-xs) font-semibold text-(--color-primary)"
        title={title}
      >
        {data.level}
      </span>
    );
  }

  return (
    <div className="flex min-w-0 flex-col gap-1" title={title}>
      <span className="whitespace-nowrap text-(length:--text-xs) text-(--color-text-muted)">
        <span className="font-semibold text-(--color-text)">Niv. {data.level}</span>
        {' • '}
        {data.currentXp}/{data.xpToNextLevel} XP
      </span>
      <div
        className="h-1.5 w-full overflow-hidden rounded-(--radius-sm) bg-(--color-surface-muted)"
        role="progressbar"
        aria-valuenow={data.currentXp}
        aria-valuemin={0}
        aria-valuemax={data.xpToNextLevel}
      >
        <div className="h-full bg-(--color-primary)" style={{ width: `${pct}%` }} />
      </div>
    </div>
  );
}
