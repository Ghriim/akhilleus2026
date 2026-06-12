import { apiRequest } from '../client';
import type { PlayerMovementPersonalBestsDataOutput } from '../types';

export function list(): Promise<PlayerMovementPersonalBestsDataOutput[]> {
  return apiRequest<PlayerMovementPersonalBestsDataOutput[]>('/api/player/personal-bests');
}
