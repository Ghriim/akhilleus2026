import { forwardRef } from 'react';
import type { TextareaHTMLAttributes } from 'react';
import { cn } from '@/lib/cn';

type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement> & {
  invalid?: boolean;
};

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
  { className, invalid, ...rest },
  ref,
) {
  return (
    <textarea
      ref={ref}
      className={cn(
        'w-full rounded-(--radius-surface) bg-(--color-surface) px-3 py-2 text-(length:--text-base)',
        'border border-(--color-border) text-(--color-text)',
        'placeholder:text-(--color-text-subtle)',
        'focus:outline-none focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary)',
        invalid && 'border-(--color-danger) focus:border-(--color-danger) focus:ring-(--color-danger)',
        className,
      )}
      {...rest}
    />
  );
});
