export type WorkoutStatus = 'PLANNED' | 'IN_PROGRESS' | 'COMPLETED' | 'CANCELED';

export type PersonalBestType =
  | 'HIGHEST_WEIGHT'
  | 'HIGHEST_REPS'
  | 'HIGHEST_VOLUME_ONE_SET'
  | 'HIGHEST_VOLUME_WORKOUT'
  | 'HIGHEST_DURATION'
  | 'HIGHEST_DISTANCE'
  | 'HIGHEST_SPEED';

export interface ApiViolation {
  message: string;
  errorCode: string;
  violations: Record<string, string[]>;
}

export interface LoginResponse {
  token: string;
}

export interface PlayerProfileDataOutput {
  id: string;
  displayName: string;
  level: number;
  currentXp: number;
  xpToNextLevel: number;
}

export interface RegisterPlayerResponse {
  playerId: string;
  email: string;
  displayName: string;
}

export interface WorkoutDataOutput {
  id: string;
  name: string;
  status: WorkoutStatus;
  plannedAt: string | null;
  dateStart: string | null;
  dateEnd: string | null;
  duration: number | null;
  volume: string | null;
  distance: string | null;
  inclineMeters: string | null;
}

export interface WorkoutHistoryDataOutput {
  items: WorkoutDataOutput[];
  page: number;
  perPage: number;
  totalCount: number;
}

export interface ExerciseMovementDataOutput {
  id: string;
  slug: string;
  label: string;
  tracksRepetitions: boolean;
  tracksWeight: boolean;
  tracksDuration: boolean;
  tracksDistance: boolean;
  tracksInclinePercent: boolean;
  tracksInclineMeters: boolean;
  videoLink: string | null;
  gifLink: string | null;
}

export interface ExerciseSetDataOutput {
  id: string;
  exerciseId: string;
  position: number;
  plannedReps: number | null;
  achievedReps: number | null;
  plannedWeight: string | null;
  achievedWeight: string | null;
  plannedDurationSeconds: number | null;
  achievedDurationSeconds: number | null;
  plannedDistanceMeters: string | null;
  achievedDistanceMeters: string | null;
  plannedInclinePercent: string | null;
  achievedInclinePercent: string | null;
  plannedInclineMeters: string | null;
  achievedInclineMeters: string | null;
  isComplete: boolean;
}

export interface ExerciseDetailsDataOutput {
  id: string;
  position: number;
  restDurationSeconds: number;
  movement: ExerciseMovementDataOutput;
  sets: ExerciseSetDataOutput[];
}

export interface WorkoutDetailsDataOutput {
  id: string;
  name: string;
  status: WorkoutStatus;
  plannedAt: string | null;
  dateStart: string | null;
  dateEnd: string | null;
  exercises: ExerciseDetailsDataOutput[];
  duration: number | null;
  volume: string | null;
  distance: string | null;
  inclineMeters: string | null;
}

export interface PersonalBestSummaryDataOutput {
  movementId: string;
  movementSlug: string;
  movementLabel: string;
  type: PersonalBestType;
  value: string;
  achievedAt: string;
  exerciseSetId: string | null;
}

export interface FinishWorkoutDataOutput {
  workout: WorkoutDataOutput;
  newPersonalBests: PersonalBestSummaryDataOutput[];
}

export interface MovementSummaryDataOutput {
  id: string;
  slug: string;
  label: string;
  mainMuscleSlug: string | null;
}

export interface PersonalBestEntryDataOutput {
  type: PersonalBestType;
  value: string;
  achievedAt: string;
  workoutId: string | null;
  exerciseSetId: string | null;
}

export interface PlayerMovementPersonalBestsDataOutput {
  movement: MovementSummaryDataOutput;
  personalBests: PersonalBestEntryDataOutput[];
}

export interface PlayerMovementListItemDataOutput {
  id: string;
  slug: string;
  label: string;
  mainMuscleSlug: string;
  tracksRepetitions: boolean;
  tracksWeight: boolean;
  tracksDuration: boolean;
  tracksDistance: boolean;
  tracksInclinePercent: boolean;
  tracksInclineMeters: boolean;
}

export interface ExerciseDataOutput {
  id: string;
  workoutId: string;
  position: number;
  restDurationSeconds: number;
  movement: ExerciseMovementDataOutput;
}

export interface RemoveExerciseDataOutput {
  exerciseId: string;
}

export interface RemoveExerciseSetDataOutput {
  exerciseSetId: string;
}

// --- Tracking sub-domain ---

export interface StepsDailyEntryDataOutput {
  id: string;
  date: string;
  count: number;
  target: number;
}

export interface DeleteStepsForDayDataOutput {
  deletedDate: string;
}

export interface PlayerStepsTargetDataOutput {
  dailyStepsTarget: number;
}

export interface HydrationEntryDataOutput {
  id: string;
  loggedAt: string;
  valueMl: number;
}

export interface HydrationDayDataOutput {
  date: string;
  targetMl: number;
  amountConsumedMl: number;
  entries: HydrationEntryDataOutput[];
}

export interface PlayerHydrationTargetDataOutput {
  dailyHydrationTargetMl: number;
}

export interface SleepDailyEntryDataOutput {
  id: string;
  date: string;
  bedAt: string;
  wakeAt: string;
  durationMinutes: number;
  quality: number | null;
}

export interface DeleteSleepDataOutput {
  deletedId: string;
}

export interface WeightEntryDataOutput {
  id: string;
  date: string;
  loggedAt: string;
  valueGrams: number;
}

export interface DeleteWeightDataOutput {
  deletedId: string;
}

export interface EarnedExperienceDataOutput {
  id: string;
  label: string;
  amount: number;
  earnedAt: string;
  sourceType: string;
  sourceId: string;
  isLocked: boolean;
}

export interface EarnedExperienceJournalDataOutput {
  items: EarnedExperienceDataOutput[];
  page: number;
  perPage: number;
  totalCount: number;
}
