import { notification } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import { ErrorState } from '@/components/ErrorState';
import { LoadingState } from '@/components/LoadingState';
import { PageHeader } from '@/components/PageHeader';
import { QuestForm } from './QuestForm';
import { useQuestQuery, useUpdateQuestMutation } from './hooks';
import { toQuestFormValues, toQuestPayload } from './transforms';
import type { QuestFormValues } from './types';

export const QuestEditPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const questQuery = useQuestQuery(id);
  const mutation = useUpdateQuestMutation(id ?? '');

  if (id === undefined) {
    return <ErrorState error={new Error('Missing quest id in URL.')} />;
  }

  if (questQuery.isLoading) {
    return <LoadingState />;
  }

  if (questQuery.error !== null && questQuery.error !== undefined) {
    return <ErrorState error={questQuery.error} title="Failed to load this quest" />;
  }

  if (questQuery.data === undefined) {
    return <ErrorState error={new Error('Quest not found.')} />;
  }

  const quest = questQuery.data;

  const handleSubmit = async (values: QuestFormValues) => {
    const updated = await mutation.mutateAsync(toQuestPayload(values));
    notification.success({ message: `Updated the quest "${updated.label}".` });
    navigate('/quests');
  };

  return (
    <>
      <PageHeader title={`Edit quest "${quest.label}"`} />
      <QuestForm
        initialValues={toQuestFormValues(quest)}
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Save"
        cancelTo="/quests"
      />
    </>
  );
};
