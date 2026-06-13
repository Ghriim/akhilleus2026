import { useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
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

  const [toAdd, setToAdd] = useState('250');
  const [editingTarget, setEditingTarget] = useState(false);
  const [targetValue, setTargetValue] = useState('');

  const mutationError =
    (addEntry.error as Error | null) ??
    (deleteEntry.error as Error | null) ??
    (updateTarget.error as Error | null);

  const add = async () => {
    const valueMl = Number(toAdd);
    if ('' === toAdd.trim() || Number.isNaN(valueMl)) return;
    await addEntry.mutateAsync({ loggedAt: new Date().toISOString(), valueMl });
  };

  const saveTarget = async () => {
    const targetMl = Number(targetValue);
    if ('' === targetValue.trim() || Number.isNaN(targetMl)) return;
    await updateTarget.mutateAsync(targetMl);
    setEditingTarget(false);
  };

  const pct = data && data.targetMl > 0 ? Math.min(100, (data.amountConsumedMl / data.targetMl) * 100) : 0;

  return (
    <Card>
      <CardHeader>
        <span className="font-(--font-display) font-semibold text-(--color-text)">💧 Hydratation</span>
        {data &&
          (editingTarget ? (
            <span className="flex items-center gap-1">
              <Input
                type="number"
                min="1"
                className="w-24"
                value={targetValue}
                onChange={(e) => setTargetValue(e.target.value)}
              />
              <Button size="sm" isLoading={updateTarget.isPending} onClick={saveTarget}>
                OK
              </Button>
              <Button size="sm" variant="ghost" onClick={() => setEditingTarget(false)}>
                Annuler
              </Button>
            </span>
          ) : (
            <button
              type="button"
              className="text-(length:--text-sm) text-(--color-text-muted) hover:text-(--color-primary)"
              onClick={() => {
                setTargetValue(String(data.targetMl));
                setEditingTarget(true);
              }}
            >
              Objectif&nbsp;: {formatNumber(data.targetMl, 0)} ml ✎
            </button>
          ))}
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
              <div className="h-full bg-(--color-primary)" style={{ width: `${pct}%` }} />
            </div>

            <div className="mt-3 flex items-end gap-2">
              <div className="flex-1">
                <Input
                  type="number"
                  min="1"
                  placeholder="ml"
                  value={toAdd}
                  onChange={(e) => setToAdd(e.target.value)}
                />
              </div>
              <Button size="sm" isLoading={addEntry.isPending} onClick={add}>
                + Ajouter
              </Button>
            </div>

            {data.entries.length > 0 && (
              <ul className="mt-3 space-y-1">
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
                      onClick={() => deleteEntry.mutate(entry.id)}
                    >
                      Supprimer
                    </Button>
                  </li>
                ))}
              </ul>
            )}

            {mutationError && (
              <Alert tone="danger" className="mt-2">
                {mutationError.message}
              </Alert>
            )}
          </>
        ) : null}
      </CardBody>
    </Card>
  );
}
