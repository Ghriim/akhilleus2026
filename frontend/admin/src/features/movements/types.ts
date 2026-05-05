export interface MuscleSummary {
  id: string;
  slug: string;
  label: string;
}

export interface EquipmentSummary {
  id: string;
  slug: string;
  label: string;
}

export interface Movement {
  id: string;
  slug: string;
  label: string;
  mainMuscle: MuscleSummary;
  secondaryMuscles: MuscleSummary[];
  equipments: EquipmentSummary[];
  tracksRepetitions: boolean;
  tracksWeight: boolean;
  tracksDuration: boolean;
  tracksDistance: boolean;
  tracksInclinePercent: boolean;
  tracksInclineMeters: boolean;
  videoLink: string | null;
  gifLink: string | null;
}

export interface MovementListItem {
  id: string;
  slug: string;
  label: string;
  mainMuscleSlug: string;
}

export interface MovementFormValues {
  label: string;
  mainMuscleId: string;
  secondaryMuscleIds: string[];
  equipmentIds: string[];
  tracksRepetitions: boolean;
  tracksWeight: boolean;
  tracksDuration: boolean;
  tracksDistance: boolean;
  tracksInclinePercent: boolean;
  tracksInclineMeters: boolean;
  videoLink: string | null;
  gifLink: string | null;
}
