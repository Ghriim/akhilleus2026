import { notification } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import { ErrorState } from '@/components/ErrorState';
import { LoadingState } from '@/components/LoadingState';
import { PageHeader } from '@/components/PageHeader';
import { FrontThemeForm } from './FrontThemeForm';
import { useFrontThemeQuery, useUpdateFrontThemeMutation } from './hooks';
import { toFrontThemeFormData, toFrontThemeFormValues } from './transforms';
import type { FrontThemeFormValues } from './types';

export const FrontThemeEditPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const themeQuery = useFrontThemeQuery(id);
  const mutation = useUpdateFrontThemeMutation(id ?? '');

  if (id === undefined) {
    return <ErrorState error={new Error('Missing theme id in URL.')} />;
  }

  if (themeQuery.isLoading) {
    return <LoadingState />;
  }

  if (themeQuery.error !== null && themeQuery.error !== undefined) {
    return <ErrorState error={themeQuery.error} title="Failed to load this theme" />;
  }

  if (themeQuery.data === undefined) {
    return <ErrorState error={new Error('Theme not found.')} />;
  }

  const theme = themeQuery.data;

  const handleSubmit = async (values: FrontThemeFormValues) => {
    const updated = await mutation.mutateAsync(toFrontThemeFormData(values));
    notification.success({ message: `Updated the theme "${updated.name}".` });
    navigate('/front-themes');
  };

  return (
    <>
      <PageHeader title={`Edit theme "${theme.name}"`} />
      <FrontThemeForm
        initialValues={toFrontThemeFormValues(theme)}
        currentImageUrl={theme.imagePreviewUrl}
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Save"
        cancelTo="/front-themes"
      />
    </>
  );
};
