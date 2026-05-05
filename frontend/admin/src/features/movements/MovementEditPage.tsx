import { notification } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import { LoadingState } from '@/components/LoadingState';
import { ErrorState } from '@/components/ErrorState';
import { PageHeader } from '@/components/PageHeader';
import { MovementForm } from './MovementForm';
import { useMovementQuery, useUpdateMovementMutation } from './hooks';
import type { MovementFormValues } from './types';

export const MovementEditPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const movementQuery = useMovementQuery(id);
  const mutation = useUpdateMovementMutation(id ?? '');

  if (id === undefined) {
    return <ErrorState error={new Error('Missing movement id in URL.')} />;
  }
  if (movementQuery.isLoading) {
    return <LoadingState />;
  }
  if (movementQuery.error !== null && movementQuery.error !== undefined) {
    return <ErrorState error={movementQuery.error} title="Failed to load this movement" />;
  }
  if (movementQuery.data === undefined) {
    return <ErrorState error={new Error('Movement not found.')} />;
  }

  const movement = movementQuery.data;

  const initialValues: Partial<MovementFormValues> = {
    label: movement.label,
    mainMuscleId: movement.mainMuscle.id,
    secondaryMuscleIds: movement.secondaryMuscles.map((m) => m.id),
    equipmentIds: movement.equipments.map((e) => e.id),
    tracksRepetitions: movement.tracksRepetitions,
    tracksWeight: movement.tracksWeight,
    tracksDuration: movement.tracksDuration,
    tracksDistance: movement.tracksDistance,
    tracksInclinePercent: movement.tracksInclinePercent,
    tracksInclineMeters: movement.tracksInclineMeters,
    videoLink: movement.videoLink,
    gifLink: movement.gifLink,
  };

  const handleSubmit = async (values: MovementFormValues) => {
    const updated = await mutation.mutateAsync(values);
    notification.success({ message: `Updated "${updated.label}".` });
    navigate('/movements');
  };

  return (
    <>
      <PageHeader title={`Edit "${movement.label}"`} />
      <MovementForm
        initialValues={initialValues}
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Save"
        cancelTo="/movements"
      />
    </>
  );
};
