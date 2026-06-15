import { Alert } from '@/components/ui/Alert';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Spinner } from '@/components/ui/Spinner';
import { QuestRow } from '@/components/quests/QuestRow';
import type { QuestProgressionDataOutput } from '@/api/types';
import { useClaimQuest, useUniqueQuests } from '@/hooks/quests/useQuests';

interface QuestSectionProps {
  title: string;
  quests: QuestProgressionDataOutput[];
  onClaim: (progressionId: string) => void;
  claimingId: string | undefined;
  showProgress?: boolean;
}

function QuestSection({ title, quests, onClaim, claimingId, showProgress = false }: QuestSectionProps) {
  return (
    <section className="mb-6">
      <h2 className="mb-2 text-(length:--text-lg) font-(--font-display) font-semibold text-(--color-text)">
        {title}
      </h2>
      {0 === quests.length ? (
        <p className="text-(length:--text-sm) text-(--color-text-muted)">
          Aucune quête dans cette catégorie.
        </p>
      ) : (
        <ul className="space-y-2">
          {quests.map((quest) => (
            <QuestRow
              key={quest.id}
              quest={quest}
              onClaim={onClaim}
              isClaiming={claimingId === quest.id}
              showProgress={showProgress}
            />
          ))}
        </ul>
      )}
    </section>
  );
}

export function UniqueQuestsPage() {
  const { data, isLoading, isError, error } = useUniqueQuests();
  const claim = useClaimQuest();
  const claimingId = claim.isPending ? claim.variables : undefined;

  const quests = data ?? [];
  const available = quests.filter(
    (q) =>
      ('IN_PROGRESS' === q.status && 'AUTOMATIC' === q.kind) ||
      ('CLAIMABLE' === q.status && 'MANUAL' === q.kind),
  );
  const readyToClaim = quests.filter((q) => 'CLAIMABLE' === q.status && 'AUTOMATIC' === q.kind);
  const completed = quests.filter((q) => 'REWARDED' === q.status);

  return (
    <>
      <PageHeader title="Quêtes uniques" description="Tes quêtes ponctuelles et leurs récompenses." />
      {isLoading ? (
        <Spinner />
      ) : isError ? (
        <Alert tone="danger">{(error as Error).message}</Alert>
      ) : 0 === quests.length ? (
        <EmptyState title="Aucune quête unique" description="Reviens plus tard pour de nouveaux défis." />
      ) : (
        <>
          <QuestSection
            title="Disponibles"
            quests={available}
            onClaim={(id) => claim.mutate(id)}
            claimingId={claimingId}
            showProgress
          />
          <QuestSection
            title="À réclamer"
            quests={readyToClaim}
            onClaim={(id) => claim.mutate(id)}
            claimingId={claimingId}
          />
          <QuestSection
            title="Terminées"
            quests={completed}
            onClaim={(id) => claim.mutate(id)}
            claimingId={claimingId}
          />
          {claim.error && (
            <Alert tone="danger" className="mt-2">
              {(claim.error as Error).message}
            </Alert>
          )}
        </>
      )}
    </>
  );
}
