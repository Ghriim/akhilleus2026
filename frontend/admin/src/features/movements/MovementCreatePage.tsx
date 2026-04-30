import { notification } from 'antd';
import { useNavigate } from 'react-router-dom';
import { PageHeader } from '@/components/PageHeader';
import { MovementForm } from './MovementForm';
import { useCreateMovementMutation } from './hooks';
import type { MovementFormValues } from './types';

export const MovementCreatePage = () => {
  const navigate = useNavigate();
  const mutation = useCreateMovementMutation();

  const handleSubmit = async (values: MovementFormValues) => {
    const created = await mutation.mutateAsync(values);
    notification.success({ message: `Created "${created.label}".` });
    navigate('/movements');
  };

  return (
    <>
      <PageHeader title="New movement" />
      <MovementForm
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Create"
        cancelTo="/movements"
      />
    </>
  );
};
