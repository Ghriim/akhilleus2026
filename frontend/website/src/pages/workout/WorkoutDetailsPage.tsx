import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Modal } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { Spinner } from '@/components/ui/Spinner';
import { AddExerciseForm } from '@/components/workout/AddExerciseForm';
import { ExerciseCard } from '@/components/workout/ExerciseCard';
import { WorkoutStatusBadge } from '@/components/workout/WorkoutStatusBadge';
import {
  useCancelWorkout,
  useFinishWorkout,
  useStartPlannedWorkout,
  useWorkoutDetails,
} from '@/hooks/workout/useWorkouts';
import { formatDateTime, formatDurationSeconds, formatNumber } from '@/lib/format';
import type { PersonalBestSummaryDataOutput } from '@/api/types';

export function WorkoutDetailsPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { data, isLoading, isError, error } = useWorkoutDetails(id);
  const startPlanned = useStartPlannedWorkout();
  const finish = useFinishWorkout();
  const cancel = useCancelWorkout();
  const [showAddExercise, setShowAddExercise] = useState(false);
  const [newPbs, setNewPbs] = useState<PersonalBestSummaryDataOutput[] | null>(null);

  if (isLoading) return <Spinner />;
  if (isError) return <Alert tone="danger">{(error as Error).message}</Alert>;
  if (!data) return <EmptyState title="Séance introuvable" />;

  const refDate = data.dateEnd ?? data.dateStart ?? data.plannedAt;
  const editable = 'PLANNED' === data.status || 'IN_PROGRESS' === data.status;

  return (
    <>
      <PageHeader
        title={data.name || 'Séance'}
        description={formatDateTime(refDate)}
        actions={
          <>
            {'PLANNED' === data.status && (
              <Button
                onClick={async () => {
                  await startPlanned.mutateAsync(data.id);
                }}
                isLoading={startPlanned.isPending}
              >
                Démarrer
              </Button>
            )}
            {'IN_PROGRESS' === data.status && (
              <Button
                onClick={async () => {
                  const res = await finish.mutateAsync(data.id);
                  setNewPbs(res.newPersonalBests);
                }}
                isLoading={finish.isPending}
              >
                Terminer
              </Button>
            )}
            {editable && (
              <Button
                variant="secondary"
                onClick={async () => {
                  if (window.confirm('Annuler la séance ?')) {
                    await cancel.mutateAsync(data.id);
                  }
                }}
                isLoading={cancel.isPending}
              >
                Annuler
              </Button>
            )}
            <Button variant="ghost" onClick={() => navigate(-1)}>
              Retour
            </Button>
          </>
        }
      />

      <div className="flex flex-wrap items-center gap-2 mb-4">
        <WorkoutStatusBadge status={data.status} />
        {data.status === 'COMPLETED' && (
          <>
            <Badge tone="neutral">Durée : {formatDurationSeconds(data.duration)}</Badge>
            <Badge tone="neutral">Volume : {formatNumber(data.volume)}</Badge>
            <Badge tone="neutral">Distance : {formatNumber(data.distance)}</Badge>
            <Badge tone="neutral">D+ : {formatNumber(data.inclineMeters)}</Badge>
          </>
        )}
      </div>

      {0 === data.exercises.length ? (
        <Card>
          <CardBody>
            <EmptyState
              title="Aucun exercice"
              description={editable ? 'Ajoute un mouvement pour commencer.' : undefined}
              action={
                editable ? (
                  <Button onClick={() => setShowAddExercise(true)}>Ajouter un exercice</Button>
                ) : undefined
              }
            />
          </CardBody>
        </Card>
      ) : (
        <div className="space-y-4">
          {data.exercises.map((ex) => (
            <ExerciseCard
              key={ex.id}
              workoutId={data.id}
              exercise={ex}
              workoutStatus={data.status}
            />
          ))}
          {editable && (
            <div className="flex justify-center">
              <Button variant="secondary" onClick={() => setShowAddExercise(true)}>
                + Ajouter un exercice
              </Button>
            </div>
          )}
        </div>
      )}

      <Modal
        open={showAddExercise}
        onClose={() => setShowAddExercise(false)}
        title="Ajouter un exercice"
      >
        <AddExerciseForm workoutId={data.id} onDone={() => setShowAddExercise(false)} />
      </Modal>

      <Modal
        open={newPbs !== null}
        onClose={() => setNewPbs(null)}
        title={newPbs && newPbs.length > 0 ? 'Bravo, nouveaux records !' : 'Séance terminée'}
        footer={
          <div className="flex justify-end">
            <Button onClick={() => setNewPbs(null)}>OK</Button>
          </div>
        }
      >
        {newPbs && newPbs.length > 0 ? (
          <ul className="space-y-2">
            {newPbs.map((pb) => (
              <li key={`${pb.movementId}-${pb.type}`} className="flex justify-between gap-2 text-(length:--text-sm)">
                <span>
                  <span className="font-semibold">{pb.movementLabel}</span> · {pb.type}
                </span>
                <span className="text-(--color-success) font-medium">{pb.value}</span>
              </li>
            ))}
          </ul>
        ) : (
          <p className="text-(length:--text-sm) text-(--color-text-muted)">Aucun nouveau record cette fois.</p>
        )}
      </Modal>
    </>
  );
}
