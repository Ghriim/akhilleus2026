import { Button, Card, Form, InputNumber, Skeleton, Typography, notification } from 'antd';
import { useEffect } from 'react';
import { ApiError } from '@/api/httpClient';
import { ErrorState } from '@/components/ErrorState';
import {
  useLevelingConfigQuery,
  useUpdateLevelingConfigMutation,
  type LevelingConfigFormValues,
} from './levelingConfig';

const MIN_XP_PER_WORKOUT_MINUTE = 50;

export const LevelingConfigCard = () => {
  const { data, isLoading, error } = useLevelingConfigQuery();
  const updateMutation = useUpdateLevelingConfigMutation();
  const [form] = Form.useForm<LevelingConfigFormValues>();

  useEffect(() => {
    if (data !== undefined) {
      form.setFieldsValue({ xpPerWorkoutMinute: data.xpPerWorkoutMinute });
    }
  }, [data, form]);

  const handleFinish = (values: LevelingConfigFormValues) => {
    form.setFields([{ name: 'xpPerWorkoutMinute', errors: [] }]);
    updateMutation.mutate(values, {
      onSuccess: () => {
        notification.success({ message: 'Global config saved.' });
      },
      onError: (err) => {
        if (err instanceof ApiError && err.violations.xpPerWorkoutMinute !== undefined) {
          form.setFields([{ name: 'xpPerWorkoutMinute', errors: err.violations.xpPerWorkoutMinute }]);
          return;
        }
        notification.error({
          message: 'Save failed',
          description: err instanceof Error ? err.message : 'Unknown error.',
        });
      },
    });
  };

  return (
    <Card title="Global config" style={{ marginBottom: 24 }}>
      <Typography.Paragraph type="secondary">
        XP granted per minute of a completed workout (minimum {MIN_XP_PER_WORKOUT_MINUTE}).
      </Typography.Paragraph>
      {error !== null ? (
        <ErrorState error={error} title="Could not load global config" />
      ) : isLoading ? (
        <Skeleton active paragraph={{ rows: 1 }} />
      ) : (
        <Form<LevelingConfigFormValues>
          form={form}
          layout="inline"
          onFinish={handleFinish}
          disabled={updateMutation.isPending}
        >
          <Form.Item
            name="xpPerWorkoutMinute"
            label="XP per workout minute"
            rules={[
              { required: true, message: 'A value is required.' },
              {
                type: 'number',
                min: MIN_XP_PER_WORKOUT_MINUTE,
                message: `Must be at least ${MIN_XP_PER_WORKOUT_MINUTE}.`,
              },
            ]}
          >
            <InputNumber min={MIN_XP_PER_WORKOUT_MINUTE} step={1} />
          </Form.Item>
          <Form.Item>
            <Button type="primary" htmlType="submit" loading={updateMutation.isPending}>
              Save
            </Button>
          </Form.Item>
        </Form>
      )}
    </Card>
  );
};
