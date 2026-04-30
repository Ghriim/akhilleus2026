import { Typography, Space } from 'antd';
import type { ReactNode } from 'react';

interface PageHeaderProps {
  title: string;
  extra?: ReactNode;
}

export const PageHeader = ({ title, extra }: PageHeaderProps) => (
  <Space
    style={{
      width: '100%',
      justifyContent: 'space-between',
      marginBottom: 16,
    }}
    align="center"
  >
    <Typography.Title level={2} style={{ margin: 0 }}>
      {title}
    </Typography.Title>
    {extra ?? null}
  </Space>
);
