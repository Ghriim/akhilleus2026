import { Button, Form, Space, type FormInstance } from 'antd';
import type { ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import { ApiError } from '@/api/httpClient';
import type { ApiViolation } from '@/api/httpClient';
import { ErrorState } from './ErrorState';

interface EntityFormShellProps<TValues> {
  form: FormInstance<TValues>;
  onSubmit: (values: TValues) => Promise<unknown> | unknown;
  isSubmitting: boolean;
  submitError: unknown;
  initialValues?: Partial<TValues>;
  submitLabel?: string;
  cancelTo?: string;
  children: ReactNode;
}

const applyViolationsToForm = <TValues,>(
  form: FormInstance<TValues>,
  violations: ApiViolation,
): void => {
  const fields = Object.entries(violations).map(([name, errors]) => ({
    name,
    errors,
  })) as Parameters<FormInstance<TValues>['setFields']>[0];
  if (fields.length > 0) {
    form.setFields(fields);
  }
};

export const EntityFormShell = <TValues extends object>({
  form,
  onSubmit,
  isSubmitting,
  submitError,
  initialValues,
  submitLabel = 'Save',
  cancelTo,
  children,
}: EntityFormShellProps<TValues>) => {
  const navigate = useNavigate();

  const handleFinish = async (values: TValues) => {
    form.setFields(
      Object.keys(values).map((name) => ({
        name,
        errors: [] as string[],
      })) as Parameters<FormInstance<TValues>['setFields']>[0],
    );
    try {
      await onSubmit(values);
    } catch (error) {
      if (error instanceof ApiError && Object.keys(error.violations).length > 0) {
        applyViolationsToForm(form, error.violations);
      }
      throw error;
    }
  };

  return (
    <>
      {submitError !== null && submitError !== undefined ? (
        <ErrorState error={submitError} title="Save failed" />
      ) : null}
      <Form<TValues>
        form={form}
        layout="vertical"
        {...(initialValues !== undefined ? { initialValues: initialValues as TValues } : {})}
        onFinish={handleFinish}
        disabled={isSubmitting}
      >
        {children}
        <Form.Item style={{ marginTop: 24 }}>
          <Space>
            <Button type="primary" htmlType="submit" loading={isSubmitting}>
              {submitLabel}
            </Button>
            {cancelTo !== undefined ? (
              <Button onClick={() => navigate(cancelTo)} disabled={isSubmitting}>
                Cancel
              </Button>
            ) : null}
          </Space>
        </Form.Item>
      </Form>
    </>
  );
};
