export const levelingKeys = {
  all: ['leveling'] as const,
  journal: (page: number, perPage: number) =>
    [...levelingKeys.all, 'journal', page, perPage] as const,
};
