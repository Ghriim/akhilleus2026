import { useState } from 'react';
import type { SleepDailyEntryDataOutput } from '@/api/types';
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
  useLogSleep,
  useSleepRange,
  useUpdatePlayerSleepTarget,
  useUpdateSleep,
} from '@/hooks/tracking/useTracking';
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

// Sleep durations read in hours/minutes — a zero shows "0h" (not formatDurationSeconds' "0s").
function formatSleepDuration(minutes: number): string {
  return 0 === minutes ? '0h' : formatDurationSeconds(minutes * 60);
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
  const { data: profile } = useProfile();
  const logSleep = useLogSleep();
  const updateSleep = useUpdateSleep();
  const updateTarget = useUpdatePlayerSleepTarget();
  const record: SleepDailyEntryDataOutput | undefined = data?.[0];

  const targetMinutes = profile?.dailySleepTargetMinutes ?? 0;
  const durationMinutes = record?.durationMinutes ?? 0;

  const [open, setOpen] = useState(false);
  const [bedAt, setBedAt] = useState('');
  const [wakeAt, setWakeAt] = useState('');
  const [quality, setQuality] = useState<number | null>(null);

  const [targetOpen, setTargetOpen] = useState(false);
  const [targetHours, setTargetHours] = useState('');

  const openModal = () => {
    setBedAt(record ? toDatetimeLocalValue(record.bedAt) : defaultBedAt());
    setWakeAt(record ? toDatetimeLocalValue(record.wakeAt) : defaultWakeAt());
    setQuality(record?.quality ?? null);
    setOpen(true);
  };

  const openTarget = () => {
    setTargetHours(targetMinutes > 0 ? String(targetMinutes / 60) : '');
    setTargetOpen(true);
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
    setOpen(false);
  };

  const saveTarget = async () => {
    const hours = Number(targetHours);
    if ('' === targetHours.trim() || Number.isNaN(hours) || hours <= 0) return;
    await updateTarget.mutateAsync(Math.round(hours * 60));
    setTargetOpen(false);
  };

  const mutationError = (logSleep.error as Error | null) ?? (updateSleep.error as Error | null);

  return (
    <Card>
      <CardHeader>
        <span className="font-(--font-display) font-semibold text-(--color-text)">Sommeil</span>
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
        ) : (
          <>
            <div className="flex items-baseline justify-between text-(length:--text-sm) text-(--color-text-muted)">
              <span className="text-(length:--text-2xl) font-semibold text-(--color-text)">
                {formatSleepDuration(durationMinutes)}
                {record && (
                  <span className="text-(length:--text-xl)"> {qualityEmoji(record.quality)}</span>
                )}
              </span>
              <span>/ {formatSleepDuration(targetMinutes)}</span>
            </div>
            <div className="mt-1 text-(length:--text-sm) text-(--color-text-muted)">
              {record
                ? `Couché ${timeOf(record.bedAt)} → Réveil ${timeOf(record.wakeAt)}`
                : 'Aucune nuit enregistrée.'}
            </div>
          </>
        )}
      </CardBody>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title="😴 Sommeil"
        footer={
          <div className="flex justify-end gap-2">
            <Button variant="ghost" onClick={() => setOpen(false)}>
              Annuler
            </Button>
            <Button isLoading={logSleep.isPending || updateSleep.isPending} onClick={save}>
              Enregistrer
            </Button>
          </div>
        }
      >
        <div className="space-y-3">
          <div>
            <Label>Couché</Label>
            <Input type="datetime-local" value={bedAt} onChange={(e) => setBedAt(e.target.value)} />
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
          {mutationError && <Alert tone="danger">{mutationError.message}</Alert>}
        </div>
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
        <Label>Objectif (heures)</Label>
        <Input
          type="number"
          min="0"
          step="0.5"
          value={targetHours}
          onChange={(e) => setTargetHours(e.target.value)}
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
