import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';

type Tone = 'info' | 'success' | 'warning' | 'danger';

interface AlertProps {
  tone?: Tone;
  title?: ReactNode;
  className?: string;
  children: ReactNode;
}

const toneClass: Record<Tone, string> = {
  info: 'bg-(--color-info-soft) text-(--color-info) border-(--color-info)',
  success: 'bg-(--color-success-soft) text-(--color-success) border-(--color-success)',
  warning: 'bg-(--color-warning-soft) text-(--color-warning) border-(--color-warning)',
  danger: 'bg-(--color-danger-soft) text-(--color-danger) border-(--color-danger)',
};

export function Alert({ tone = 'info', title, className, children }: AlertProps) {
  return (
    <div
      className={cn(
        'rounded-(--radius-md) border-l-4 px-4 py-3 text-(length:--text-sm)',
        toneClass[tone],
        className,
      )}
      role="alert"
    >
      {title && <div className="font-semibold mb-1">{title}</div>}
      <div>{children}</div>
    </div>
  );
}
