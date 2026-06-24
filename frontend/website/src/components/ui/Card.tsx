import type { HTMLAttributes, ReactNode } from 'react';
import { cn } from '@/lib/cn';

interface CardProps extends HTMLAttributes<HTMLDivElement> {
  children: ReactNode;
}

export function Card({ className, children, ...rest }: CardProps) {
  return (
    <div
      className={cn(
        'rounded-(--radius-surface) bg-(--color-surface) border border-(--color-border) shadow-(--shadow-sm)',
        '[filter:var(--glow)]',
        'system:motion-safe:animate-[system-rise_0.45s_cubic-bezier(0.2,0.7,0.2,1)_both]',
        className,
      )}
      {...rest}
    >
      {children}
    </div>
  );
}

export function CardHeader({ className, children, ...rest }: CardProps) {
  return (
    <div
      className={cn(
        'px-5 py-4 border-b border-(--color-border) flex items-center justify-between gap-3',
        className,
      )}
      {...rest}
    >
      {children}
    </div>
  );
}

export function CardBody({ className, children, ...rest }: CardProps) {
  return (
    <div className={cn('px-5 py-4', className)} {...rest}>
      {children}
    </div>
  );
}

export function CardFooter({ className, children, ...rest }: CardProps) {
  return (
    <div className={cn('px-5 py-4 border-t border-(--color-border)', className)} {...rest}>
      {children}
    </div>
  );
}
