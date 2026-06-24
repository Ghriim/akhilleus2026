import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { XMarkIcon } from '@/components/icons';
import { IconButton } from '@/components/ui/IconButton';
import { cn } from '@/lib/cn';

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title?: ReactNode;
  children: ReactNode;
  footer?: ReactNode;
  className?: string;
}

export function Modal({ open, onClose, title, children, footer, className }: ModalProps) {
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if ('Escape' === e.key) onClose();
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!open) return null;

  // Portaled to <body> so the fixed overlay escapes any ancestor stacking context (e.g. a Card's
  // `filter` glow), guaranteeing a full-viewport backdrop, true centering and top-most layering.
  return createPortal(
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-4"
      onMouseDown={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
    >
      <div
        role="dialog"
        aria-modal="true"
        className={cn(
          'w-full max-w-lg rounded-(--radius-surface) bg-(--color-surface) shadow-(--shadow-lg) border border-(--color-border)',
          '[filter:var(--glow)]',
          className,
        )}
      >
        <div className="flex items-center justify-between gap-3 px-5 py-3 border-b border-(--color-border)">
          <div className="text-(length:--text-lg) font-semibold">{title}</div>
          <IconButton label="Fermer" onClick={onClose}>
            <XMarkIcon />
          </IconButton>
        </div>
        <div className="px-5 py-4">{children}</div>
        {footer && <div className="px-5 py-3 border-t border-(--color-border)">{footer}</div>}
      </div>
    </div>,
    document.body,
  );
}
