import { useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Spinner } from '@/components/ui/Spinner';
import { WorkoutListItem } from '@/components/workout/WorkoutListItem';
import { useWorkoutHistory } from '@/hooks/workout/useWorkouts';

const PER_PAGE = 20;

export function HistoryPage() {
  const [page, setPage] = useState(1);
  const { data, isLoading, isError, error } = useWorkoutHistory(page, PER_PAGE);

  return (
    <>
      <PageHeader title="Historique" description="Séances terminées ou annulées." />
      {isLoading ? (
        <Spinner />
      ) : isError ? (
        <Alert tone="danger">{(error as Error).message}</Alert>
      ) : !data || 0 === data.items.length ? (
        <EmptyState title="Aucune séance pour l'instant" />
      ) : (
        <>
          <div className="space-y-2">
            {data.items.map((w) => (
              <WorkoutListItem key={w.id} workout={w} />
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
