import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Spinner } from '@/components/ui/Spinner';
import { usePersonalBests } from '@/hooks/personal-best/usePersonalBests';
import { formatDate } from '@/lib/format';

const TYPE_LABEL: Record<string, string> = {
  HIGHEST_WEIGHT: 'Poids max',
  HIGHEST_REPS: 'Reps max',
  HIGHEST_VOLUME_ONE_SET: 'Volume / série',
  HIGHEST_VOLUME_WORKOUT: 'Volume / séance',
  HIGHEST_DURATION: 'Durée max',
  HIGHEST_DISTANCE: 'Distance max',
  HIGHEST_SPEED: 'Vitesse max',
};

export function AchievementsPage() {
  const { data, isLoading, isError, error } = usePersonalBests();

  return (
    <>
      <PageHeader title="Records personnels" description="Vos meilleurs marques par mouvement." />
      {isLoading ? (
        <Spinner />
      ) : isError ? (
        <Alert tone="danger">{(error as Error).message}</Alert>
      ) : !data || 0 === data.length ? (
        <EmptyState
          title="Aucun record encore"
          description="Termine une séance pour décrocher tes premiers records."
        />
      ) : (
        <div className="space-y-4">
          {data.map((entry) => (
            <Card key={entry.movement.id}>
              <CardHeader>
                <div>
                  <div className="text-(length:--text-base) font-semibold">{entry.movement.label}</div>
                  {entry.movement.mainMuscleSlug && (
                    <div className="text-(length:--text-xs) text-(--color-text-muted)">
                      {entry.movement.mainMuscleSlug}
                    </div>
                  )}
                </div>
              </CardHeader>
              <CardBody>
                <ul className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  {entry.personalBests.map((pb) => (
                    <li
                      key={`${entry.movement.id}-${pb.type}`}
                      className="flex items-center justify-between rounded-(--radius-md) bg-(--color-surface-muted) px-3 py-2"
                    >
                      <div>
                        <Badge tone="primary">{TYPE_LABEL[pb.type] ?? pb.type}</Badge>
                        <div className="mt-1 text-(length:--text-xs) text-(--color-text-muted)">
                          {formatDate(pb.achievedAt)}
                        </div>
                      </div>
                      <span className="text-(length:--text-lg) font-semibold text-(--color-text)">
                        {pb.value}
                      </span>
                    </li>
                  ))}
                </ul>
              </CardBody>
            </Card>
          ))}
        </div>
      )}
    </>
  );
}
