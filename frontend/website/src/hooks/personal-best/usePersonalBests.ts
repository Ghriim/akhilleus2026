import { useQuery } from '@tanstack/react-query';
import * as personalBestsApi from '@/api/endpoints/personalBests';

export const personalBestKeys = {
  all: ['personal-bests'] as const,
  list: () => [...personalBestKeys.all, 'list'] as const,
};

export function usePersonalBests() {
  return useQuery({
    queryKey: personalBestKeys.list(),
    queryFn: personalBestsApi.list,
  });
}
