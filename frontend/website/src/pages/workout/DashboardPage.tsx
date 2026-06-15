import { useNavigate } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Spinner } from '@/components/ui/Spinner';
import { QuestWidget } from '@/components/quests/QuestWidget';
import { TrackingWidget } from '@/components/tracking/TrackingWidget';
import { WorkoutListItem } from '@/components/workout/WorkoutListItem';
import { useStartEmptyWorkout, useUpcomingWorkouts } from '@/hooks/workout/useWorkouts';

export function DashboardPage() {
  const navigate = useNavigate();
  const { data, isLoading, isError, error } = useUpcomingWorkouts();
  const startEmpty = useStartEmptyWorkout();

  const handleStart = async () => {
    const w = await startEmpty.mutateAsync({});
    navigate(`/workouts/${w.id}`);
  };

  return (
    <>
      <PageHeader
        title="Tableau de bord"
        description="Vos prochaines séances et un raccourci pour démarrer."
        actions={
          <>
            <Button variant="secondary" onClick={() => navigate('/workouts/new')}>
              Planifier une séance
            </Button>
            <Button onClick={handleStart} isLoading={startEmpty.isPending}>
              Démarrer maintenant
            </Button>
          </>
        }
      />
      <QuestWidget />
      <TrackingWidget />
      {isLoading ? (
        <Spinner />
      ) : isError ? (
        <Alert tone="danger">{(error as Error).message}</Alert>
      ) : !data || 0 === data.length ? (
        <EmptyState
          title="Aucune séance à venir"
          description="Planifie-en une ou démarre une séance vide."
        />
      ) : (
        <div className="space-y-2">
          {data.map((w) => (
            <WorkoutListItem key={w.id} workout={w} />
          ))}
        </div>
      )}
    </>
  );
}
