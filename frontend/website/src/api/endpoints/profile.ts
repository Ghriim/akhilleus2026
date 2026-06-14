import { apiRequest } from '../client';
import type { PlayerProfileDataOutput } from '../types';

export function getProfile(): Promise<PlayerProfileDataOutput> {
  return apiRequest<PlayerProfileDataOutput>('/api/player/profile');
}
