import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { cn } from '@/lib/cn';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';
type Size = 'sm' | 'md' | 'lg';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: Variant | undefined;
  size?: Size | undefined;
  isLoading?: boolean | undefined;
  leftIcon?: ReactNode | undefined;
  rightIcon?: ReactNode | undefined;
}

const base =
  'inline-flex items-center justify-center gap-2 rounded-none font-medium ' +
  'transition-colors disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none ' +
  'focus-visible:ring-2 focus-visible:ring-(--color-primary) ' +
  'system:uppercase system:[letter-spacing:var(--label-spacing)] system:text-(length:--text-sm) ' +
  'system:motion-safe:active:translate-y-px';

const variantClass: Record<Variant, string> = {
  primary:
    'bg-(--color-primary) text-(--color-primary-foreground) hover:bg-(--color-primary-hover) shadow-(--bar-glow)',
  secondary:
    'bg-(--color-surface) text-(--color-text) border border-(--color-border) hover:bg-(--color-surface-muted)',
  ghost: 'bg-transparent text-(--color-text) hover:bg-(--color-surface-muted)',
  danger:
    'bg-(--color-danger) text-(--color-text-inverse) hover:bg-(--color-danger-hover) shadow-(--bar-glow)',
};

const sizeClass: Record<Size, string> = {
  sm: 'text-(length:--text-sm) px-3 py-1.5',
  md: 'text-(length:--text-base) px-4 py-2',
  lg: 'text-(length:--text-lg) px-5 py-2.5',
};

export function Button({
  variant = 'primary',
  size = 'md',
  isLoading = false,
  leftIcon,
  rightIcon,
  className,
  children,
  disabled,
  type = 'button',
  ...rest
}: ButtonProps) {
  return (
    <button
      type={type}
      className={cn(base, variantClass[variant], sizeClass[size], className)}
      disabled={disabled || isLoading}
      {...rest}
    >
      {leftIcon}
      <span>{isLoading ? '…' : children}</span>
      {rightIcon}
    </button>
  );
}
