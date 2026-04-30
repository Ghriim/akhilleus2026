import { Button, Layout, Space, Typography, theme } from 'antd';
import { LogoutOutlined } from '@ant-design/icons';
import { useAuth } from '@/auth/useAuth';
import { ThemeToggle } from '@/components/ThemeToggle';

const { Header } = Layout;

export const AppHeader = () => {
  const { identity, logout } = useAuth();
  const { token } = theme.useToken();

  return (
    <Header
      style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: '0 24px',
        background: token.colorBgContainer,
        color: token.colorText,
        borderBottom: `1px solid ${token.colorBorderSecondary}`,
      }}
    >
      <Typography.Title level={4} style={{ margin: 0, color: token.colorText }}>
        Akhilleus Admin
      </Typography.Title>
      <Space>
        {identity !== null ? (
          <Typography.Text type="secondary">{identity.username}</Typography.Text>
        ) : null}
        <ThemeToggle />
        <Button
          type="text"
          icon={<LogoutOutlined />}
          onClick={() => {
            void logout();
          }}
          aria-label="Log out"
        >
          Log out
        </Button>
      </Space>
    </Header>
  );
};
