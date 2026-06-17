import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { useDeleteWorkout } from '@/hooks/workout/useWorkouts';

interface DeleteWorkoutButtonProps {
  workoutId: string;
  plannedAt: string | null;
  dateStart: string | null;
  dateEnd: string | null;
}

/**
 * Predicts whether the backend will hard-delete (same-day) or soft-delete (other day) this workout,
 * so the confirmation copy is accurate. Uses the same representative-date precedence as the server
 * (dateEnd → dateStart → plannedAt); the comparison is against the local day, which matches the
 * server's Europe/Paris boundary for French users. A mismatch only changes the warning text — the
 * server remains the source of truth for the actual mode.
 */
function isDatedToday(plannedAt: string | null, dateStart: string | null, dateEnd: string | null): boolean {
  const iso = dateEnd ?? dateStart ?? plannedAt;
  if (!iso) return false;

  const date = new Date(iso);
  const now = new Date();
  return (
    date.getFullYear() === now.getFullYear() &&
    date.getMonth() === now.getMonth() &&
    date.getDate() === now.getDate()
  );
}

export function DeleteWorkoutButton({
  workoutId,
  plannedAt,
  dateStart,
  dateEnd,
}: DeleteWorkoutButtonProps) {
  const navigate = useNavigate();
  const del = useDeleteWorkout();

  const willHardDelete = isDatedToday(plannedAt, dateStart, dateEnd);
  const confirmCopy = willHardDelete
    ? 'Cette séance sera définitivement supprimée. Continuer ?'
    : "Cette séance sera marquée comme supprimée ; l'XP déjà gagnée est conservée. Continuer ?";

  return (
    <Button
      variant="danger"
      isLoading={del.isPending}
      onClick={async () => {
        if (window.confirm(confirmCopy)) {
          await del.mutateAsync(workoutId);
          navigate('/history');
        }
      }}
    >
      Supprimer
    </Button>
  );
}
