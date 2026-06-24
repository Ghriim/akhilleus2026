import { useState } from 'react';
import { PencilIcon, PlusIcon } from '@/components/icons';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { IconButton } from '@/components/ui/IconButton';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Modal } from '@/components/ui/Modal';
import { Spinner } from '@/components/ui/Spinner';
import {
  useTodaySteps,
  useUpdateStepsTodayTarget,
  useUpsertSteps,
} from '@/hooks/tracking/useTracking';
import { formatNumber } from '@/lib/format';

export function StepsCard() {
  const { data, isLoading, isError, error } = useTodaySteps();
  const upsert = useUpsertSteps();
  const updateTarget = useUpdateStepsTodayTarget();

  const [addOpen, setAddOpen] = useState(false);
  const [targetOpen, setTargetOpen] = useState(false);
  const [value, setValue] = useState('');
  const [targetValue, setTargetValue] = useState('');

  const openAdd = () => {
    setValue(data ? String(data.count) : '');
    setAddOpen(true);
  };
  const openTarget = () => {
    setTargetValue(data ? String(data.target) : '');
    setTargetOpen(true);
  };

  const save = async () => {
    const count = Number(value);
    if (!data || '' === value.trim() || Number.isNaN(count)) return;
    await upsert.mutateAsync({ date: data.date.slice(0, 10), count });
    setAddOpen(false);
  };

  const saveTarget = async () => {
    const target = Number(targetValue);
    if ('' === targetValue.trim() || Number.isNaN(target)) return;
    await updateTarget.mutateAsync(target);
    setTargetOpen(false);
  };

  const pct = data && data.target > 0 ? Math.min(100, (data.count / data.target) * 100) : 0;

  return (
    <Card>
      <CardHeader>
        <span className="font-(--font-display) font-semibold text-(--color-text)">Pas</span>
        {data && (
          <span className="flex items-center gap-1">
            <IconButton label="Changer l'objectif" onClick={openTarget}>
              <PencilIcon />
            </IconButton>
            <IconButton label="Ajouter une valeur" onClick={openAdd}>
              <PlusIcon />
            </IconButton>
          </span>
        )}
      </CardHeader>
      <CardBody>
        {isLoading ? (
          <Spinner size="sm" />
        ) : isError ? (
          <Alert tone="danger">{(error as Error).message}</Alert>
        ) : data ? (
          <>
            <div className="flex items-baseline justify-between text-(length:--text-sm) text-(--color-text-muted)">
              <span className="text-(length:--text-2xl) font-semibold text-(--color-text)">
                {formatNumber(data.count, 0)}
              </span>
              <span>/ {formatNumber(data.target, 0)} pas</span>
            </div>
            <div className="mt-2 h-2 w-full overflow-hidden rounded-(--radius-sm) bg-(--color-surface-muted)">
              <div className="h-full bg-(--color-primary) shadow-(--bar-glow)" style={{ width: `${pct}%` }} />
            </div>
          </>
        ) : null}
      </CardBody>

      <Modal
        open={addOpen}
        onClose={() => setAddOpen(false)}
        title="👣 Pas du jour"
        footer={
          <div className="flex justify-end gap-2">
            <Button variant="ghost" onClick={() => setAddOpen(false)}>
              Annuler
            </Button>
            <Button isLoading={upsert.isPending} onClick={save}>
              Enregistrer
            </Button>
          </div>
        }
      >
        <Label>Nombre de pas</Label>
        <Input
          type="number"
          min="0"
          placeholder="Nombre de pas"
          value={value}
          onChange={(e) => setValue(e.target.value)}
        />
        {upsert.error && (
          <Alert tone="danger" className="mt-2">
            {(upsert.error as Error).message}
          </Alert>
        )}
      </Modal>

      <Modal
        open={targetOpen}
        onClose={() => setTargetOpen(false)}
        title="Changer l'objectif"
        footer={
          <div className="flex justify-end gap-2">
            <Button variant="ghost" onClick={() => setTargetOpen(false)}>
              Annuler
            </Button>
            <Button isLoading={updateTarget.isPending} onClick={saveTarget}>
              Enregistrer
            </Button>
          </div>
        }
      >
        <Label>Objectif (pas)</Label>
        <Input
          type="number"
          min="1"
          value={targetValue}
          onChange={(e) => setTargetValue(e.target.value)}
        />
        {updateTarget.error && (
          <Alert tone="danger" className="mt-2">
            {(updateTarget.error as Error).message}
          </Alert>
        )}
      </Modal>
    </Card>
  );
}
