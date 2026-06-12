import { useState } from 'react';
import type { ExerciseMovementDataOutput } from '@/api/types';
import { Button } from '@/components/ui/Button';
import { useAddSet } from '@/hooks/workout/useSets';
import {
  achievedPayload,
  plannedPayload,
  SetForm,
  type SetFormMode,
} from './SetForm';

interface AddSetButtonProps {
  workoutId: string;
  exerciseId: string;
  movement: ExerciseMovementDataOutput;
  mode: SetFormMode;
}

export function AddSetButton({ workoutId, exerciseId, movement, mode }: AddSetButtonProps) {
  const [open, setOpen] = useState(false);
  const addSet = useAddSet(workoutId);

  if (!open) {
    return (
      <Button variant="secondary" size="sm" onClick={() => setOpen(true)}>
        + Ajouter une série
      </Button>
    );
  }

  return (
    <div className="rounded-(--radius-md) border border-(--color-border) bg-(--color-surface-muted) p-3">
      <SetForm
        movement={movement}
        mode={mode}
        submitLabel="Ajouter"
        isLoading={addSet.isPending}
        onCancel={() => setOpen(false)}
        onSubmit={async (values) => {
          const input = 'planned' === mode ? plannedPayload(values) : achievedPayload(values);
          await addSet.mutateAsync({ exerciseId, input });
          setOpen(false);
        }}
      />
    </div>
  );
}
