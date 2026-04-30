import { Spin } from 'antd';

interface LoadingStateProps {
  label?: string;
}

export const LoadingState = ({ label = 'Loading…' }: LoadingStateProps) => (
  <div style={{ display: 'flex', justifyContent: 'center', padding: 48 }}>
    <Spin tip={label} size="large">
      <div style={{ minWidth: 120, minHeight: 24 }} />
    </Spin>
  </div>
);
