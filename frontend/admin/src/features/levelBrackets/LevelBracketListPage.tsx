import { Button, Card, Space, notification } from 'antd';
import { PlusOutlined, EditOutlined } from '@ant-design/icons';
import { Link, useNavigate } from 'react-router-dom';
import type { ColumnsType } from 'antd/es/table';
import { DataTable } from '@/components/DataTable';
import { DeleteConfirmButton } from '@/components/DeleteConfirmButton';
import { PageHeader } from '@/components/PageHeader';
import { LevelCurveChart } from './LevelCurveChart';
import { useDeleteLevelBracketMutation, useLevelBracketsQuery } from './hooks';
import type { LevelBracket } from './types';

const formatFormula = (bracket: LevelBracket): string =>
  `${bracket.coefficientA} × n^${bracket.exponentK} + ${bracket.offsetB}`;

export const LevelBracketListPage = () => {
  const { data, isLoading, error } = useLevelBracketsQuery();
  const deleteMutation = useDeleteLevelBracketMutation();
  const navigate = useNavigate();

  const handleDelete = (id: string) => {
    deleteMutation.mutate(id, {
      onSuccess: () => {
        notification.success({ message: 'Level bracket deleted.' });
      },
      onError: (err) => {
        notification.error({
          message: 'Delete failed',
          description: err instanceof Error ? err.message : 'Unknown error.',
        });
      },
    });
  };

  const columns: ColumnsType<LevelBracket> = [
    {
      title: 'From level',
      dataIndex: 'fromLevel',
      key: 'fromLevel',
    },
    {
      title: 'To level',
      dataIndex: 'toLevel',
      key: 'toLevel',
      render: (value: number | null) => (value === null ? '∞' : value),
    },
    {
      title: 'Marginal cost (a × n^k + b)',
      key: 'formula',
      render: (_value, row) => formatFormula(row),
    },
    {
      title: 'Actions',
      key: 'actions',
      width: 220,
      render: (_value, row) => (
        <Space>
          <Button
            type="text"
            icon={<EditOutlined />}
            onClick={() => navigate(`/level-brackets/${row.id}`)}
          >
            Edit
          </Button>
          <DeleteConfirmButton
            onConfirm={() => handleDelete(row.id)}
            isLoading={deleteMutation.isPending && deleteMutation.variables === row.id}
            title={`Delete bracket from level ${row.fromLevel}?`}
          />
        </Space>
      ),
    },
  ];

  return (
    <>
      <PageHeader
        title="Level brackets"
        extra={
          <Link to="/level-brackets/new">
            <Button type="primary" icon={<PlusOutlined />}>
              New bracket
            </Button>
          </Link>
        }
      />
      {data !== undefined && data.length > 0 ? (
        <Card title="Curve preview — marginal XP cost per level" style={{ marginBottom: 24 }}>
          <LevelCurveChart brackets={data} />
        </Card>
      ) : null}
      <DataTable<LevelBracket>
        columns={columns}
        rows={data}
        isLoading={isLoading}
        error={error}
        emptyText="No level brackets yet."
      />
    </>
  );
};
