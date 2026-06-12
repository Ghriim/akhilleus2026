import { useMovements } from '@/hooks/movement/useMovements';
import { Label } from '@/components/ui/Label';
import { Select } from '@/components/ui/Select';
import { Spinner } from '@/components/ui/Spinner';

interface MovementPickerProps {
  value: string;
  onChange: (id: string) => void;
  label?: string;
  id?: string;
}

export function MovementPicker({ value, onChange, label = 'Mouvement', id }: MovementPickerProps) {
  const { data, isLoading, isError, error } = useMovements();

  return (
    <div>
      <Label htmlFor={id}>{label}</Label>
      {isLoading ? (
        <Spinner size="sm" />
      ) : isError ? (
        <div className="text-(--color-danger) text-(length:--text-sm)">
          {(error as Error).message}
        </div>
      ) : (
        <Select id={id} value={value} onChange={(e) => onChange(e.target.value)}>
          <option value="">— Sélectionner —</option>
          {data?.map((m) => (
            <option key={m.id} value={m.id}>
              {m.label}
            </option>
          ))}
        </Select>
      )}
    </div>
  );
}
