export interface LevelBracket {
  id: string;
  fromLevel: number;
  toLevel: number | null;
  coefficientA: number;
  exponentK: number;
  offsetB: number;
}

export interface LevelBracketFormValues {
  fromLevel: number;
  toLevel: number | null;
  coefficientA: number;
  exponentK: number;
  offsetB: number;
}
