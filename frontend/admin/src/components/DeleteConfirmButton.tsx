import { Button, Popconfirm } from 'antd';
import { DeleteOutlined } from '@ant-design/icons';

interface DeleteConfirmButtonProps {
  onConfirm: () => void;
  isLoading?: boolean;
  label?: string;
  title?: string;
  description?: string;
}

export const DeleteConfirmButton = ({
  onConfirm,
  isLoading = false,
  label = 'Delete',
  title = 'Delete this row?',
  description = 'This action cannot be undone.',
}: DeleteConfirmButtonProps) => (
  <Popconfirm
    title={title}
    description={description}
    okText="Delete"
    okButtonProps={{ danger: true, loading: isLoading }}
    cancelText="Cancel"
    onConfirm={onConfirm}
  >
    <Button danger icon={<DeleteOutlined />} type="text" loading={isLoading}>
      {label}
    </Button>
  </Popconfirm>
);
