/**
 * Hand-rolled mirror of the backend DataOutput / DataInput shapes used by the player site.
 * Phase 8 may swap this for openapi-typescript generation.
 */

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

export interface RegisterPlayerResponse {
  playerId: string;
  email: string;
  displayName: string;
}

export interface WorkoutDataOutput {
  id: string;
  status: WorkoutStatus;
  plannedAt: string | null;
  dateStart: string | null;
  dateEnd: string | null;
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
  completed: boolean;
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
  status: WorkoutStatus;
  plannedAt: string | null;
  dateStart: string | null;
  dateEnd: string | null;
  exercises: ExerciseDetailsDataOutput[];
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
