import type { ReactNode } from 'react';

interface PageHeaderProps {
  title: ReactNode;
  description?: ReactNode;
  actions?: ReactNode;
}

export function PageHeader({ title, description, actions }: PageHeaderProps) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
      <div>
        <h1 className="hud-title text-(length:--text-3xl) system:text-(length:--text-2xl) font-(--font-display) font-semibold text-(--color-text)">
          {title}
        </h1>
        {description && (
          <p className="mt-1 text-(length:--text-sm) text-(--color-text-muted)">{description}</p>
        )}
      </div>
      {actions && <div className="flex gap-2">{actions}</div>}
    </div>
  );
}
