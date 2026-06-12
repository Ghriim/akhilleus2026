import type { ExerciseDetailsDataOutput, WorkoutStatus } from '@/api/types';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { useRemoveExercise } from '@/hooks/workout/useExercises';
import { formatDurationSeconds } from '@/lib/format';
import { AddSetButton } from './AddSetButton';
import { MovementMedia } from './MovementMedia';
import { SetRow } from './SetRow';
import type { SetFormMode } from './SetForm';

interface ExerciseCardProps {
  workoutId: string;
  exercise: ExerciseDetailsDataOutput;
  workoutStatus: WorkoutStatus;
}

export function ExerciseCard({ workoutId, exercise, workoutStatus }: ExerciseCardProps) {
  const remove = useRemoveExercise(workoutId);
  const mode: SetFormMode | null =
    'PLANNED' === workoutStatus ? 'planned' : 'IN_PROGRESS' === workoutStatus ? 'achieved' : null;
  const editable = mode !== null;

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center gap-3">
          <Badge tone="primary">#{exercise.position}</Badge>
          <div>
            <div className="text-(length:--text-base) font-semibold">{exercise.movement.label}</div>
            <div className="text-(length:--text-xs) text-(--color-text-muted)">
              Repos : {formatDurationSeconds(exercise.restDurationSeconds)}
            </div>
          </div>
        </div>
        {editable && (
          <Button
            variant="ghost"
            size="sm"
            onClick={() => {
              if (window.confirm("Supprimer l'exercice ?")) {
                remove.mutate(exercise.id);
              }
            }}
          >
            Supprimer
          </Button>
        )}
      </CardHeader>
      <CardBody>
        <div className="space-y-3">
          <MovementMedia movement={exercise.movement} />
          {0 === exercise.sets.length ? (
            <div className="text-(length:--text-sm) text-(--color-text-muted)">
              Aucune série encore.
            </div>
          ) : (
            <div className="space-y-2">
              {exercise.sets.map((s) => (
                <SetRow
                  key={s.id}
                  workoutId={workoutId}
                  set={s}
                  movement={exercise.movement}
                  mode={mode ?? 'achieved'}
                  editable={editable}
                />
              ))}
            </div>
          )}
          {editable && mode && (
            <AddSetButton
              workoutId={workoutId}
              exerciseId={exercise.id}
              movement={exercise.movement}
              mode={mode}
            />
          )}
        </div>
      </CardBody>
    </Card>
  );
}
