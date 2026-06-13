import { LineChart } from '@mui/x-charts/LineChart';
import type { LevelBracket } from './types';

// How many levels of the open-ended (last) bracket to preview, since it has no upper bound.
const OPEN_ENDED_PREVIEW_LEVELS = 10;

const buildCurve = (brackets: LevelBracket[]): { levels: number[]; costs: number[] } => {
  if (brackets.length === 0) {
    return { levels: [], costs: [] };
  }

  const sorted = [...brackets].sort((a, b) => a.fromLevel - b.fromLevel);
  const maxFinite = sorted.reduce((max, b) => (b.toLevel !== null ? Math.max(max, b.toLevel) : max), 1);
  const openEnded = sorted.find((b) => b.toLevel === null);
  const maxLevel =
    openEnded !== undefined
      ? Math.max(maxFinite, openEnded.fromLevel + OPEN_ENDED_PREVIEW_LEVELS - 1)
      : maxFinite;

  const levels: number[] = [];
  const costs: number[] = [];
  for (let n = 1; n <= maxLevel; n += 1) {
    const bracket = sorted.find((b) => b.fromLevel <= n && (b.toLevel === null || n <= b.toLevel));
    if (bracket === undefined) {
      continue;
    }
    levels.push(n);
    costs.push(bracket.coefficientA * n ** bracket.exponentK + bracket.offsetB);
  }

  return { levels, costs };
};

interface LevelCurveChartProps {
  brackets: LevelBracket[];
}

export const LevelCurveChart = ({ brackets }: LevelCurveChartProps) => {
  const { levels, costs } = buildCurve(brackets);
  if (levels.length === 0) {
    return null;
  }

  return (
    <LineChart
      xAxis={[{ data: levels, label: 'Level', scaleType: 'point' }]}
      series={[{ data: costs, label: 'Marginal XP cost', showMark: false, color: '#1677ff' }]}
      height={280}
    />
  );
};
