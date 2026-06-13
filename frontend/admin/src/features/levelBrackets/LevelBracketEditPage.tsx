import { notification } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import { LoadingState } from '@/components/LoadingState';
import { ErrorState } from '@/components/ErrorState';
import { PageHeader } from '@/components/PageHeader';
import { LevelBracketForm } from './LevelBracketForm';
import { useLevelBracketQuery, useUpdateLevelBracketMutation } from './hooks';
import type { LevelBracketFormValues } from './types';

export const LevelBracketEditPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const bracketQuery = useLevelBracketQuery(id);
  const mutation = useUpdateLevelBracketMutation(id ?? '');

  if (id === undefined) {
    return <ErrorState error={new Error('Missing level bracket id in URL.')} />;
  }

  if (bracketQuery.isLoading) {
    return <LoadingState />;
  }

  if (bracketQuery.error !== null && bracketQuery.error !== undefined) {
    return <ErrorState error={bracketQuery.error} title="Failed to load this level bracket" />;
  }

  if (bracketQuery.data === undefined) {
    return <ErrorState error={new Error('Level bracket not found.')} />;
  }

  const bracket = bracketQuery.data;

  const handleSubmit = async (values: LevelBracketFormValues) => {
    const updated = await mutation.mutateAsync(values);
    notification.success({ message: `Updated the bracket starting at level ${updated.fromLevel}.` });
    navigate('/level-brackets');
  };

  return (
    <>
      <PageHeader title={`Edit bracket from level ${bracket.fromLevel}`} />
      <LevelBracketForm
        initialValues={{
          fromLevel: bracket.fromLevel,
          toLevel: bracket.toLevel,
          coefficientA: bracket.coefficientA,
          exponentK: bracket.exponentK,
          offsetB: bracket.offsetB,
        }}
        onSubmit={handleSubmit}
        isSubmitting={mutation.isPending}
        submitError={mutation.error}
        submitLabel="Save"
        cancelTo="/level-brackets"
      />
    </>
  );
};
