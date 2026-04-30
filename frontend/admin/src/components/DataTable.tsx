import { Table } from 'antd';
import type { ColumnsType, TableProps } from 'antd/es/table';
import { ErrorState } from './ErrorState';

interface DataTableProps<TRow extends { id: string }> {
  columns: ColumnsType<TRow>;
  rows: TRow[] | undefined;
  isLoading: boolean;
  error: unknown;
  emptyText?: string;
  onChange?: TableProps<TRow>['onChange'];
}

export const DataTable = <TRow extends { id: string }>({
  columns,
  rows,
  isLoading,
  error,
  emptyText = 'No rows yet.',
  onChange,
}: DataTableProps<TRow>) => {
  if (error !== null && error !== undefined) {
    return <ErrorState error={error} title="Failed to load the list" />;
  }

  return (
    <Table<TRow>
      rowKey={(row) => row.id}
      columns={columns}
      dataSource={rows}
      loading={isLoading}
      pagination={false}
      locale={{ emptyText }}
      {...(onChange !== undefined ? { onChange } : {})}
    />
  );
};
