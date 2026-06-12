import { Button } from './Button';

interface PaginationProps {
  page: number;
  perPage: number;
  total: number;
  onPageChange: (page: number) => void;
}

export function Pagination({ page, perPage, total, onPageChange }: PaginationProps) {
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  const hasPrev = page > 1;
  const hasNext = page < totalPages;
  return (
    <div className="flex items-center justify-between gap-3 mt-4 text-(length:--text-sm) text-(--color-text-muted)">
      <span>
        Page {page} / {totalPages} · {total} résultat{total > 1 ? 's' : ''}
      </span>
      <div className="flex gap-2">
        <Button size="sm" variant="secondary" disabled={!hasPrev} onClick={() => onPageChange(page - 1)}>
          Précédent
        </Button>
        <Button size="sm" variant="secondary" disabled={!hasNext} onClick={() => onPageChange(page + 1)}>
          Suivant
        </Button>
      </div>
    </div>
  );
}
