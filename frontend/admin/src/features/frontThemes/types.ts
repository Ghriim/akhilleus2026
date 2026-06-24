import type { UploadFile } from 'antd';

export interface FrontTheme {
  id: string;
  name: string;
  description: string | null;
  imagePreviewUrl: string | null;
}

/** Values held by the antd form. The image is an antd Upload fileList (0 or 1 file). */
export interface FrontThemeFormValues {
  name: string;
  description?: string;
  image?: UploadFile[];
}
