import { Form, Input } from 'antd';
import { EntityFormShell } from '@/components/EntityFormShell';
import type { MuscleFormValues } from './types';

interface MuscleFormProps {
  initialValues?: Partial<MuscleFormValues>;
  onSubmit: (values: MuscleFormValues) => Promise<unknown>;
  isSubmitting: boolean;
  submitError: unknown;
  submitLabel?: string;
  cancelTo?: string;
}

export const MuscleForm = ({
  initialValues,
  onSubmit,
  isSubmitting,
  submitError,
  submitLabel,
  cancelTo,
}: MuscleFormProps) => {
  const [form] = Form.useForm<MuscleFormValues>();

  return (
    <EntityFormShell<MuscleFormValues>
      form={form}
      onSubmit={onSubmit}
      isSubmitting={isSubmitting}
      submitError={submitError}
      {...(initialValues !== undefined ? { initialValues } : {})}
      {...(submitLabel !== undefined ? { submitLabel } : {})}
      {...(cancelTo !== undefined ? { cancelTo } : {})}
    >
      <Form.Item
        label="Label"
        name="label"
        rules={[{ required: true, message: 'Label is required.' }]}
      >
        <Input autoFocus />
      </Form.Item>
    </EntityFormShell>
  );
};
