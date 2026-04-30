import { useState } from 'react';
import { Button, Card, Form, Input, Typography } from 'antd';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '@/auth/useAuth';
import { ApiError } from '@/api/httpClient';
import { ErrorState } from '@/components/ErrorState';

interface LoginValues {
  email: string;
  password: string;
}

interface LocationState {
  from?: string;
}

export const LoginPage = () => {
  const { login, isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [error, setError] = useState<unknown>(null);
  const [submitting, setSubmitting] = useState(false);

  if (isAuthenticated) {
    const fromState = location.state as LocationState | null;
    return <Navigate to={fromState?.from ?? '/equipments'} replace />;
  }

  const handleSubmit = async (values: LoginValues) => {
    setSubmitting(true);
    setError(null);
    try {
      await login(values.email, values.password);
      navigate('/equipments', { replace: true });
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) {
        setError(new Error('Invalid email or password.'));
      } else {
        setError(err);
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: 16,
      }}
    >
      <Card style={{ width: '100%', maxWidth: 400 }}>
        <Typography.Title level={3} style={{ marginBottom: 8 }}>
          Akhilleus Admin
        </Typography.Title>
        <Typography.Paragraph type="secondary">
          Sign in to manage reference data.
        </Typography.Paragraph>
        {error !== null ? <ErrorState error={error} title="Sign-in failed" /> : null}
        <Form<LoginValues> layout="vertical" onFinish={handleSubmit} disabled={submitting}>
          <Form.Item
            label="Email"
            name="email"
            rules={[
              { required: true, message: 'Email is required.' },
              { type: 'email', message: 'Email is not a valid address.' },
            ]}
          >
            <Input autoComplete="username" autoFocus />
          </Form.Item>
          <Form.Item
            label="Password"
            name="password"
            rules={[{ required: true, message: 'Password is required.' }]}
          >
            <Input.Password autoComplete="current-password" />
          </Form.Item>
          <Form.Item>
            <Button type="primary" htmlType="submit" loading={submitting} block>
              Sign in
            </Button>
          </Form.Item>
        </Form>
      </Card>
    </div>
  );
};
