import { notification } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import { LoadingState } from '@/components/LoadingState';
import { ErrorState } from '@/components/ErrorState';
import { PageHeader } from '@/components/PageHeader';
import { EquipmentForm } from './EquipmentForm';
import { useEquipmentQuery, useUpdateEquipmentMutation } from './hooks';
import type { EquipmentFormValues } from './types';

export const EquipmentEditPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const equipmentQuery = useEquipmentQuery(id);
  const mutation = useUpdateEquipmentMutation(id ?? '');

  if (id === undefined) {
    return <ErrorState error={new Error('Missing equipment id in URL.')} />;
  }

  if (equipmentQuery.isLoading) {
    return <LoadingState />;
  }

  if (equipmentQuery.error !== null && equipmentQuery.error !== undefined) {
    return <ErrorState error={equipmentQuery.error} title="Failed to load this equipment" />;
  }

  if (equipmentQuery.data === undefined) {
    return <ErrorState error={new Error('Equipment not found.')} />;
  }

  const handleSubmit = async (values: EquipmentFormValues) => {
    const updated = await mutation.mutateAsync(values);
    notification.success({ message: `Updated "${updated.label}".` });
    navigate('/equipments');
  };

  return (
    <>
      <PageHeader title={`Edit "${equipmentQuery.data.label}"`} />
      <EquipmentForm
        initialValues={{ label: equipmentQuery.data.label }}
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Save"
        cancelTo="/equipments"
      />
    </>
  );
};
