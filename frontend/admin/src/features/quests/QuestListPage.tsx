import { Button, Space, Tag, notification } from 'antd';
import { PlusOutlined, EditOutlined } from '@ant-design/icons';
import { Link, useNavigate } from 'react-router-dom';
import type { ColumnsType } from 'antd/es/table';
import { DataTable } from '@/components/DataTable';
import { DeleteConfirmButton } from '@/components/DeleteConfirmButton';
import { PageHeader } from '@/components/PageHeader';
import { useDeleteQuestMutation, useQuestsQuery } from './hooks';
import type { Quest } from './types';

const formatDate = (iso: string | null): string =>
  iso === null ? '∞' : new Date(iso).toLocaleDateString();

export const QuestListPage = () => {
  const { data, isLoading, error } = useQuestsQuery();
  const deleteMutation = useDeleteQuestMutation();
  const navigate = useNavigate();

  const handleDelete = (id: string) => {
    deleteMutation.mutate(id, {
      onSuccess: () => {
        notification.success({ message: 'Quest deleted.' });
      },
      onError: (err) => {
        notification.error({
          message: 'Delete failed',
          description: err instanceof Error ? err.message : 'Unknown error.',
        });
      },
    });
  };

  const columns: ColumnsType<Quest> = [
    {
      title: 'Label',
      dataIndex: 'label',
      key: 'label',
    },
    {
      title: 'Kind',
      dataIndex: 'kind',
      key: 'kind',
      render: (value: Quest['kind']) => (
        <Tag color={value === 'AUTOMATIC' ? 'blue' : 'gold'}>{value}</Tag>
      ),
    },
    {
      title: 'Periodicity',
      dataIndex: 'periodicity',
      key: 'periodicity',
    },
    {
      title: 'Metric',
      dataIndex: 'metric',
      key: 'metric',
      render: (value: string | null) => value ?? '—',
    },
    {
      title: 'Target',
      dataIndex: 'targetValue',
      key: 'targetValue',
      render: (value: string | null) => value ?? '—',
    },
    {
      title: 'Reward XP',
      dataIndex: 'rewardedXp',
      key: 'rewardedXp',
    },
    {
      title: 'Window',
      key: 'window',
      render: (_value, row) => `${formatDate(row.dateStart)} → ${formatDate(row.dateEnd)}`,
    },
    {
      title: 'Actions',
      key: 'actions',
      width: 220,
      render: (_value, row) => (
        <Space>
          <Button type="text" icon={<EditOutlined />} onClick={() => navigate(`/quests/${row.id}`)}>
            Edit
          </Button>
          <DeleteConfirmButton
            onConfirm={() => handleDelete(row.id)}
            isLoading={deleteMutation.isPending && deleteMutation.variables === row.id}
            title={`Delete quest "${row.label}"?`}
          />
        </Space>
      ),
    },
  ];

  return (
    <>
      <PageHeader
        title="Quests"
        extra={
          <Link to="/quests/new">
            <Button type="primary" icon={<PlusOutlined />}>
              New quest
            </Button>
          </Link>
        }
      />
      <DataTable<Quest>
        columns={columns}
        rows={data}
        isLoading={isLoading}
        error={error}
        emptyText="No quests yet."
      />
    </>
  );
};
