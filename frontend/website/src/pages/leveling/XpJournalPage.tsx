import { useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Spinner } from '@/components/ui/Spinner';
import { useXpJournal } from '@/hooks/leveling/useLeveling';
import { formatDate } from '@/lib/format';

const PER_PAGE = 20;

function LockIcon() {
  return (
    <svg
      viewBox="0 0 24 24"
      width="14"
      height="14"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <rect x="4" y="11" width="16" height="9" rx="2" />
      <path d="M8 11V7a4 4 0 0 1 8 0v4" />
    </svg>
  );
}

export function XpJournalPage() {
  const [page, setPage] = useState(1);
  const { data, isLoading, isError, error } = useXpJournal(page, PER_PAGE);

  return (
    <>
      <PageHeader title="Journal XP" description="L'expérience que tu as gagnée." />
      {isLoading ? (
        <Spinner />
      ) : isError ? (
        <Alert tone="danger">{(error as Error).message}</Alert>
      ) : !data || 0 === data.items.length ? (
        <EmptyState title="Aucune expérience pour l'instant" />
      ) : (
        <>
          <div className="space-y-2">
            {data.items.map((entry) => (
              <div
                key={entry.id}
                className="flex items-center justify-between gap-3 rounded-(--radius-md) border border-(--color-border) bg-(--color-surface) px-4 py-3"
              >
                <div className="min-w-0">
                  <div className="flex items-center gap-2 text-(--color-text)">
                    <span className="truncate">{entry.label}</span>
                    {entry.isLocked ? (
                      <span
                        className="text-(--color-text-muted)"
                        title="Expérience verrouillée (déjà intégrée à ton niveau)"
                      >
                        <LockIcon />
                      </span>
                    ) : null}
                  </div>
                  <div className="text-(length:--text-sm) text-(--color-text-muted)">
                    {formatDate(entry.earnedAt)}
                  </div>
                </div>
                <Badge tone="primary">+{entry.amount} XP</Badge>
              </div>
            ))}
          </div>
          <Pagination
            page={data.page}
            perPage={data.perPage}
            total={data.totalCount}
            onPageChange={setPage}
          />
        </>
      )}
    </>
  );
}
