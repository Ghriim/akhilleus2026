import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { cn } from '@/lib/cn';

interface IconButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  /** Accessible name, also surfaced as the hover tooltip. */
  label: string;
  children: ReactNode;
}

export function IconButton({ label, className, children, type = 'button', ...rest }: IconButtonProps) {
  return (
    <button
      type={type}
      aria-label={label}
      title={label}
      className={cn(
        'inline-flex h-8 w-8 items-center justify-center rounded-(--radius-surface) text-(--color-primary)',
        'transition-colors hover:bg-(--color-surface-muted) hover:text-(--color-primary-hover)',
        'focus:outline-none focus-visible:ring-2 focus-visible:ring-(--color-primary)',
        'disabled:cursor-not-allowed disabled:opacity-60',
        className,
      )}
      {...rest}
    >
      {children}
    </button>
  );
}
