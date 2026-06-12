import type { ExerciseMovementDataOutput } from '@/api/types';

interface MovementMediaProps {
  movement: ExerciseMovementDataOutput;
}

export function MovementMedia({ movement }: MovementMediaProps) {
  if (!movement.gifLink && !movement.videoLink) return null;

  return (
    <div className="flex flex-wrap gap-3">
      {movement.gifLink && (
        <img
          src={movement.gifLink}
          alt={movement.label}
          className="h-32 w-auto rounded-(--radius-md) border border-(--color-border) bg-(--color-surface)"
          loading="lazy"
        />
      )}
      {movement.videoLink && (
        <a
          href={movement.videoLink}
          target="_blank"
          rel="noreferrer"
          className="self-center text-(length:--text-sm) text-(--color-primary) hover:underline"
        >
          Voir la vidéo →
        </a>
      )}
    </div>
  );
}
