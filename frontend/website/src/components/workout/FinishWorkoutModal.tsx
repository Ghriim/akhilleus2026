import type { FinishWorkoutDataOutput, PersonalBestSummaryDataOutput } from '../../api/types';
import { Modal } from '../Modal';

interface Props {
  open: boolean;
  onClose: () => void;
  result: FinishWorkoutDataOutput | null;
  incompleteSetIds: string[] | null;
  errorMessage: string | null;
}

const PB_LABELS: Record<PersonalBestSummaryDataOutput['type'], string> = {
  HIGHEST_WEIGHT: 'Highest weight',
  HIGHEST_REPS: 'Highest reps',
  HIGHEST_VOLUME_ONE_SET: 'Best one-set volume',
  HIGHEST_VOLUME_WORKOUT: 'Best workout volume',
  HIGHEST_DURATION: 'Longest duration',
  HIGHEST_DISTANCE: 'Longest distance',
  HIGHEST_SPEED: 'Best speed',
};

export function FinishWorkoutModal({ open, onClose, result, incompleteSetIds, errorMessage }: Props) {
  if (incompleteSetIds && incompleteSetIds.length > 0) {
    return (
      <Modal open={open} onClose={onClose} title="Cannot finish workout">
        <p>The following sets are not yet marked as completed:</p>
        <ul>
          {incompleteSetIds.map((id) => (
            <li key={id}>
              <code>{id}</code>
            </li>
          ))}
        </ul>
        <p className="muted">
          Mark each set as complete (or remove it) before finishing the workout.
        </p>
      </Modal>
    );
  }

  if (errorMessage) {
    return (
      <Modal open={open} onClose={onClose} title="Could not finish workout">
        <p className="error" style={{ color: 'var(--color-danger)' }}>
          {errorMessage}
        </p>
      </Modal>
    );
  }

  if (result) {
    return (
      <Modal open={open} onClose={onClose} title="Workout completed">
        <p>Nice work! Your workout is now marked as completed.</p>
        {result.newPersonalBests.length === 0 ? (
          <p className="muted">No new personal bests this session.</p>
        ) : (
          <>
            <p style={{ fontWeight: 600 }}>
              🏆 {result.newPersonalBests.length} new personal best
              {result.newPersonalBests.length === 1 ? '' : 's'}:
            </p>
            <ul>
              {result.newPersonalBests.map((pb) => (
                <li key={`${pb.movementId}:${pb.type}`}>
                  <strong>{pb.movementLabel}</strong> — {PB_LABELS[pb.type]}: {pb.value}
                </li>
              ))}
            </ul>
          </>
        )}
      </Modal>
    );
  }

  return null;
}
