export interface ListQueryParams {
  sort?: string;
  direction?: 'ASC' | 'DESC';
}

export const buildListQueryString = (params?: ListQueryParams): string => {
  const search = new URLSearchParams();
  if (params?.sort !== undefined) {
    search.set('sort', params.sort);
  }
  if (params?.direction !== undefined) {
    search.set('direction', params.direction);
  }
  const qs = search.toString();
  return qs === '' ? '' : `?${qs}`;
};
