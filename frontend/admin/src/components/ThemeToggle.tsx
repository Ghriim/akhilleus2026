import { Button, Tooltip } from 'antd';
import { MoonOutlined, SunOutlined } from '@ant-design/icons';
import { useTheme } from '@/theme/useTheme';

export const ThemeToggle = () => {
  const { mode, toggle } = useTheme();
  const nextMode = mode === 'light' ? 'dark' : 'light';

  return (
    <Tooltip title={`Switch to ${nextMode} theme`}>
      <Button
        type="text"
        shape="circle"
        icon={mode === 'light' ? <MoonOutlined /> : <SunOutlined />}
        onClick={toggle}
        aria-label={`Switch to ${nextMode} theme`}
      />
    </Tooltip>
  );
};
