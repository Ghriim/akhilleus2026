import { useState } from 'react';
import { Button, Space, notification } from 'antd';
import { PlusOutlined, EditOutlined } from '@ant-design/icons';
import { Link, useNavigate } from 'react-router-dom';
import type { ColumnsType, TableProps } from 'antd/es/table';
import { DataTable } from '@/components/DataTable';
import { DeleteConfirmButton } from '@/components/DeleteConfirmButton';
import { PageHeader } from '@/components/PageHeader';
import { useDeleteMuscleMutation, useMusclesQuery } from './hooks';
import type { Muscle } from './types';

export const MuscleListPage = () => {
  const [direction, setDirection] = useState<'ASC' | 'DESC'>('ASC');
  const { data, isLoading, error } = useMusclesQuery({ sort: 'label', direction });
  const deleteMutation = useDeleteMuscleMutation();
  const navigate = useNavigate();

  const handleChange: TableProps<Muscle>['onChange'] = (_pagination, _filters, sorter) => {
    if (Array.isArray(sorter)) return;
    setDirection(sorter.order === 'descend' ? 'DESC' : 'ASC');
  };

  const handleDelete = (id: string) => {
    deleteMutation.mutate(id, {
      onSuccess: () => {
        notification.success({ message: 'Muscle deleted.' });
      },
      onError: (err) => {
        notification.error({
          message: 'Delete failed',
          description: err instanceof Error ? err.message : 'Unknown error.',
        });
      },
    });
  };

  const columns: ColumnsType<Muscle> = [
    {
      title: 'Label',
      dataIndex: 'label',
      key: 'label',
      sorter: true,
      sortOrder: direction === 'ASC' ? 'ascend' : 'descend',
      showSorterTooltip: false,
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
            onClick={() => navigate(`/muscles/${row.id}`)}
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
        title="Muscles"
        extra={
          <Link to="/muscles/new">
            <Button type="primary" icon={<PlusOutlined />}>
              New muscle
            </Button>
          </Link>
        }
      />
      <DataTable<Muscle>
        columns={columns}
        rows={data}
        isLoading={isLoading}
        error={error}
        emptyText="No muscle yet."
        onChange={handleChange}
      />
    </>
  );
};
