import { useState } from 'react';
import type { ExerciseMovementDataOutput, ExerciseSetDataOutput } from '@/api/types';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import {
  achievedPayload,
  plannedPayload,
  SetForm,
  type SetFormMode,
  valuesFromSet,
} from './SetForm';
import { useRemoveSet, useUpdateAchievedSet, useUpdatePlannedSet } from '@/hooks/workout/useSets';
import { formatNumber } from '@/lib/format';

interface SetRowProps {
  workoutId: string;
  set: ExerciseSetDataOutput;
  movement: ExerciseMovementDataOutput;
  mode: SetFormMode;
  editable: boolean;
}

export function SetRow({ workoutId, set, movement, mode, editable }: SetRowProps) {
  const [editing, setEditing] = useState(false);
  const updatePlanned = useUpdatePlannedSet(workoutId);
  const updateAchieved = useUpdateAchievedSet(workoutId);
  const removeSet = useRemoveSet(workoutId);

  if (editing) {
    return (
      <div className="rounded-(--radius-md) border border-(--color-border) bg-(--color-surface-muted) p-3">
        <SetForm
          movement={movement}
          mode={mode}
          initial={valuesFromSet(set, mode)}
          submitLabel="Enregistrer"
          isLoading={updatePlanned.isPending || updateAchieved.isPending}
          onCancel={() => setEditing(false)}
          onSubmit={async (values) => {
            if ('planned' === mode) {
              await updatePlanned.mutateAsync({ id: set.id, input: plannedPayload(values) });
            } else {
              await updateAchieved.mutateAsync({ id: set.id, input: achievedPayload(values) });
            }
            setEditing(false);
          }}
        />
      </div>
    );
  }

  return (
    <div className="flex items-center justify-between gap-3 rounded-(--radius-md) border border-(--color-border) bg-(--color-surface) px-3 py-2">
      <div className="flex items-center gap-3 text-(length:--text-sm) text-(--color-text)">
        <span className="font-mono text-(--color-text-muted)">#{set.position}</span>
        <SetSummary set={set} movement={movement} mode={mode} />
        {set.isComplete && <Badge tone="success">Complet</Badge>}
      </div>
      {editable && (
        <div className="flex gap-1">
          <Button size="sm" variant="ghost" onClick={() => setEditing(true)}>
            Modifier
          </Button>
          <Button
            size="sm"
            variant="ghost"
            onClick={() => {
              if (window.confirm('Supprimer cette série ?')) {
                removeSet.mutate(set.id);
              }
            }}
          >
            Supprimer
          </Button>
        </div>
      )}
    </div>
  );
}

function SetSummary({
  set,
  movement,
  mode,
}: {
  set: ExerciseSetDataOutput;
  movement: ExerciseMovementDataOutput;
  mode: SetFormMode;
}) {
  const parts: string[] = [];
  const pick = (planned: number | string | null, achieved: number | string | null) =>
    'planned' === mode ? planned : achieved;

  if (movement.tracksRepetitions) {
    const v = pick(set.plannedReps, set.achievedReps);
    parts.push(`${formatNumber(v, 0)} rép.`);
  }
  if (movement.tracksWeight) {
    const v = pick(set.plannedWeight, set.achievedWeight);
    parts.push(`${formatNumber(v)} kg`);
  }
  if (movement.tracksDuration) {
    const v = pick(set.plannedDurationSeconds, set.achievedDurationSeconds);
    parts.push(`${formatNumber(v, 0)} s`);
  }
  if (movement.tracksDistance) {
    const v = pick(set.plannedDistanceMeters, set.achievedDistanceMeters);
    parts.push(`${formatNumber(v)} m`);
  }
  if (movement.tracksInclinePercent) {
    const v = pick(set.plannedInclinePercent, set.achievedInclinePercent);
    parts.push(`${formatNumber(v)} %`);
  }
  if (movement.tracksInclineMeters) {
    const v = pick(set.plannedInclineMeters, set.achievedInclineMeters);
    parts.push(`${formatNumber(v)} m D+`);
  }
  return <span className="text-(--color-text-muted)">{parts.join(' · ') || '—'}</span>;
}
