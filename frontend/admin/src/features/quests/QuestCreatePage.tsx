import { notification } from 'antd';
import { useNavigate } from 'react-router-dom';
import { PageHeader } from '@/components/PageHeader';
import { QuestForm } from './QuestForm';
import { useCreateQuestMutation } from './hooks';
import { toQuestPayload } from './transforms';
import type { QuestFormValues } from './types';

export const QuestCreatePage = () => {
  const navigate = useNavigate();
  const mutation = useCreateQuestMutation();

  const handleSubmit = async (values: QuestFormValues) => {
    const created = await mutation.mutateAsync(toQuestPayload(values));
    notification.success({ message: `Created the quest "${created.label}".` });
    navigate('/quests');
  };

  return (
    <>
      <PageHeader title="New quest" />
      <QuestForm
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Create"
        cancelTo="/quests"
      />
    </>
  );
};
