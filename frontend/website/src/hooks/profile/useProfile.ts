import { useQuery } from '@tanstack/react-query';
import * as profileApi from '@/api/endpoints/profile';
import { profileKeys } from './keys';

export function useProfile() {
  return useQuery({
    queryKey: profileKeys.me(),
    queryFn: profileApi.getProfile,
  });
}
