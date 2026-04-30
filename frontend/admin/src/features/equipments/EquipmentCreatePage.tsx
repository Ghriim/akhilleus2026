import { notification } from 'antd';
import { useNavigate } from 'react-router-dom';
import { PageHeader } from '@/components/PageHeader';
import { EquipmentForm } from './EquipmentForm';
import { useCreateEquipmentMutation } from './hooks';
import type { EquipmentFormValues } from './types';

export const EquipmentCreatePage = () => {
  const navigate = useNavigate();
  const mutation = useCreateEquipmentMutation();

  const handleSubmit = async (values: EquipmentFormValues) => {
    const created = await mutation.mutateAsync(values);
    notification.success({ message: `Created "${created.label}".` });
    navigate('/equipments');
  };

  return (
    <>
      <PageHeader title="New equipment" />
      <EquipmentForm
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Create"
        cancelTo="/equipments"
      />
    </>
  );
};
