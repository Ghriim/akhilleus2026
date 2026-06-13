import { useEffect, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
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

  const [value, setValue] = useState('');
  const [editingTarget, setEditingTarget] = useState(false);
  const [targetValue, setTargetValue] = useState('');

  useEffect(() => {
    setValue(data ? String(data.count) : '');
  }, [data]);

  const mutationError =
    (upsert.error as Error | null) ?? (updateTarget.error as Error | null);

  const save = async () => {
    const count = Number(value);
    if (!data || '' === value.trim() || Number.isNaN(count)) return;
    await upsert.mutateAsync({ date: data.date.slice(0, 10), count });
  };

  const saveTarget = async () => {
    const target = Number(targetValue);
    if ('' === targetValue.trim() || Number.isNaN(target)) return;
    await updateTarget.mutateAsync(target);
    setEditingTarget(false);
  };

  const pct = data && data.target > 0 ? Math.min(100, (data.count / data.target) * 100) : 0;

  return (
    <Card>
      <CardHeader>
        <span className="font-(--font-display) font-semibold text-(--color-text)">👣 Pas</span>
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
                setTargetValue(String(data.target));
                setEditingTarget(true);
              }}
            >
              Objectif&nbsp;: {formatNumber(data.target, 0)} pas ✎
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
                {formatNumber(data.count, 0)}
              </span>
              <span>/ {formatNumber(data.target, 0)} pas</span>
            </div>
            <div className="mt-2 h-2 w-full overflow-hidden rounded-(--radius-sm) bg-(--color-surface-muted)">
              <div className="h-full bg-(--color-primary)" style={{ width: `${pct}%` }} />
            </div>

            <div className="mt-3 flex items-end gap-2">
              <div className="flex-1">
                <Input
                  type="number"
                  min="0"
                  placeholder="Nombre de pas"
                  value={value}
                  onChange={(e) => setValue(e.target.value)}
                />
              </div>
              <Button size="sm" isLoading={upsert.isPending} onClick={save}>
                Enregistrer
              </Button>
            </div>

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
