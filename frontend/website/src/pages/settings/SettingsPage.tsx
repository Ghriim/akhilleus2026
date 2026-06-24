import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { cn } from '@/lib/cn';
import { THEMES, type Theme } from '@/theme/constants';
import { useTheme } from '@/theme/useTheme';

const THEME_LABELS: Record<Theme, string> = {
  basic: 'Basic',
  system: 'System',
};

export function SettingsPage() {
  const { theme, setTheme } = useTheme();

  return (
    <>
      <PageHeader title="Réglages" description="Vos préférences et paramètres de compte." />
      <Card>
        <CardHeader>
          <span className="font-(--font-display) font-semibold text-(--color-text)">Thème</span>
        </CardHeader>
        <CardBody>
          <div className="flex flex-wrap gap-3">
            {THEMES.map((value) => {
              const isActive = theme === value;
              return (
                <button
                  key={value}
                  type="button"
                  aria-pressed={isActive}
                  onClick={() => setTheme(value)}
                  className={cn(
                    'min-w-28 rounded-(--radius-surface) border px-4 py-3 text-(length:--text-sm) font-semibold transition-colors',
                    isActive
                      ? 'border-(--color-primary) bg-(--color-primary-soft) text-(--color-primary)'
                      : 'border-(--color-border) bg-(--color-surface) text-(--color-text-muted) hover:border-(--color-border-strong) hover:text-(--color-text)',
                  )}
                >
                  {THEME_LABELS[value]}
                </button>
              );
            })}
          </div>
        </CardBody>
      </Card>
    </>
  );
}
