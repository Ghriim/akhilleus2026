import type { ExerciseMovementDataOutput } from '../../api/types';

interface Props {
  movement: Pick<ExerciseMovementDataOutput, 'videoLink' | 'gifLink' | 'label'>;
}

export function MovementMediaLinks({ movement }: Props) {
  const { videoLink, gifLink, label } = movement;
  if (videoLink === null && gifLink === null) return null;

  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        gap: 'var(--space-2)',
        marginTop: 'var(--space-1)',
        flexWrap: 'wrap',
      }}
    >
      {videoLink !== null && (
        <a
          href={videoLink}
          target="_blank"
          rel="noopener noreferrer"
          style={{ fontSize: '0.85em' }}
        >
          ▶ Voir la démo
        </a>
      )}
      {gifLink !== null && (
        <a
          href={gifLink}
          target="_blank"
          rel="noopener noreferrer"
          title={`${label} demo`}
          aria-label={`Open ${label} GIF in a new tab`}
        >
          <img
            src={gifLink}
            alt={`${label} demo`}
            style={{
              maxHeight: 60,
              maxWidth: 100,
              borderRadius: 4,
              border: '1px solid var(--color-border)',
              display: 'block',
            }}
          />
        </a>
      )}
    </div>
  );
}
