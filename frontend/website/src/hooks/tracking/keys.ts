export const trackingKeys = {
  all: ['tracking'] as const,
  hydrationToday: () => [...trackingKeys.all, 'hydration', 'today'] as const,
  stepsToday: () => [...trackingKeys.all, 'steps', 'today'] as const,
  stepsRange: (from: string, to: string) => [...trackingKeys.all, 'steps', from, to] as const,
  sleepRange: (from: string, to: string) => [...trackingKeys.all, 'sleep', from, to] as const,
  weightRange: (from: string, to: string) => [...trackingKeys.all, 'weight', from, to] as const,
};
