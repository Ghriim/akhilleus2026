import { useQuery } from '@tanstack/react-query';
import * as movementsApi from '@/api/endpoints/movements';

export const movementKeys = {
  all: ['movements'] as const,
  list: () => [...movementKeys.all, 'list'] as const,
};

export function useMovements() {
  return useQuery({
    queryKey: movementKeys.list(),
    queryFn: movementsApi.list,
  });
}
