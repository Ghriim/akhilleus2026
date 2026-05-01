import { useEffect } from 'react';
import type { ReactNode } from 'react';

interface Props {
  open: boolean;
  title: string;
  onClose: () => void;
  children: ReactNode;
  /** Footer content (typically buttons). If omitted, a single Close button is rendered. */
  footer?: ReactNode;
}

/**
 * Tiny accessible modal — no dependency on a UI lib so the D&D theming pass keeps full control.
 * Closes on Escape and on backdrop click.
 */
export function Modal({ open, title, onClose, children, footer }: Props) {
  useEffect(() => {
    if (!open) return;
    const handler = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label={title}
      onClick={onClose}
      style={{
        position: 'fixed',
        inset: 0,
        background: 'rgba(0, 0, 0, 0.4)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 1000,
      }}
    >
      <div
        onClick={(e) => e.stopPropagation()}
        style={{
          background: 'var(--color-surface)',
          borderRadius: 'var(--radius)',
          padding: 'var(--space-6)',
          minWidth: 320,
          maxWidth: '90vw',
          maxHeight: '90vh',
          overflow: 'auto',
          boxShadow: '0 8px 30px rgba(0, 0, 0, 0.2)',
        }}
      >
        <h2 style={{ marginTop: 0 }}>{title}</h2>
        <div>{children}</div>
        <div
          style={{
            marginTop: 'var(--space-6)',
            display: 'flex',
            justifyContent: 'flex-end',
            gap: 'var(--space-2)',
          }}
        >
          {footer ?? (
            <button type="button" onClick={onClose}>
              Close
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
