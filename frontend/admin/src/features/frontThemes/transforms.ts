import type { FrontTheme, FrontThemeFormValues } from './types';

/** Form → multipart body. Only appends the image when a new file was picked. */
export const toFrontThemeFormData = (values: FrontThemeFormValues): FormData => {
  const body = new FormData();
  body.append('name', values.name);
  if (values.description !== undefined && values.description !== '') {
    body.append('description', values.description);
  }
  const file = values.image?.[0]?.originFileObj;
  if (file !== undefined) {
    body.append('image', file);
  }

  return body;
};

/** API → form initial values (the image is never pre-filled; the current preview is shown instead). */
export const toFrontThemeFormValues = (theme: FrontTheme): Partial<FrontThemeFormValues> => ({
  name: theme.name,
  ...(theme.description !== null ? { description: theme.description } : {}),
});
