import { DatePicker, Form, Input, InputNumber, Select } from 'antd';
import { EntityFormShell } from '@/components/EntityFormShell';
import type { QuestFormValues } from './types';

const KIND_OPTIONS = [
  { value: 'AUTOMATIC', label: 'Automatic (tracks a metric)' },
  { value: 'MANUAL', label: 'Manual (claimed directly)' },
];

const PERIODICITY_OPTIONS = [
  { value: 'UNIQUE', label: 'Unique' },
  { value: 'DAILY', label: 'Daily' },
  { value: 'WEEKLY', label: 'Weekly' },
  { value: 'MONTHLY', label: 'Monthly' },
];

const METRIC_OPTIONS = [
  { value: 'STEPS_DAILY', label: 'Steps (daily)' },
  { value: 'HYDRATION_ML_DAILY', label: 'Hydration mL (daily)' },
  { value: 'SLEEP_DURATION_MINUTES', label: 'Sleep duration (minutes)' },
  { value: 'WORKOUT_COUNT', label: 'Workout count' },
  { value: 'WORKOUT_DURATION_MINUTES', label: 'Workout duration (minutes)' },
];

interface QuestFormProps {
  initialValues?: Partial<QuestFormValues>;
  onSubmit: (values: QuestFormValues) => Promise<unknown>;
  isSubmitting: boolean;
  submitError: unknown;
  submitLabel?: string;
  cancelTo?: string;
}

export const QuestForm = ({
  initialValues,
  onSubmit,
  isSubmitting,
  submitError,
  submitLabel,
  cancelTo,
}: QuestFormProps) => {
  const [form] = Form.useForm<QuestFormValues>();
  const kind = Form.useWatch('kind', form);
  const isAutomatic = kind === 'AUTOMATIC';

  return (
    <EntityFormShell<QuestFormValues>
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
        <Input autoFocus placeholder="e.g. Hydrate 1.5 L today" />
      </Form.Item>
      <Form.Item
        label="Kind"
        name="kind"
        rules={[{ required: true, message: 'Kind is required.' }]}
      >
        <Select options={KIND_OPTIONS} />
      </Form.Item>
      {isAutomatic ? (
        <>
          <Form.Item
            label="Metric"
            name="metric"
            rules={[{ required: true, message: 'An automatic quest requires a metric.' }]}
          >
            <Select options={METRIC_OPTIONS} placeholder="Pick the tracked metric" />
          </Form.Item>
          <Form.Item
            label="Target value"
            name="targetValue"
            rules={[{ required: true, message: 'An automatic quest requires a target value.' }]}
          >
            <InputNumber min={1} style={{ width: '100%' }} placeholder="e.g. 1500" />
          </Form.Item>
        </>
      ) : null}
      <Form.Item
        label="Periodicity"
        name="periodicity"
        rules={[{ required: true, message: 'Periodicity is required.' }]}
      >
        <Select options={PERIODICITY_OPTIONS} />
      </Form.Item>
      <Form.Item
        label="Rewarded XP"
        name="rewardedXp"
        rules={[{ required: true, message: 'Rewarded XP is required.' }]}
      >
        <InputNumber min={1} style={{ width: '100%' }} />
      </Form.Item>
      <Form.Item
        label="Start date"
        name="dateStart"
        rules={[{ required: true, message: 'Start date is required.' }]}
      >
        <DatePicker showTime style={{ width: '100%' }} />
      </Form.Item>
      <Form.Item
        label="End date"
        name="dateEnd"
        tooltip="Leave empty for an open-ended quest."
      >
        <DatePicker showTime style={{ width: '100%' }} placeholder="Open-ended — leave empty" />
      </Form.Item>
    </EntityFormShell>
  );
};
