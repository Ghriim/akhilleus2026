import { Form, Input, Upload, type UploadFile } from 'antd';
import { UploadOutlined } from '@ant-design/icons';
import { Button } from 'antd';
import { EntityFormShell } from '@/components/EntityFormShell';
import type { FrontThemeFormValues } from './types';

interface FrontThemeFormProps {
  initialValues?: Partial<FrontThemeFormValues>;
  /** Existing stored preview shown above the upload on the edit page. */
  currentImageUrl?: string | null;
  onSubmit: (values: FrontThemeFormValues) => Promise<unknown>;
  isSubmitting: boolean;
  submitError: unknown;
  submitLabel?: string;
  cancelTo?: string;
}

const normFile = (event: { fileList?: UploadFile[] } | UploadFile[]): UploadFile[] => {
  if (Array.isArray(event)) {
    return event;
  }

  return event.fileList ?? [];
};

export const FrontThemeForm = ({
  initialValues,
  currentImageUrl,
  onSubmit,
  isSubmitting,
  submitError,
  submitLabel,
  cancelTo,
}: FrontThemeFormProps) => {
  const [form] = Form.useForm<FrontThemeFormValues>();

  return (
    <EntityFormShell<FrontThemeFormValues>
      form={form}
      onSubmit={onSubmit}
      isSubmitting={isSubmitting}
      submitError={submitError}
      {...(initialValues !== undefined ? { initialValues } : {})}
      {...(submitLabel !== undefined ? { submitLabel } : {})}
      {...(cancelTo !== undefined ? { cancelTo } : {})}
    >
      <Form.Item
        label="Name"
        name="name"
        rules={[{ required: true, message: 'Name is required.' }]}
      >
        <Input autoFocus placeholder="e.g. System" />
      </Form.Item>
      <Form.Item label="Description" name="description">
        <Input.TextArea rows={3} placeholder="Optional description" />
      </Form.Item>
      {currentImageUrl !== undefined && currentImageUrl !== null ? (
        <Form.Item label="Current preview">
          <img
            src={currentImageUrl}
            alt="Current preview"
            style={{ maxWidth: 240, maxHeight: 160, display: 'block' }}
          />
        </Form.Item>
      ) : null}
      <Form.Item
        label="Preview image"
        name="image"
        valuePropName="fileList"
        getValueFromEvent={normFile}
        tooltip="PNG or JPEG, 2 MB max."
      >
        <Upload
          accept="image/png,image/jpeg"
          maxCount={1}
          listType="picture"
          beforeUpload={() => false}
        >
          <Button icon={<UploadOutlined />}>Select image</Button>
        </Upload>
      </Form.Item>
    </EntityFormShell>
  );
};
