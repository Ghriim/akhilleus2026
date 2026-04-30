import { Checkbox, Form, Input, Row, Col, Select, Typography } from 'antd';
import { EntityFormShell } from '@/components/EntityFormShell';
import { LoadingState } from '@/components/LoadingState';
import { ErrorState } from '@/components/ErrorState';
import { useEquipmentsQuery } from '@/features/equipments/hooks';
import { useMusclesQuery } from '@/features/muscles/hooks';
import type { MovementFormValues } from './types';

const TRACKING_FIELDS: Array<{ name: keyof MovementFormValues; label: string }> = [
  { name: 'tracksRepetitions', label: 'Repetitions' },
  { name: 'tracksWeight', label: 'Weight' },
  { name: 'tracksDuration', label: 'Duration' },
  { name: 'tracksDistance', label: 'Distance' },
  { name: 'tracksInclinePercent', label: 'Incline %' },
  { name: 'tracksInclineMeters', label: 'Incline meters' },
];

interface MovementFormProps {
  initialValues?: Partial<MovementFormValues>;
  onSubmit: (values: MovementFormValues) => Promise<unknown>;
  isSubmitting: boolean;
  submitError: unknown;
  submitLabel?: string;
  cancelTo?: string;
}

export const MovementForm = ({
  initialValues,
  onSubmit,
  isSubmitting,
  submitError,
  submitLabel,
  cancelTo,
}: MovementFormProps) => {
  const [form] = Form.useForm<MovementFormValues>();
  const musclesQuery = useMusclesQuery();
  const equipmentsQuery = useEquipmentsQuery();

  if (musclesQuery.isLoading || equipmentsQuery.isLoading) {
    return <LoadingState label="Loading dependencies…" />;
  }
  if (musclesQuery.error !== null && musclesQuery.error !== undefined) {
    return <ErrorState error={musclesQuery.error} title="Failed to load muscles" />;
  }
  if (equipmentsQuery.error !== null && equipmentsQuery.error !== undefined) {
    return <ErrorState error={equipmentsQuery.error} title="Failed to load equipments" />;
  }

  const muscleOptions = (musclesQuery.data ?? []).map((m) => ({
    value: m.id,
    label: m.label,
  }));
  const equipmentOptions = (equipmentsQuery.data ?? []).map((e) => ({
    value: e.id,
    label: e.label,
  }));

  const validateAtLeastOneTrackingField = ({ getFieldsValue }: { getFieldsValue: () => unknown }) => ({
    validator: () => {
      const values = getFieldsValue() as MovementFormValues;
      const hasOne = TRACKING_FIELDS.some(({ name }) => Boolean(values[name]));
      return hasOne
        ? Promise.resolve()
        : Promise.reject(new Error('Enable at least one tracking field.'));
    },
  });

  const defaults: Partial<MovementFormValues> = {
    secondaryMuscleIds: [],
    equipmentIds: [],
    tracksRepetitions: false,
    tracksWeight: false,
    tracksDuration: false,
    tracksDistance: false,
    tracksInclinePercent: false,
    tracksInclineMeters: false,
    ...(initialValues ?? {}),
  };

  return (
    <EntityFormShell<MovementFormValues>
      form={form}
      onSubmit={onSubmit}
      isSubmitting={isSubmitting}
      submitError={submitError}
      initialValues={defaults}
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

      <Form.Item
        label="Main muscle"
        name="mainMuscleId"
        rules={[{ required: true, message: 'Main muscle is required.' }]}
      >
        <Select
          showSearch
          options={muscleOptions}
          optionFilterProp="label"
          placeholder="Pick the primary muscle"
        />
      </Form.Item>

      <Form.Item label="Secondary muscles" name="secondaryMuscleIds">
        <Select mode="multiple" options={muscleOptions} optionFilterProp="label" allowClear />
      </Form.Item>

      <Form.Item label="Equipments" name="equipmentIds">
        <Select mode="multiple" options={equipmentOptions} optionFilterProp="label" allowClear />
      </Form.Item>

      <Typography.Title level={5} style={{ marginTop: 16 }}>
        Tracking fields
      </Typography.Title>
      <Form.Item
        name="__trackingValidator"
        dependencies={TRACKING_FIELDS.map(({ name }) => name)}
        rules={[validateAtLeastOneTrackingField]}
      >
        <Row gutter={[16, 8]}>
          {TRACKING_FIELDS.map(({ name, label }) => (
            <Col xs={12} md={8} key={name}>
              <Form.Item name={name} valuePropName="checked" noStyle>
                <Checkbox>{label}</Checkbox>
              </Form.Item>
            </Col>
          ))}
        </Row>
      </Form.Item>
    </EntityFormShell>
  );
};
