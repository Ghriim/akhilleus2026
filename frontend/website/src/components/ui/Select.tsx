import { forwardRef } from 'react';
import type { SelectHTMLAttributes } from 'react';
import { cn } from '@/lib/cn';

type SelectProps = SelectHTMLAttributes<HTMLSelectElement> & {
  invalid?: boolean;
};

export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
  { className, invalid, children, ...rest },
  ref,
) {
  return (
    <select
      ref={ref}
      className={cn(
        'w-full rounded-(--radius-md) bg-(--color-surface) px-3 py-2 text-(length:--text-base)',
        'border border-(--color-border) text-(--color-text)',
        'focus:outline-none focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary)',
        'disabled:cursor-not-allowed disabled:opacity-60',
        invalid && 'border-(--color-danger) focus:border-(--color-danger) focus:ring-(--color-danger)',
        className,
      )}
      {...rest}
    >
      {children}
    </select>
  );
});
