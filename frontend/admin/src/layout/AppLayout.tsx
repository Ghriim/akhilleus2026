import { Layout } from 'antd';
import { Outlet } from 'react-router-dom';
import { AppHeader } from './AppHeader';
import { AppSider } from './AppSider';

const { Content } = Layout;

export const AppLayout = () => (
  <Layout style={{ minHeight: '100vh' }}>
    <AppSider />
    <Layout>
      <AppHeader />
      <Content style={{ margin: 24 }}>
        <Outlet />
      </Content>
    </Layout>
  </Layout>
);
