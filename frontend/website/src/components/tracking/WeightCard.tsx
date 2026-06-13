import { useEffect, useState } from 'react';
import type { WeightEntryDataOutput } from '@/api/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Spinner } from '@/components/ui/Spinner';
import { useLogWeight, useUpdateWeight, useWeightRange } from '@/hooks/tracking/useTracking';
import { formatNumber } from '@/lib/format';

interface WeightCardProps {
  today: string;
}

export function WeightCard({ today }: WeightCardProps) {
  const { data, isLoading } = useWeightRange(today, today);
  const logWeight = useLogWeight();
  const updateWeight = useUpdateWeight();
  const record: WeightEntryDataOutput | undefined = data?.[0];
  const currentKg = record ? record.valueGrams / 1000 : null;

  const [value, setValue] = useState('');

  useEffect(() => {
    setValue(null !== currentKg ? String(currentKg) : '');
  }, [currentKg]);

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
  };

  const mutationError =
    (logWeight.error as Error | null) ?? (updateWeight.error as Error | null);

  return (
    <Card>
      <CardHeader>
        <span className="font-(--font-display) font-semibold text-(--color-text)">⚖️ Poids</span>
        {null !== currentKg && (
          <span className="text-(length:--text-2xl) font-semibold text-(--color-text)">
            {formatNumber(currentKg, 1)} kg
          </span>
        )}
      </CardHeader>
      <CardBody>
        {isLoading ? (
          <Spinner size="sm" />
        ) : (
          <>
            <div className="flex items-end gap-2">
              <div className="flex-1">
                <Input
                  type="number"
                  min="0"
                  step="0.1"
                  placeholder="Poids (kg)"
                  value={value}
                  onChange={(e) => setValue(e.target.value)}
                />
              </div>
              <Button
                size="sm"
                isLoading={logWeight.isPending || updateWeight.isPending}
                onClick={save}
              >
                Enregistrer
              </Button>
            </div>
            {mutationError && (
              <Alert tone="danger" className="mt-2">
                {mutationError.message}
              </Alert>
            )}
          </>
        )}
      </CardBody>
    </Card>
  );
}
