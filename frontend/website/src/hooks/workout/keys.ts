export const workoutKeys = {
  all: ['workouts'] as const,
  upcoming: () => [...workoutKeys.all, 'upcoming'] as const,
  history: (page: number, perPage: number) => [...workoutKeys.all, 'history', page, perPage] as const,
  calendar: (year: number, month: number) => [...workoutKeys.all, 'calendar', year, month] as const,
  details: (id: string) => [...workoutKeys.all, 'details', id] as const,
};
