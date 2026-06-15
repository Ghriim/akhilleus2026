import { Layout, Menu } from 'antd';
import { ToolOutlined, ThunderboltOutlined, ApartmentOutlined, LineChartOutlined, TrophyOutlined } from '@ant-design/icons';
import { useLocation, useNavigate } from 'react-router-dom';

const { Sider } = Layout;

const items = [
  {
    key: '/equipments',
    icon: <ToolOutlined />,
    label: 'Equipments',
  },
  {
    key: '/muscles',
    icon: <ApartmentOutlined />,
    label: 'Muscles',
  },
  {
    key: '/movements',
    icon: <ThunderboltOutlined />,
    label: 'Movements',
  },
  {
    key: '/level-brackets',
    icon: <LineChartOutlined />,
    label: 'Level brackets',
  },
  {
    key: '/quests',
    icon: <TrophyOutlined />,
    label: 'Quests',
  },
];

const findActiveKey = (pathname: string): string => {
  const match = items.find((item) => pathname.startsWith(item.key));
  return match?.key ?? '';
};

export const AppSider = () => {
  const location = useLocation();
  const navigate = useNavigate();

  return (
    <Sider breakpoint="lg" collapsible>
      <div
        style={{
          height: 64,
          margin: 16,
          textAlign: 'center',
          color: 'white',
          fontWeight: 600,
          letterSpacing: '0.05em',
        }}
      >
        Akhilleus
      </div>
      <Menu
        theme="dark"
        mode="inline"
        selectedKeys={[findActiveKey(location.pathname)]}
        items={items}
        onClick={({ key }) => navigate(key)}
      />
    </Sider>
  );
};
