import { notification } from 'antd';
import { useNavigate } from 'react-router-dom';
import { PageHeader } from '@/components/PageHeader';
import { LevelBracketForm } from './LevelBracketForm';
import { useCreateLevelBracketMutation } from './hooks';
import type { LevelBracketFormValues } from './types';

export const LevelBracketCreatePage = () => {
  const navigate = useNavigate();
  const mutation = useCreateLevelBracketMutation();

  const handleSubmit = async (values: LevelBracketFormValues) => {
    const created = await mutation.mutateAsync(values);
    notification.success({ message: `Created the bracket starting at level ${created.fromLevel}.` });
    navigate('/level-brackets');
  };

  return (
    <>
      <PageHeader title="New level bracket" />
      <LevelBracketForm
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Create"
        cancelTo="/level-brackets"
      />
    </>
  );
};
