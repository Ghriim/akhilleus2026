import { useState } from 'react';
import type { ExerciseMovementDataOutput, ExerciseSetDataOutput } from '@/api/types';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';

export type SetFormMode = 'planned' | 'achieved';

export interface SetFormValues {
  reps: string;
  weight: string;
  durationSeconds: string;
  distanceMeters: string;
  inclinePercent: string;
  inclineMeters: string;
}

const EMPTY_VALUES: SetFormValues = {
  reps: '',
  weight: '',
  durationSeconds: '',
  distanceMeters: '',
  inclinePercent: '',
  inclineMeters: '',
};

export function valuesFromSet(set: ExerciseSetDataOutput | null, mode: SetFormMode): SetFormValues {
  if (!set) return EMPTY_VALUES;
  if ('planned' === mode) {
    return {
      reps: set.plannedReps?.toString() ?? '',
      weight: set.plannedWeight ?? '',
      durationSeconds: set.plannedDurationSeconds?.toString() ?? '',
      distanceMeters: set.plannedDistanceMeters ?? '',
      inclinePercent: set.plannedInclinePercent ?? '',
      inclineMeters: set.plannedInclineMeters ?? '',
    };
  }
  return {
    reps: set.achievedReps?.toString() ?? '',
    weight: set.achievedWeight ?? '',
    durationSeconds: set.achievedDurationSeconds?.toString() ?? '',
    distanceMeters: set.achievedDistanceMeters ?? '',
    inclinePercent: set.achievedInclinePercent ?? '',
    inclineMeters: set.achievedInclineMeters ?? '',
  };
}

function toNumberOrNull(value: string): number | null {
  if ('' === value.trim()) return null;
  const n = Number(value);
  return Number.isNaN(n) ? null : n;
}

function toStringOrNull(value: string): string | null {
  const v = value.trim();
  return '' === v ? null : v;
}

export function plannedPayload(values: SetFormValues) {
  return {
    plannedReps: toNumberOrNull(values.reps),
    plannedWeight: toStringOrNull(values.weight),
    plannedDurationSeconds: toNumberOrNull(values.durationSeconds),
    plannedDistanceMeters: toStringOrNull(values.distanceMeters),
    plannedInclinePercent: toStringOrNull(values.inclinePercent),
    plannedInclineMeters: toStringOrNull(values.inclineMeters),
  };
}

export function achievedPayload(values: SetFormValues) {
  return {
    achievedReps: toNumberOrNull(values.reps),
    achievedWeight: toStringOrNull(values.weight),
    achievedDurationSeconds: toNumberOrNull(values.durationSeconds),
    achievedDistanceMeters: toStringOrNull(values.distanceMeters),
    achievedInclinePercent: toStringOrNull(values.inclinePercent),
    achievedInclineMeters: toStringOrNull(values.inclineMeters),
  };
}

interface SetFormProps {
  movement: ExerciseMovementDataOutput;
  mode: SetFormMode;
  initial?: SetFormValues | undefined;
  onSubmit: (values: SetFormValues) => void;
  submitLabel: string;
  isLoading?: boolean | undefined;
  onCancel?: (() => void) | undefined;
}

export function SetForm({
  movement,
  mode,
  initial,
  onSubmit,
  submitLabel,
  isLoading,
  onCancel,
}: SetFormProps) {
  const [values, setValues] = useState<SetFormValues>(initial ?? EMPTY_VALUES);

  const set = <K extends keyof SetFormValues>(key: K) => (v: string) =>
    setValues((prev) => ({ ...prev, [key]: v }));

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        onSubmit(values);
      }}
      className="grid grid-cols-2 sm:grid-cols-3 gap-3"
    >
      {movement.tracksRepetitions && (
        <Field label="Répétitions" value={values.reps} onChange={set('reps')} type="number" />
      )}
      {movement.tracksWeight && (
        <Field label="Poids (kg)" value={values.weight} onChange={set('weight')} type="number" step="0.5" />
      )}
      {movement.tracksDuration && (
        <Field
          label="Durée (s)"
          value={values.durationSeconds}
          onChange={set('durationSeconds')}
          type="number"
        />
      )}
      {movement.tracksDistance && (
        <Field
          label="Distance (m)"
          value={values.distanceMeters}
          onChange={set('distanceMeters')}
          type="number"
          step="0.1"
        />
      )}
      {movement.tracksInclinePercent && (
        <Field
          label="Inclinaison (%)"
          value={values.inclinePercent}
          onChange={set('inclinePercent')}
          type="number"
          step="0.1"
        />
      )}
      {movement.tracksInclineMeters && (
        <Field
          label="D+ (m)"
          value={values.inclineMeters}
          onChange={set('inclineMeters')}
          type="number"
          step="0.1"
        />
      )}
      <div className="col-span-full flex justify-end gap-2 mt-1">
        {onCancel && (
          <Button type="button" variant="ghost" size="sm" onClick={onCancel}>
            Annuler
          </Button>
        )}
        <Button type="submit" size="sm" isLoading={isLoading}>
          {submitLabel}
        </Button>
      </div>
      <input type="hidden" value={mode} readOnly />
    </form>
  );
}

interface FieldProps {
  label: string;
  value: string;
  onChange: (v: string) => void;
  type?: string;
  step?: string;
}

function Field({ label, value, onChange, type = 'text', step }: FieldProps) {
  return (
    <div>
      <Label>{label}</Label>
      <Input type={type} step={step} value={value} onChange={(e) => onChange(e.target.value)} />
    </div>
  );
}
