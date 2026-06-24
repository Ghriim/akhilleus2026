import { forwardRef } from 'react';
import type { InputHTMLAttributes } from 'react';
import { cn } from '@/lib/cn';

type InputProps = InputHTMLAttributes<HTMLInputElement> & {
  invalid?: boolean;
};

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  { className, invalid, ...rest },
  ref,
) {
  return (
    <input
      ref={ref}
      className={cn(
        'w-full rounded-(--radius-md) bg-(--color-surface) px-3 py-2 text-(length:--text-base)',
        'border border-(--color-border) text-(--color-text)',
        'placeholder:text-(--color-text-subtle)',
        'focus:outline-none focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary)',
        'system:focus:shadow-(--bar-glow)',
        'disabled:cursor-not-allowed disabled:opacity-60',
        invalid && 'border-(--color-danger) focus:border-(--color-danger) focus:ring-(--color-danger)',
        className,
      )}
      {...rest}
    />
  );
});
