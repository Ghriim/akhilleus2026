import { HydrationCard } from './HydrationCard';
import { SleepCard } from './SleepCard';
import { StepsCard } from './StepsCard';
import { WeightCard } from './WeightCard';

function todayYmd(): string {
  const d = new Date();
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export function TrackingWidget() {
  const today = todayYmd();

  return (
    <section className="mb-6">
      <h2 className="mb-3 text-(length:--text-xl) font-(--font-display) font-semibold text-(--color-text)">
        Suivi du jour
      </h2>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <HydrationCard />
        <StepsCard />
        <SleepCard today={today} />
        <WeightCard today={today} />
      </div>
    </section>
  );
}
