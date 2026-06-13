import { Form, InputNumber, Typography } from 'antd';
import { EntityFormShell } from '@/components/EntityFormShell';
import type { LevelBracketFormValues } from './types';

interface LevelBracketFormProps {
  initialValues?: Partial<LevelBracketFormValues>;
  onSubmit: (values: LevelBracketFormValues) => Promise<unknown>;
  isSubmitting: boolean;
  submitError: unknown;
  submitLabel?: string;
  cancelTo?: string;
}

export const LevelBracketForm = ({
  initialValues,
  onSubmit,
  isSubmitting,
  submitError,
  submitLabel,
  cancelTo,
}: LevelBracketFormProps) => {
  const [form] = Form.useForm<LevelBracketFormValues>();

  return (
    <EntityFormShell<LevelBracketFormValues>
      form={form}
      onSubmit={onSubmit}
      isSubmitting={isSubmitting}
      submitError={submitError}
      {...(initialValues !== undefined ? { initialValues } : {})}
      {...(submitLabel !== undefined ? { submitLabel } : {})}
      {...(cancelTo !== undefined ? { cancelTo } : {})}
    >
      <Typography.Paragraph type="secondary">
        Marginal cost to reach level <em>n</em>: <code>a × n^k + b</code>. Brackets must be contiguous and
        non-overlapping, the first must start at level 1, and exactly one (the last) is open-ended.
      </Typography.Paragraph>
      <Form.Item
        label="From level"
        name="fromLevel"
        rules={[{ required: true, message: 'From level is required.' }]}
      >
        <InputNumber min={1} style={{ width: '100%' }} autoFocus />
      </Form.Item>
      <Form.Item
        label="To level"
        name="toLevel"
        tooltip="Leave empty for the open-ended (last) bracket."
      >
        <InputNumber min={1} style={{ width: '100%' }} placeholder="∞ (open-ended — leave empty)" />
      </Form.Item>
      <Form.Item
        label="Coefficient a"
        name="coefficientA"
        rules={[{ required: true, message: 'Coefficient a is required.' }]}
      >
        <InputNumber style={{ width: '100%' }} />
      </Form.Item>
      <Form.Item
        label="Exponent k"
        name="exponentK"
        rules={[{ required: true, message: 'Exponent k is required.' }]}
      >
        <InputNumber min={1} style={{ width: '100%' }} />
      </Form.Item>
      <Form.Item
        label="Offset b"
        name="offsetB"
        rules={[{ required: true, message: 'Offset b is required.' }]}
      >
        <InputNumber style={{ width: '100%' }} />
      </Form.Item>
    </EntityFormShell>
  );
};
