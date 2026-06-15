import { useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Spinner } from '@/components/ui/Spinner';
import { QuestRow } from '@/components/quests/QuestRow';
import {
  useClaimQuest,
  useDailyQuests,
  useMonthlyQuests,
  useWeeklyQuests,
} from '@/hooks/quests/useQuests';
import { cn } from '@/lib/cn';

type Tab = 'daily' | 'weekly' | 'monthly';

const TABS: { id: Tab; label: string }[] = [
  { id: 'daily', label: 'Quotidiennes' },
  { id: 'weekly', label: 'Hebdomadaires' },
  { id: 'monthly', label: 'Mensuelles' },
];

function readTabFromHash(): Tab {
  const match = /#quests=(daily|weekly|monthly)/.exec(window.location.hash);
  return (match?.[1] as Tab) ?? 'daily';
}

export function QuestWidget() {
  const [tab, setTab] = useState<Tab>(readTabFromHash);

  const daily = useDailyQuests();
  const weekly = useWeeklyQuests();
  const monthly = useMonthlyQuests();
  const claim = useClaimQuest();

  const query = 'daily' === tab ? daily : 'weekly' === tab ? weekly : monthly;

  const selectTab = (next: Tab) => {
    setTab(next);
    window.location.hash = `quests=${next}`;
  };

  return (
    <section className="mb-6">
      <h2 className="mb-3 text-(length:--text-xl) font-(--font-display) font-semibold text-(--color-text)">
        🗡️ Quêtes
      </h2>
      <Card>
        <CardHeader>
          <div className="flex gap-1">
            {TABS.map((t) => (
              <button
                key={t.id}
                type="button"
                className={cn(
                  'rounded-(--radius-md) px-3 py-1 text-(length:--text-sm) font-medium transition-colors',
                  t.id === tab
                    ? 'bg-(--color-primary) text-(--color-primary-foreground)'
                    : 'bg-(--color-surface-muted) text-(--color-text) hover:bg-(--color-border)',
                )}
                onClick={() => selectTab(t.id)}
              >
                {t.label}
              </button>
            ))}
          </div>
        </CardHeader>
        <CardBody>
          {query.isLoading ? (
            <Spinner size="sm" />
          ) : query.isError ? (
            <Alert tone="danger">{(query.error as Error).message}</Alert>
          ) : !query.data || 0 === query.data.length ? (
            <p className="text-(length:--text-sm) text-(--color-text-muted)">
              Aucune quête active pour cette période.
            </p>
          ) : (
            <ul className="space-y-2">
              {query.data.map((quest) => (
                <QuestRow
                  key={quest.id}
                  quest={quest}
                  onClaim={(id) => claim.mutate(id)}
                  isClaiming={claim.isPending && claim.variables === quest.id}
                  showProgress={'daily' === tab}
                />
              ))}
            </ul>
          )}
          {claim.error && (
            <Alert tone="danger" className="mt-2">
              {(claim.error as Error).message}
            </Alert>
          )}
        </CardBody>
      </Card>
    </section>
  );
}
