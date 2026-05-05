import type { ExerciseMovementDataOutput } from '../../api/types';

interface Props {
  movement: Pick<ExerciseMovementDataOutput, 'videoLink' | 'gifLink' | 'label'>;
}

/**
 * Returns the YouTube video id if the URL points to a youtube.com / youtu.be
 * video (covers /watch?v=, /embed/, /shorts/, and the short youtu.be host),
 * otherwise null. Robust to malformed URLs (catches the URL constructor throw).
 */
function parseYoutubeVideoId(url: string): string | null {
  try {
    const u = new URL(url);
    if (u.hostname === 'youtu.be') {
      const id = u.pathname.replace(/^\//, '').split('/')[0];
      return id !== '' ? id : null;
    }
    if (u.hostname.endsWith('youtube.com') || u.hostname.endsWith('youtube-nocookie.com')) {
      const watchParam = u.searchParams.get('v');
      if (watchParam !== null && watchParam !== '') return watchParam;
      const pathMatch = u.pathname.match(/^\/(?:embed|shorts)\/([^/?#]+)/);
      if (pathMatch !== null) return pathMatch[1];
    }
    return null;
  } catch {
    return null;
  }
}

/**
 * Renders the media tile that sits to the right of the sets list in an exercise card.
 * Precedence (highest first):
 *   1. videoLink is a YouTube URL → inline YouTube embed (no-cookie variant, lazy-loaded).
 *   2. videoLink is a non-YouTube URL → big clickable play tile that opens the URL in a new tab.
 *   3. gifLink set → big clickable GIF tile that opens the GIF in a new tab.
 *   4. neither set → discreet "no demo" placeholder.
 */
export function MovementMedia({ movement }: Props) {
  const { videoLink, gifLink, label } = movement;
  const youtubeId = videoLink !== null ? parseYoutubeVideoId(videoLink) : null;

  if (videoLink !== null && youtubeId !== null) {
    return (
      <div className="movement-media">
        <iframe
          src={`https://www.youtube-nocookie.com/embed/${youtubeId}`}
          title={`${label} demo video`}
          loading="lazy"
          allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowFullScreen
        />
      </div>
    );
  }

  if (videoLink !== null) {
    return (
      <a
        href={videoLink}
        target="_blank"
        rel="noopener noreferrer"
        title={`Open ${label} video in a new tab`}
        aria-label={`Open ${label} video in a new tab`}
        className="movement-media movement-media__play-tile"
      >
        <span aria-hidden="true">▶</span>
      </a>
    );
  }

  if (gifLink !== null) {
    return (
      <a
        href={gifLink}
        target="_blank"
        rel="noopener noreferrer"
        title={`Open ${label} GIF in a new tab`}
        aria-label={`Open ${label} GIF in a new tab`}
        className="movement-media movement-media__gif-link"
      >
        <img src={gifLink} alt={`${label} demo`} className="movement-media__gif" />
      </a>
    );
  }

  return (
    <div className="movement-media movement-media__placeholder" aria-hidden="true">
      <span>Aucune démo</span>
    </div>
  );
}
