import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';

export function SettingsPage() {
  return (
    <>
      <PageHeader title="Réglages" description="Vos préférences et paramètres de compte." />
      <EmptyState
        title="Bientôt disponible"
        description="Cette section accueillera bientôt vos réglages. Reviens vite !"
      />
    </>
  );
}
