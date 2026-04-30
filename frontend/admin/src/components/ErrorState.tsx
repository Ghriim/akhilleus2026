import { Alert } from 'antd';
import { ApiError } from '@/api/httpClient';

interface ErrorStateProps {
  error: unknown;
  title?: string;
}

const messageFromError = (error: unknown): string => {
  if (error instanceof ApiError) {
    return error.message;
  }
  if (error instanceof Error) {
    return error.message;
  }
  return 'An unexpected error occurred.';
};

export const ErrorState = ({ error, title = 'Something went wrong' }: ErrorStateProps) => (
  <Alert
    type="error"
    showIcon
    message={title}
    description={messageFromError(error)}
    style={{ margin: 16 }}
  />
);
