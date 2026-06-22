import { NavLink, useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { PlayerLevelBadge } from '@/components/layout/PlayerLevelBadge';
import { useAuth } from '@/auth/useAuth';
import { cn } from '@/lib/cn';

const LINKS: { to: string; label: string; end: boolean }[] = [
  { to: '/', label: 'Tableau de bord', end: true },
  { to: '/planning', label: 'Planning', end: false },
  { to: '/history', label: 'Historique', end: false },
  { to: '/movements', label: 'Mouvements', end: false },
  { to: '/achievements', label: 'Records', end: false },
  { to: '/statistics', label: 'Statistiques', end: false },
  { to: '/quests/unique', label: 'Quêtes uniques', end: false },
  { to: '/leveling/journal', label: 'Journal XP', end: false },
];

export function NavBar() {
  const { logout, isAuthenticated } = useAuth();
  const navigate = useNavigate();
  if (!isAuthenticated) return null;

  return (
    <header className="sticky top-0 z-10 bg-(--color-surface) border-b border-(--color-border)">
      <div className="mx-auto max-w-6xl flex items-center justify-between gap-4 px-4 py-3">
        <div className="flex items-center gap-4">
          <NavLink
            to="/"
            className="text-(length:--text-xl) font-(--font-display) font-semibold text-(--color-text)"
          >
            Akhilleus
          </NavLink>
          <PlayerLevelBadge />
        </div>
        <nav className="flex gap-1 overflow-x-auto">
          {LINKS.map((l) => (
            <NavLink
              key={l.to}
              to={l.to}
              end={l.end}
              className={({ isActive }) =>
                cn(
                  'whitespace-nowrap rounded-(--radius-md) px-3 py-1.5 text-(length:--text-sm)',
                  isActive
                    ? 'bg-(--color-primary-soft) text-(--color-primary) font-semibold'
                    : 'text-(--color-text-muted) hover:bg-(--color-surface-muted)',
                )
              }
            >
              {l.label}
            </NavLink>
          ))}
        </nav>
        <Button
          variant="ghost"
          size="sm"
          onClick={async () => {
            await logout();
            navigate('/login', { replace: true });
          }}
        >
          Déconnexion
        </Button>
      </div>
    </header>
  );
}
