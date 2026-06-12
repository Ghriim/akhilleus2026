import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { useAddExercise } from '@/hooks/workout/useExercises';
import { MovementPicker } from './MovementPicker';

interface AddExerciseFormProps {
  workoutId: string;
  onDone?: () => void;
}

export function AddExerciseForm({ workoutId, onDone }: AddExerciseFormProps) {
  const [movementId, setMovementId] = useState('');
  const [rest, setRest] = useState('60');
  const add = useAddExercise(workoutId);

  return (
    <form
      onSubmit={async (e) => {
        e.preventDefault();
        if (!movementId) return;
        await add.mutateAsync({
          movementId,
          restDurationSeconds: rest ? Number(rest) : undefined,
        });
        setMovementId('');
        setRest('60');
        onDone?.();
      }}
      className="grid grid-cols-1 sm:grid-cols-3 gap-3"
    >
      <div className="sm:col-span-2">
        <MovementPicker value={movementId} onChange={setMovementId} />
      </div>
      <div>
        <Label htmlFor="rest">Repos (s)</Label>
        <Input id="rest" type="number" value={rest} onChange={(e) => setRest(e.target.value)} />
      </div>
      <div className="sm:col-span-3 flex justify-end">
        <Button type="submit" isLoading={add.isPending} disabled={!movementId}>
          Ajouter l'exercice
        </Button>
      </div>
    </form>
  );
}
