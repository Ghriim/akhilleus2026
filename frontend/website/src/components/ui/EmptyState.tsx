import type { ReactNode } from 'react';

interface EmptyStateProps {
  title: ReactNode;
  description?: ReactNode;
  action?: ReactNode;
}

export function EmptyState({ title, description, action }: EmptyStateProps) {
  return (
    <div className="rounded-(--radius-surface) border border-dashed border-(--color-border) bg-(--color-surface) px-6 py-10 text-center">
      <div className="text-(length:--text-lg) font-semibold text-(--color-text)">{title}</div>
      {description && (
        <div className="mt-1 text-(length:--text-sm) text-(--color-text-muted)">{description}</div>
      )}
      {action && <div className="mt-4 flex justify-center">{action}</div>}
    </div>
  );
}
