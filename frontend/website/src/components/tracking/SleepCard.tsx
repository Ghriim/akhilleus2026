import { useState } from 'react';
import type { SleepDailyEntryDataOutput } from '@/api/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Spinner } from '@/components/ui/Spinner';
import { useLogSleep, useSleepRange, useUpdateSleep } from '@/hooks/tracking/useTracking';
import { cn } from '@/lib/cn';
import {
  formatDurationSeconds,
  fromDatetimeLocalValue,
  toDatetimeLocalValue,
} from '@/lib/format';

interface SleepCardProps {
  today: string;
}

const QUALITY_EMOJI = ['😫', '😕', '😐', '🙂', '😄'];

function qualityEmoji(quality: number | null): string {
  if (null === quality || quality < 1 || quality > 5) return '';
  return QUALITY_EMOJI[quality - 1] ?? '';
}

function timeOf(iso: string): string {
  const d = new Date(iso);
  return Number.isNaN(d.getTime())
    ? '—'
    : d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

function defaultBedAt(): string {
  const d = new Date();
  d.setDate(d.getDate() - 1);
  d.setHours(23, 0, 0, 0);
  return toDatetimeLocalValue(d.toISOString());
}

function defaultWakeAt(): string {
  const d = new Date();
  d.setHours(7, 0, 0, 0);
  return toDatetimeLocalValue(d.toISOString());
}

export function SleepCard({ today }: SleepCardProps) {
  const { data, isLoading } = useSleepRange(today, today);
  const logSleep = useLogSleep();
  const updateSleep = useUpdateSleep();
  const record: SleepDailyEntryDataOutput | undefined = data?.[0];

  const [editing, setEditing] = useState(false);
  const [bedAt, setBedAt] = useState('');
  const [wakeAt, setWakeAt] = useState('');
  const [quality, setQuality] = useState<number | null>(null);

  const startEditing = () => {
    setBedAt(record ? toDatetimeLocalValue(record.bedAt) : defaultBedAt());
    setWakeAt(record ? toDatetimeLocalValue(record.wakeAt) : defaultWakeAt());
    setQuality(record?.quality ?? null);
    setEditing(true);
  };

  const save = async () => {
    if ('' === bedAt || '' === wakeAt) return;
    const input = {
      bedAt: fromDatetimeLocalValue(bedAt),
      wakeAt: fromDatetimeLocalValue(wakeAt),
      quality,
    };
    if (record) {
      await updateSleep.mutateAsync({ id: record.id, input });
    } else {
      await logSleep.mutateAsync(input);
    }
    setEditing(false);
  };

  const mutationError =
    (logSleep.error as Error | null) ?? (updateSleep.error as Error | null);

  return (
    <Card>
      <CardHeader>
        <span className="font-(--font-display) font-semibold text-(--color-text)">😴 Sommeil</span>
        {!editing && (
          <Button size="sm" variant="ghost" onClick={startEditing}>
            {record ? 'Modifier' : 'Ajouter'}
          </Button>
        )}
      </CardHeader>
      <CardBody>
        {isLoading ? (
          <Spinner size="sm" />
        ) : editing ? (
          <div className="space-y-3">
            <div>
              <Label>Couché</Label>
              <Input
                type="datetime-local"
                value={bedAt}
                onChange={(e) => setBedAt(e.target.value)}
              />
            </div>
            <div>
              <Label>Réveil</Label>
              <Input
                type="datetime-local"
                value={wakeAt}
                onChange={(e) => setWakeAt(e.target.value)}
              />
            </div>
            <div>
              <Label>Qualité</Label>
              <div className="flex gap-1">
                {QUALITY_EMOJI.map((emoji, idx) => {
                  const v = idx + 1;
                  return (
                    <button
                      key={v}
                      type="button"
                      aria-label={`Qualité ${v}`}
                      className={cn(
                        'rounded-(--radius-md) border px-2 py-1 text-(length:--text-lg)',
                        quality === v
                          ? 'border-(--color-primary) bg-(--color-primary-soft)'
                          : 'border-(--color-border) bg-(--color-surface) opacity-60 hover:opacity-100',
                      )}
                      onClick={() => setQuality(quality === v ? null : v)}
                    >
                      {emoji}
                    </button>
                  );
                })}
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button size="sm" variant="ghost" onClick={() => setEditing(false)}>
                Annuler
              </Button>
              <Button
                size="sm"
                isLoading={logSleep.isPending || updateSleep.isPending}
                onClick={save}
              >
                Enregistrer
              </Button>
            </div>
            {mutationError && <Alert tone="danger">{mutationError.message}</Alert>}
          </div>
        ) : record ? (
          <div>
            <div className="text-(length:--text-2xl) font-semibold text-(--color-text)">
              {formatDurationSeconds(record.durationMinutes * 60)}{' '}
              <span className="text-(length:--text-xl)">{qualityEmoji(record.quality)}</span>
            </div>
            <div className="mt-1 text-(length:--text-sm) text-(--color-text-muted)">
              Couché {timeOf(record.bedAt)} → Réveil {timeOf(record.wakeAt)}
            </div>
          </div>
        ) : (
          <div className="text-(length:--text-sm) text-(--color-text-muted)">
            Aucune nuit enregistrée.
          </div>
        )}
      </CardBody>
    </Card>
  );
}
