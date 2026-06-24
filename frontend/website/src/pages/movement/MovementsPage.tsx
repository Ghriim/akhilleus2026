import { useMemo, useState } from 'react';
import type { PersonalBestEntryDataOutput, PersonalBestType } from '@/api/types';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { PageHeader } from '@/components/ui/PageHeader';
import { Spinner } from '@/components/ui/Spinner';
import { useMovements } from '@/hooks/movement/useMovements';
import { usePersonalBests } from '@/hooks/personal-best/usePersonalBests';
import { formatDate } from '@/lib/format';

const PERSONAL_BEST_LABEL: Record<PersonalBestType, string> = {
  HIGHEST_WEIGHT: 'Poids max',
  HIGHEST_REPS: 'Reps max',
  HIGHEST_VOLUME_ONE_SET: 'Volume / série',
  HIGHEST_VOLUME_WORKOUT: 'Volume / séance',
  HIGHEST_DURATION: 'Durée max',
  HIGHEST_DISTANCE: 'Distance max',
  HIGHEST_SPEED: 'Vitesse max',
};

export function MovementsPage() {
  const [filter, setFilter] = useState('');
  const movementsQuery = useMovements();
  const personalBestsQuery = usePersonalBests();

  const personalBestsByMovementId = useMemo(() => {
    const map = new Map<string, PersonalBestEntryDataOutput[]>();
    for (const entry of personalBestsQuery.data ?? []) {
      map.set(entry.movement.id, entry.personalBests);
    }
    return map;
  }, [personalBestsQuery.data]);

  const isLoading = movementsQuery.isLoading || personalBestsQuery.isLoading;
  const isError = movementsQuery.isError || personalBestsQuery.isError;
  const error = (movementsQuery.error ?? personalBestsQuery.error) as Error | null;

  const filtered =
    movementsQuery.data?.filter((m) => m.label.toLowerCase().includes(filter.toLowerCase())) ?? [];

  return (
    <>
      <PageHeader
        title="Mouvements"
        description="Catalogue des mouvements et records personnels associés."
      />
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
        <Alert tone="danger">{error?.message}</Alert>
      ) : (
        <div className="space-y-3">
          {filtered.map((m) => {
            const personalBests = personalBestsByMovementId.get(m.id) ?? [];
            return (
              <Card key={m.id}>
                <CardHeader>
                  <div>
                    <div className="text-(length:--text-base) font-semibold">{m.label}</div>
                    <div className="text-(length:--text-xs) text-(--color-text-muted)">
                      {m.mainMuscleSlug}
                    </div>
                  </div>
                  <div className="flex flex-wrap justify-end gap-1">
                    {m.tracksRepetitions && <Badge>rép.</Badge>}
                    {m.tracksWeight && <Badge>poids</Badge>}
                    {m.tracksDuration && <Badge>durée</Badge>}
                    {m.tracksDistance && <Badge>distance</Badge>}
                    {m.tracksInclinePercent && <Badge>incl.%</Badge>}
                    {m.tracksInclineMeters && <Badge>D+</Badge>}
                  </div>
                </CardHeader>
                {personalBests.length > 0 && (
                  <CardBody>
                    <ul className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                      {personalBests.map((pb) => (
                        <li
                          key={`${m.id}-${pb.type}`}
                          className="flex items-center justify-between rounded-(--radius-surface) bg-(--color-surface-muted) px-3 py-2"
                        >
                          <div>
                            <Badge tone="primary">{PERSONAL_BEST_LABEL[pb.type] ?? pb.type}</Badge>
                            <div className="mt-1 text-(length:--text-xs) text-(--color-text-muted)">
                              {formatDate(pb.achievedAt)}
                            </div>
                          </div>
                          <span className="text-(length:--text-lg) font-semibold text-(--color-text)">
                            {pb.value}
                          </span>
                        </li>
                      ))}
                    </ul>
                  </CardBody>
                )}
              </Card>
            );
          })}
        </div>
      )}
    </>
  );
}
