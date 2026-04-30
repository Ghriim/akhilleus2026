import { useState } from 'react';
import { Button, Space, notification } from 'antd';
import { PlusOutlined, EditOutlined } from '@ant-design/icons';
import { Link, useNavigate } from 'react-router-dom';
import type { ColumnsType, TableProps } from 'antd/es/table';
import { DataTable } from '@/components/DataTable';
import { DeleteConfirmButton } from '@/components/DeleteConfirmButton';
import { PageHeader } from '@/components/PageHeader';
import { useDeleteMovementMutation, useMovementsQuery } from './hooks';
import type { MovementListItem } from './types';

export const MovementListPage = () => {
  const [direction, setDirection] = useState<'ASC' | 'DESC'>('ASC');
  const { data, isLoading, error } = useMovementsQuery({ sort: 'label', direction });
  const deleteMutation = useDeleteMovementMutation();
  const navigate = useNavigate();

  const handleChange: TableProps<MovementListItem>['onChange'] = (_pagination, _filters, sorter) => {
    if (Array.isArray(sorter)) return;
    setDirection(sorter.order === 'descend' ? 'DESC' : 'ASC');
  };

  const handleDelete = (id: string) => {
    deleteMutation.mutate(id, {
      onSuccess: () => notification.success({ message: 'Movement deleted.' }),
      onError: (err) =>
        notification.error({
          message: 'Delete failed',
          description: err instanceof Error ? err.message : 'Unknown error.',
        }),
    });
  };

  const columns: ColumnsType<MovementListItem> = [
    {
      title: 'Label',
      dataIndex: 'label',
      key: 'label',
      sorter: true,
      sortOrder: direction === 'ASC' ? 'ascend' : 'descend',
      showSorterTooltip: false,
    },
    {
      title: 'Main muscle',
      dataIndex: 'mainMuscleSlug',
      key: 'mainMuscleSlug',
      width: 200,
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
            onClick={() => navigate(`/movements/${row.id}`)}
          >
            Edit
          </Button>
          <DeleteConfirmButton
            onConfirm={() => handleDelete(row.id)}
            isLoading={deleteMutation.isPending && deleteMutation.variables === row.id}
            title={`Delete "${row.label}"?`}
          />
        </Space>
      ),
    },
  ];

  return (
    <>
      <PageHeader
        title="Movements"
        extra={
          <Link to="/movements/new">
            <Button type="primary" icon={<PlusOutlined />}>
              New movement
            </Button>
          </Link>
        }
      />
      <DataTable<MovementListItem>
        columns={columns}
        rows={data}
        isLoading={isLoading}
        error={error}
        emptyText="No movement yet."
        onChange={handleChange}
      />
    </>
  );
};
