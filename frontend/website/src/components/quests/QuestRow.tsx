import { Button } from '@/components/ui/Button';
import type { QuestProgressionDataOutput } from '@/api/types';
import { formatNumber } from '@/lib/format';

const STATUS_LABEL: Record<string, string> = {
  IN_PROGRESS: 'En cours',
  CLAIMABLE: 'À réclamer',
  REWARDED: 'Réclamée',
};

interface QuestRowProps {
  quest: QuestProgressionDataOutput;
  onClaim: (progressionId: string) => void;
  isClaiming?: boolean;
  showProgress?: boolean;
}

export function QuestRow({ quest, onClaim, isClaiming = false, showProgress = false }: QuestRowProps) {
  const current = quest.currentValue !== null ? Number(quest.currentValue) : null;
  const target = quest.targetValue !== null ? Number(quest.targetValue) : null;
  const pct =
    current !== null && target !== null && target > 0
      ? Math.min(100, (current / target) * 100)
      : 0;

  const canClaim = 'CLAIMABLE' === quest.status;
  const hasProgress = showProgress && target !== null && target > 0;

  return (
    <li className="rounded-(--radius-sm) bg-(--color-surface-muted) px-3 py-2">
      <div className="flex items-center justify-between gap-2">
        <span className="font-medium text-(--color-text)">{quest.label}</span>
        <span className="flex items-center gap-2">
          <span className="text-(length:--text-sm) text-(--color-text-muted)">
            +{quest.rewardedXp} XP
          </span>
          {canClaim ? (
            <Button size="sm" isLoading={isClaiming} onClick={() => onClaim(quest.id)}>
              Réclamer
            </Button>
          ) : (
            <span className="text-(length:--text-sm) text-(--color-text-subtle)">
              {STATUS_LABEL[quest.status] ?? quest.status}
            </span>
          )}
        </span>
      </div>
      {hasProgress && (
        <>
          <div className="mt-1 flex items-baseline justify-between text-(length:--text-sm) text-(--color-text-muted)">
            <span>{formatNumber(current, 0)}</span>
            <span>/ {formatNumber(target, 0)}</span>
          </div>
          <div className="mt-1 h-2 w-full overflow-hidden rounded-(--radius-sm) bg-(--color-surface)">
            <div className="h-full bg-(--color-primary) shadow-(--bar-glow)" style={{ width: `${pct}%` }} />
          </div>
        </>
      )}
    </li>
  );
}
