import { Button, Space, notification } from 'antd';
import { PlusOutlined, EditOutlined } from '@ant-design/icons';
import { Link, useNavigate } from 'react-router-dom';
import type { ColumnsType } from 'antd/es/table';
import { DataTable } from '@/components/DataTable';
import { DeleteConfirmButton } from '@/components/DeleteConfirmButton';
import { PageHeader } from '@/components/PageHeader';
import { useDeleteFrontThemeMutation, useFrontThemesQuery } from './hooks';
import type { FrontTheme } from './types';

export const FrontThemeListPage = () => {
  const { data, isLoading, error } = useFrontThemesQuery();
  const deleteMutation = useDeleteFrontThemeMutation();
  const navigate = useNavigate();

  const handleDelete = (id: string) => {
    deleteMutation.mutate(id, {
      onSuccess: () => {
        notification.success({ message: 'Theme deleted.' });
      },
      onError: (err) => {
        notification.error({
          message: 'Delete failed',
          description: err instanceof Error ? err.message : 'Unknown error.',
        });
      },
    });
  };

  const columns: ColumnsType<FrontTheme> = [
    {
      title: 'Preview',
      dataIndex: 'imagePreviewUrl',
      key: 'imagePreviewUrl',
      width: 96,
      render: (value: string | null) =>
        value !== null ? (
          <img src={value} alt="" style={{ width: 64, height: 40, objectFit: 'cover' }} />
        ) : (
          '—'
        ),
    },
    {
      title: 'Name',
      dataIndex: 'name',
      key: 'name',
    },
    {
      title: 'Description',
      dataIndex: 'description',
      key: 'description',
      render: (value: string | null) => value ?? '—',
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
            onClick={() => navigate(`/front-themes/${row.id}`)}
          >
            Edit
          </Button>
          <DeleteConfirmButton
            onConfirm={() => handleDelete(row.id)}
            isLoading={deleteMutation.isPending && deleteMutation.variables === row.id}
            title={`Delete theme "${row.name}"?`}
          />
        </Space>
      ),
    },
  ];

  return (
    <>
      <PageHeader
        title="Front themes"
        extra={
          <Link to="/front-themes/new">
            <Button type="primary" icon={<PlusOutlined />}>
              New theme
            </Button>
          </Link>
        }
      />
      <DataTable<FrontTheme>
        columns={columns}
        rows={data}
        isLoading={isLoading}
        error={error}
        emptyText="No themes yet."
      />
    </>
  );
};
