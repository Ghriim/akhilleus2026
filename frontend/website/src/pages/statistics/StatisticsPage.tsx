import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';

export function StatisticsPage() {
  return (
    <>
      <PageHeader title="Statistiques" description="Le suivi graphique de votre progression." />
      <EmptyState
        title="Bientôt disponible"
        description="Cette section accueillera bientôt les graphiques de votre activité. Reviens vite !"
      />
    </>
  );
}
