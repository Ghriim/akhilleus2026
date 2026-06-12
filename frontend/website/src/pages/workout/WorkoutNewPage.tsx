import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { PageHeader } from '@/components/ui/PageHeader';
import { usePlanWorkout, useStartEmptyWorkout } from '@/hooks/workout/useWorkouts';
import { fromDatetimeLocalValue } from '@/lib/format';

export function WorkoutNewPage() {
  const navigate = useNavigate();
  const startEmpty = useStartEmptyWorkout();
  const plan = usePlanWorkout();

  const [name, setName] = useState('');
  const [plannedAt, setPlannedAt] = useState('');
  const [error, setError] = useState<string | null>(null);

  return (
    <>
      <PageHeader title="Nouvelle séance" description="Démarre une séance maintenant ou planifie-la." />
      {error && <Alert tone="danger" className="mb-4">{error}</Alert>}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card>
          <CardHeader>
            <h2 className="text-(length:--text-lg) font-semibold">Démarrer maintenant</h2>
          </CardHeader>
          <CardBody>
            <form
              className="space-y-3"
              onSubmit={async (e) => {
                e.preventDefault();
                setError(null);
                try {
                  const w = await startEmpty.mutateAsync(name ? { name } : {});
                  navigate(`/workouts/${w.id}`);
                } catch (err) {
                  setError((err as Error).message);
                }
              }}
            >
              <div>
                <Label htmlFor="name-now">Nom (optionnel)</Label>
                <Input id="name-now" value={name} onChange={(e) => setName(e.target.value)} />
              </div>
              <Button type="submit" isLoading={startEmpty.isPending} className="w-full">
                Démarrer
              </Button>
            </form>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>
            <h2 className="text-(length:--text-lg) font-semibold">Planifier</h2>
          </CardHeader>
          <CardBody>
            <form
              className="space-y-3"
              onSubmit={async (e) => {
                e.preventDefault();
                setError(null);
                if (!plannedAt) {
                  setError('La date est requise.');
                  return;
                }
                try {
                  const w = await plan.mutateAsync({
                    name: name || undefined,
                    plannedAt: fromDatetimeLocalValue(plannedAt),
                  });
                  navigate(`/workouts/${w.id}`);
                } catch (err) {
                  setError((err as Error).message);
                }
              }}
            >
              <div>
                <Label htmlFor="name-plan">Nom (optionnel)</Label>
                <Input id="name-plan" value={name} onChange={(e) => setName(e.target.value)} />
              </div>
              <div>
                <Label htmlFor="plannedAt">Date prévue</Label>
                <Input
                  id="plannedAt"
                  type="datetime-local"
                  value={plannedAt}
                  onChange={(e) => setPlannedAt(e.target.value)}
                  required
                />
              </div>
              <Button type="submit" isLoading={plan.isPending} className="w-full">
                Planifier
              </Button>
            </form>
          </CardBody>
        </Card>
      </div>
    </>
  );
}
