import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';

type Tone = 'neutral' | 'primary' | 'success' | 'warning' | 'danger' | 'info';

interface BadgeProps {
  tone?: Tone;
  className?: string;
  children: ReactNode;
}

const toneClass: Record<Tone, string> = {
  neutral: 'bg-(--color-surface-muted) text-(--color-text-muted)',
  primary: 'bg-(--color-primary-soft) text-(--color-primary)',
  success: 'bg-(--color-success-soft) text-(--color-success)',
  warning: 'bg-(--color-warning-soft) text-(--color-warning)',
  danger: 'bg-(--color-danger-soft) text-(--color-danger)',
  info: 'bg-(--color-info-soft) text-(--color-info)',
};

export function Badge({ tone = 'neutral', className, children }: BadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-(--radius-sm) px-2 py-0.5 text-(length:--text-xs) font-medium',
        toneClass[tone],
        className,
      )}
    >
      {children}
    </span>
  );
}
