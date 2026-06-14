import { useQuery } from '@tanstack/react-query';
import * as levelingApi from '@/api/endpoints/leveling';
import { levelingKeys } from './keys';

export function useXpJournal(page: number, perPage: number = 20) {
  return useQuery({
    queryKey: levelingKeys.journal(page, perPage),
    queryFn: () => levelingApi.listJournal(page, perPage),
  });
}
