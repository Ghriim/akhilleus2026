import { useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Input } from '@/components/ui/Input';
import { PageHeader } from '@/components/ui/PageHeader';
import { Spinner } from '@/components/ui/Spinner';
import { TBody, THead, TD, TH, TR, Table } from '@/components/ui/Table';
import { useMovements } from '@/hooks/movement/useMovements';

export function MovementsPage() {
  const [filter, setFilter] = useState('');
  const { data, isLoading, isError, error } = useMovements();

  const filtered =
    data?.filter((m) => m.label.toLowerCase().includes(filter.toLowerCase())) ?? [];

  return (
    <>
      <PageHeader title="Mouvements" description="Catalogue des mouvements disponibles." />
      <div className="mb-4 max-w-sm">
        <Input
          placeholder="Rechercher…"
          value={filter}
          onChange={(e) => setFilter(e.target.value)}
        />
      </div>
      {isLoading ? (
        <Spinner />
      ) : isError ? (
        <Alert tone="danger">{(error as Error).message}</Alert>
      ) : (
        <Table>
          <THead>
            <TR>
              <TH>Mouvement</TH>
              <TH>Muscle</TH>
              <TH>Suivi</TH>
            </TR>
          </THead>
          <TBody>
            {filtered.map((m) => (
              <TR key={m.id}>
                <TD className="font-medium">{m.label}</TD>
                <TD className="text-(--color-text-muted)">{m.mainMuscleSlug}</TD>
                <TD>
                  <div className="flex flex-wrap gap-1">
                    {m.tracksRepetitions && <Badge>rép.</Badge>}
                    {m.tracksWeight && <Badge>poids</Badge>}
                    {m.tracksDuration && <Badge>durée</Badge>}
                    {m.tracksDistance && <Badge>distance</Badge>}
                    {m.tracksInclinePercent && <Badge>incl.%</Badge>}
                    {m.tracksInclineMeters && <Badge>D+</Badge>}
                  </div>
                </TD>
              </TR>
            ))}
          </TBody>
        </Table>
      )}
    </>
  );
}
