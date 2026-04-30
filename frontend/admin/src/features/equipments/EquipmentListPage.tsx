import { useState } from 'react';
import { Button, Space, notification } from 'antd';
import { PlusOutlined, EditOutlined } from '@ant-design/icons';
import { Link, useNavigate } from 'react-router-dom';
import type { ColumnsType, TableProps } from 'antd/es/table';
import { DataTable } from '@/components/DataTable';
import { DeleteConfirmButton } from '@/components/DeleteConfirmButton';
import { PageHeader } from '@/components/PageHeader';
import { useDeleteEquipmentMutation, useEquipmentsQuery } from './hooks';
import type { Equipment } from './types';

export const EquipmentListPage = () => {
  const [direction, setDirection] = useState<'ASC' | 'DESC'>('ASC');
  const { data, isLoading, error } = useEquipmentsQuery({ sort: 'label', direction });
  const deleteMutation = useDeleteEquipmentMutation();
  const navigate = useNavigate();

  const handleChange: TableProps<Equipment>['onChange'] = (_pagination, _filters, sorter) => {
    if (Array.isArray(sorter)) return;
    setDirection(sorter.order === 'descend' ? 'DESC' : 'ASC');
  };

  const handleDelete = (id: string) => {
    deleteMutation.mutate(id, {
      onSuccess: () => {
        notification.success({ message: 'Equipment deleted.' });
      },
      onError: (err) => {
        notification.error({
          message: 'Delete failed',
          description: err instanceof Error ? err.message : 'Unknown error.',
        });
      },
    });
  };

  const columns: ColumnsType<Equipment> = [
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
            onClick={() => navigate(`/equipments/${row.id}`)}
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
        title="Equipments"
        extra={
          <Link to="/equipments/new">
            <Button type="primary" icon={<PlusOutlined />}>
              New equipment
            </Button>
          </Link>
        }
      />
      <DataTable<Equipment>
        columns={columns}
        rows={data}
        isLoading={isLoading}
        error={error}
        emptyText="No equipment yet."
        onChange={handleChange}
      />
    </>
  );
};
