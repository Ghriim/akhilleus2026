import type { LabelHTMLAttributes, ReactNode } from 'react';
import { cn } from '@/lib/cn';

interface LabelProps extends LabelHTMLAttributes<HTMLLabelElement> {
  children: ReactNode;
}

export function Label({ className, children, ...rest }: LabelProps) {
  return (
    <label
      className={cn(
        'block text-(length:--text-sm) font-medium text-(--color-text) mb-1',
        className,
      )}
      {...rest}
    >
      {children}
    </label>
  );
}
