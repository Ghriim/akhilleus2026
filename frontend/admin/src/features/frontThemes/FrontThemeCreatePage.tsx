import { notification } from 'antd';
import { useNavigate } from 'react-router-dom';
import { PageHeader } from '@/components/PageHeader';
import { FrontThemeForm } from './FrontThemeForm';
import { useCreateFrontThemeMutation } from './hooks';
import { toFrontThemeFormData } from './transforms';
import type { FrontThemeFormValues } from './types';

export const FrontThemeCreatePage = () => {
  const navigate = useNavigate();
  const mutation = useCreateFrontThemeMutation();

  const handleSubmit = async (values: FrontThemeFormValues) => {
    const created = await mutation.mutateAsync(toFrontThemeFormData(values));
    notification.success({ message: `Created the theme "${created.name}".` });
    navigate('/front-themes');
  };

  return (
    <>
      <PageHeader title="New theme" />
      <FrontThemeForm
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Create"
        cancelTo="/front-themes"
      />
    </>
  );
};
