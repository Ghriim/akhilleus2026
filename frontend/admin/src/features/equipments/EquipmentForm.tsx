import { Form, Input } from 'antd';
import { EntityFormShell } from '@/components/EntityFormShell';
import type { EquipmentFormValues } from './types';

interface EquipmentFormProps {
  initialValues?: Partial<EquipmentFormValues>;
  onSubmit: (values: EquipmentFormValues) => Promise<unknown>;
  isSubmitting: boolean;
  submitError: unknown;
  submitLabel?: string;
  cancelTo?: string;
}

export const EquipmentForm = ({
  initialValues,
  onSubmit,
  isSubmitting,
  submitError,
  submitLabel,
  cancelTo,
}: EquipmentFormProps) => {
  const [form] = Form.useForm<EquipmentFormValues>();

  return (
    <EntityFormShell<EquipmentFormValues>
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
