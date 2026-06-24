import { useState } from 'react';
import type { WeightEntryDataOutput } from '@/api/types';
import { PencilIcon, PlusIcon } from '@/components/icons';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { IconButton } from '@/components/ui/IconButton';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Modal } from '@/components/ui/Modal';
import { Spinner } from '@/components/ui/Spinner';
import { useProfile } from '@/hooks/profile/useProfile';
import {
  useLogWeight,
  useUpdatePlayerWeightTarget,
  useUpdateWeight,
  useWeightRange,
} from '@/hooks/tracking/useTracking';
import { formatNumber } from '@/lib/format';

interface WeightCardProps {
  today: string;
}

export function WeightCard({ today }: WeightCardProps) {
  const { data, isLoading } = useWeightRange(today, today);
  const { data: profile } = useProfile();
  const logWeight = useLogWeight();
  const updateWeight = useUpdateWeight();
  const updateTarget = useUpdatePlayerWeightTarget();
  const record: WeightEntryDataOutput | undefined = data?.[0];
  const currentKg = record ? record.valueGrams / 1000 : null;

  const targetGrams = profile?.targetWeightGrams ?? null;
  const targetKg = null !== targetGrams ? targetGrams / 1000 : null;

  const [open, setOpen] = useState(false);
  const [value, setValue] = useState('');

  const [targetOpen, setTargetOpen] = useState(false);
  const [targetValue, setTargetValue] = useState('');

  const openModal = () => {
    setValue(null !== currentKg ? String(currentKg) : '');
    setOpen(true);
  };

  const openTarget = () => {
    setTargetValue(null !== targetKg ? String(targetKg) : '');
    setTargetOpen(true);
  };

  const save = async () => {
    const kg = Number(value);
    if ('' === value.trim() || Number.isNaN(kg)) return;
    const valueGrams = Math.round(kg * 1000);
    const loggedAt = new Date().toISOString();
    if (record) {
      await updateWeight.mutateAsync({ id: record.id, loggedAt, valueGrams });
    } else {
      await logWeight.mutateAsync({ loggedAt, valueGrams });
    }
    setOpen(false);
  };

  const saveTarget = async () => {
    const kg = Number(targetValue);
    if ('' === targetValue.trim() || Number.isNaN(kg) || kg <= 0) return;
    await updateTarget.mutateAsync(Math.round(kg * 1000));
    setTargetOpen(false);
  };

  const mutationError = (logWeight.error as Error | null) ?? (updateWeight.error as Error | null);

  return (
    <Card>
      <CardHeader>
        <span className="font-(--font-display) font-semibold text-(--color-text)">⚖️ Poids</span>
        <span className="flex items-center gap-1">
          <IconButton label="Changer l'objectif" onClick={openTarget}>
            <PencilIcon />
          </IconButton>
          <IconButton
            label={record ? 'Modifier la valeur' : 'Ajouter une valeur'}
            onClick={openModal}
          >
            <PlusIcon />
          </IconButton>
        </span>
      </CardHeader>
      <CardBody>
        {isLoading ? (
          <Spinner size="sm" />
        ) : null !== targetKg ? (
          <div className="flex items-baseline justify-between text-(length:--text-sm) text-(--color-text-muted)">
            <span className="text-(length:--text-2xl) font-semibold text-(--color-text)">
              {null !== currentKg ? `${formatNumber(currentKg, 1)} kg` : '—'}
            </span>
            <span>/ {formatNumber(targetKg, 1)} kg</span>
          </div>
        ) : null !== currentKg ? (
          <div className="text-(length:--text-2xl) font-semibold text-(--color-text)">
            {formatNumber(currentKg, 1)} kg
          </div>
        ) : (
          <div className="text-(length:--text-sm) text-(--color-text-muted)">
            Aucun poids enregistré.
          </div>
        )}
      </CardBody>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title="⚖️ Poids"
        footer={
          <div className="flex justify-end gap-2">
            <Button variant="ghost" onClick={() => setOpen(false)}>
              Annuler
            </Button>
            <Button isLoading={logWeight.isPending || updateWeight.isPending} onClick={save}>
              Enregistrer
            </Button>
          </div>
        }
      >
        <Label>Poids (kg)</Label>
        <Input
          type="number"
          min="0"
          step="0.1"
          placeholder="Poids (kg)"
          value={value}
          onChange={(e) => setValue(e.target.value)}
        />
        {mutationError && (
          <Alert tone="danger" className="mt-2">
            {mutationError.message}
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
        <Label>Objectif (kg)</Label>
        <Input
          type="number"
          min="0"
          step="0.1"
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
