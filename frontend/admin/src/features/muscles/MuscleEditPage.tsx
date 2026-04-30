import { notification } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import { LoadingState } from '@/components/LoadingState';
import { ErrorState } from '@/components/ErrorState';
import { PageHeader } from '@/components/PageHeader';
import { MuscleForm } from './MuscleForm';
import { useMuscleQuery, useUpdateMuscleMutation } from './hooks';
import type { MuscleFormValues } from './types';

export const MuscleEditPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const muscleQuery = useMuscleQuery(id);
  const mutation = useUpdateMuscleMutation(id ?? '');

  if (id === undefined) {
    return <ErrorState error={new Error('Missing muscle id in URL.')} />;
  }

  if (muscleQuery.isLoading) {
    return <LoadingState />;
  }

  if (muscleQuery.error !== null && muscleQuery.error !== undefined) {
    return <ErrorState error={muscleQuery.error} title="Failed to load this muscle" />;
  }

  if (muscleQuery.data === undefined) {
    return <ErrorState error={new Error('Muscle not found.')} />;
  }

  const handleSubmit = async (values: MuscleFormValues) => {
    const updated = await mutation.mutateAsync(values);
    notification.success({ message: `Updated "${updated.label}".` });
    navigate('/muscles');
  };

  return (
    <>
      <PageHeader title={`Edit "${muscleQuery.data.label}"`} />
      <MuscleForm
        initialValues={{ label: muscleQuery.data.label }}
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Save"
        cancelTo="/muscles"
      />
    </>
  );
};
