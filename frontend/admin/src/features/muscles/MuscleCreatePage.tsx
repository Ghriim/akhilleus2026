import { notification } from 'antd';
import { useNavigate } from 'react-router-dom';
import { PageHeader } from '@/components/PageHeader';
import { MuscleForm } from './MuscleForm';
import { useCreateMuscleMutation } from './hooks';
import type { MuscleFormValues } from './types';

export const MuscleCreatePage = () => {
  const navigate = useNavigate();
  const mutation = useCreateMuscleMutation();

  const handleSubmit = async (values: MuscleFormValues) => {
    const created = await mutation.mutateAsync(values);
    notification.success({ message: `Created "${created.label}".` });
    navigate('/muscles');
  };

  return (
    <>
      <PageHeader title="New muscle" />
      <MuscleForm
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Create"
        cancelTo="/muscles"
      />
    </>
  );
};
