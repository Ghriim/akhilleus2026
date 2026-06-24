import { useState } from 'react';
import { ListIcon, PencilIcon, PlusIcon } from '@/components/icons';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { IconButton } from '@/components/ui/IconButton';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Modal } from '@/components/ui/Modal';
import { Spinner } from '@/components/ui/Spinner';
import {
  useAddHydrationEntry,
  useDeleteHydrationEntry,
  useTodayHydration,
  useUpdateHydrationTodayTarget,
} from '@/hooks/tracking/useTracking';
import { formatNumber } from '@/lib/format';

function timeOf(iso: string): string {
  const d = new Date(iso);
  return Number.isNaN(d.getTime())
    ? '—'
    : d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

export function HydrationCard() {
  const { data, isLoading, isError, error } = useTodayHydration();
  const addEntry = useAddHydrationEntry();
  const deleteEntry = useDeleteHydrationEntry();
  const updateTarget = useUpdateHydrationTodayTarget();

  const [addOpen, setAddOpen] = useState(false);
  const [targetOpen, setTargetOpen] = useState(false);
  const [listOpen, setListOpen] = useState(false);
  const [toAdd, setToAdd] = useState('250');
  const [targetValue, setTargetValue] = useState('');

  const openTarget = () => {
    setTargetValue(data ? String(data.targetMl) : '');
    setTargetOpen(true);
  };
  const openAdd = () => {
    setToAdd('250');
    setAddOpen(true);
  };

  const add = async () => {
    const valueMl = Number(toAdd);
    if ('' === toAdd.trim() || Number.isNaN(valueMl)) return;
    await addEntry.mutateAsync({ loggedAt: new Date().toISOString(), valueMl });
    setAddOpen(false);
  };

  const saveTarget = async () => {
    const targetMl = Number(targetValue);
    if ('' === targetValue.trim() || Number.isNaN(targetMl)) return;
    await updateTarget.mutateAsync(targetMl);
    setTargetOpen(false);
  };

  const pct =
    data && data.targetMl > 0 ? Math.min(100, (data.amountConsumedMl / data.targetMl) * 100) : 0;

  return (
    <Card>
      <CardHeader>
        <span className="font-(--font-display) font-semibold text-(--color-text)">
          Hydratation
        </span>
        {data && (
          <span className="flex items-center gap-1">
            <IconButton label="Changer l'objectif" onClick={openTarget}>
              <PencilIcon />
            </IconButton>
            <IconButton label="Voir les entrées" onClick={() => setListOpen(true)}>
              <ListIcon />
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
                {formatNumber(data.amountConsumedMl, 0)} ml
              </span>
              <span>/ {formatNumber(data.targetMl, 0)} ml</span>
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
        title="💧 Ajouter de l'eau"
        footer={
          <div className="flex justify-end gap-2">
            <Button variant="ghost" onClick={() => setAddOpen(false)}>
              Annuler
            </Button>
            <Button isLoading={addEntry.isPending} onClick={add}>
              Ajouter
            </Button>
          </div>
        }
      >
        <Label>Quantité (ml)</Label>
        <Input
          type="number"
          min="1"
          placeholder="ml"
          value={toAdd}
          onChange={(e) => setToAdd(e.target.value)}
        />
        {addEntry.error && (
          <Alert tone="danger" className="mt-2">
            {(addEntry.error as Error).message}
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
        <Label>Objectif (ml)</Label>
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

      <Modal open={listOpen} onClose={() => setListOpen(false)} title="💧 Entrées du jour">
        {data && data.entries.length > 0 ? (
          <ul className="space-y-1">
            {data.entries.map((entry) => (
              <li
                key={entry.id}
                className="flex items-center justify-between rounded-(--radius-sm) bg-(--color-surface-muted) px-2 py-1 text-(length:--text-sm) text-(--color-text)"
              >
                <span>
                  <span className="text-(--color-text-muted)">{timeOf(entry.loggedAt)}</span>
                  {' · '}
                  {formatNumber(entry.valueMl, 0)} ml
                </span>
                <Button
                  size="sm"
                  variant="ghost"
                  isLoading={deleteEntry.isPending}
                  onClick={() => deleteEntry.mutate(entry.id)}
                >
                  Supprimer
                </Button>
              </li>
            ))}
          </ul>
        ) : (
          <p className="text-(length:--text-sm) text-(--color-text-muted)">
            Aucune entrée pour aujourd'hui.
          </p>
        )}
        {deleteEntry.error && (
          <Alert tone="danger" className="mt-2">
            {(deleteEntry.error as Error).message}
          </Alert>
        )}
      </Modal>
    </Card>
  );
}
