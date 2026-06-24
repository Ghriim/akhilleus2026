import type { HTMLAttributes, ThHTMLAttributes, TdHTMLAttributes, ReactNode } from 'react';
import { cn } from '@/lib/cn';

type RowProps = HTMLAttributes<HTMLTableRowElement> & { children: ReactNode };

export function Table({ className, children, ...rest }: HTMLAttributes<HTMLTableElement>) {
  return (
    <div className="overflow-x-auto rounded-(--radius-surface) border border-(--color-border) bg-(--color-surface)">
      <table className={cn('w-full text-(length:--text-sm)', className)} {...rest}>
        {children}
      </table>
    </div>
  );
}

export function THead({ children }: { children: ReactNode }) {
  return <thead className="bg-(--color-surface-muted) text-(--color-text-muted)">{children}</thead>;
}

export function TBody({ children }: { children: ReactNode }) {
  return <tbody className="divide-y divide-(--color-border)">{children}</tbody>;
}

export function TR({ className, children, ...rest }: RowProps) {
  return (
    <tr className={cn('hover:bg-(--color-surface-muted)', className)} {...rest}>
      {children}
    </tr>
  );
}

export function TH({ className, children, ...rest }: ThHTMLAttributes<HTMLTableCellElement>) {
  return (
    <th
      className={cn('text-left font-semibold uppercase tracking-wide px-4 py-2 text-(length:--text-xs)', className)}
      {...rest}
    >
      {children}
    </th>
  );
}

export function TD({ className, children, ...rest }: TdHTMLAttributes<HTMLTableCellElement>) {
  return (
    <td className={cn('px-4 py-2 align-middle', className)} {...rest}>
      {children}
    </td>
  );
}
