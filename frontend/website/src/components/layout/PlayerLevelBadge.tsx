import { useProfile } from '@/hooks/profile/useProfile';

export function PlayerLevelBadge() {
  const { data } = useProfile();
  if (!data) return null;

  const pct =
    data.xpToNextLevel > 0
      ? Math.min(100, Math.round((data.currentXp / data.xpToNextLevel) * 100))
      : 0;

  return (
    <div
      className="flex min-w-0 flex-col gap-1"
      title={`Niveau ${data.level} — ${data.currentXp} / ${data.xpToNextLevel} XP`}
    >
      <span className="whitespace-nowrap text-(length:--text-xs) text-(--color-text-muted)">
        <span className="font-semibold text-(--color-text)">Niv. {data.level}</span>
        {' • '}
        {data.currentXp}/{data.xpToNextLevel} XP
      </span>
      <div
        className="h-1.5 w-28 overflow-hidden rounded-(--radius-sm) bg-(--color-surface-muted)"
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
