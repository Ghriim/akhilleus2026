import { apiRequest } from '../client';
import type { PlayerMovementListItemDataOutput } from '../types';

export function list(): Promise<PlayerMovementListItemDataOutput[]> {
  return apiRequest<PlayerMovementListItemDataOutput[]>('/api/player/movements');
}
