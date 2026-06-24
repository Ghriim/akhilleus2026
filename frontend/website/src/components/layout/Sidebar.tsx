import { useState } from 'react';
import type { ComponentType, SVGProps } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import {
  CalendarIcon,
  ChartIcon,
  ChevronsLeftIcon,
  DumbbellIcon,
  FlagIcon,
  GearIcon,
  HomeIcon,
  LogoutIcon,
  PlusIcon,
  StarIcon,
} from '@/components/icons';
import { PlayerLevelBadge } from '@/components/layout/PlayerLevelBadge';
import { IconButton } from '@/components/ui/IconButton';
import { useAuth } from '@/auth/useAuth';
import { cn } from '@/lib/cn';

const STORAGE_KEY = 'sidebar:collapsed';

type IconType = ComponentType<SVGProps<SVGSVGElement>>;

interface LeafItem {
  to: string;
  label: string;
  end?: boolean;
  Icon: IconType;
}

interface MenuSection {
  label: string;
  items: LeafItem[];
}

const DASHBOARD: LeafItem = { to: '/', label: 'Tableau de bord', end: true, Icon: HomeIcon };

const SECTIONS: MenuSection[] = [
  {
    label: 'Entraînements',
    items: [
      { to: '/workouts/new', label: 'Nouvelle séance', Icon: PlusIcon },
      { to: '/planning', label: 'Planning', Icon: CalendarIcon },
      { to: '/movements', label: 'Mouvements', Icon: DumbbellIcon },
      { to: '/statistics', label: 'Statistiques', Icon: ChartIcon },
    ],
  },
  {
    label: 'Journal',
    items: [
      { to: '/leveling/journal', label: 'XP', Icon: StarIcon },
      { to: '/quests/unique', label: 'Quêtes', Icon: FlagIcon },
    ],
  },
];

const SETTINGS: LeafItem = { to: '/settings', label: 'Réglages', Icon: GearIcon };

export function Sidebar() {
  const { logout, isAuthenticated } = useAuth();
  const navigate = useNavigate();

  const [collapsed, setCollapsed] = useState<boolean>(() => {
    try {
      return '1' === localStorage.getItem(STORAGE_KEY);
    } catch {
      return false;
    }
  });

  if (!isAuthenticated) return null;

  const setCollapsedPersisted = (next: boolean) => {
    setCollapsed(next);
    try {
      localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
    } catch {
      /* ignore */
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  const leafClass = (isActive: boolean) =>
    cn(
      'flex items-center gap-3 rounded-(--radius-surface) px-3 py-2 text-(length:--text-sm)',
      collapsed && 'justify-center px-0',
      isActive
        ? 'bg-(--color-primary-soft) font-semibold text-(--color-primary) system:shadow-(--bar-glow)'
        : 'text-(--color-text-muted) hover:bg-(--color-surface-muted)',
    );

  const renderLeaf = (item: LeafItem) => (
    <NavLink
      to={item.to}
      end={item.end ?? false}
      title={collapsed ? item.label : undefined}
      className={({ isActive }) => leafClass(isActive)}
    >
      <item.Icon width={20} height={20} className="shrink-0" />
      {!collapsed && <span className="truncate">{item.label}</span>}
    </NavLink>
  );

  return (
    <aside
      className={cn(
        'sticky top-0 z-10 flex h-dvh shrink-0 flex-col border-r border-(--color-border) bg-(--color-surface-2) transition-[width] duration-200',
        collapsed ? 'w-16' : 'w-60',
      )}
    >
      <div className="flex items-center gap-2 px-3 py-3">
        {!collapsed && (
          <NavLink
            to="/"
            className="flex-1 truncate text-(length:--text-xl) font-(--font-display) font-semibold text-(--color-text)"
          >
            Akhilleus
          </NavLink>
        )}
        <IconButton
          label={collapsed ? 'Déplier le menu' : 'Replier le menu'}
          onClick={() => setCollapsedPersisted(!collapsed)}
          className={cn(collapsed && 'mx-auto')}
        >
          <ChevronsLeftIcon className={cn('transition-transform', collapsed && 'rotate-180')} />
        </IconButton>
      </div>

      <div className={cn('px-3 py-3', collapsed && 'flex justify-center')}>
        <PlayerLevelBadge compact={collapsed} />
      </div>

      <nav className="flex-1 overflow-y-auto px-2 py-3">
        <ul className="space-y-1">
          <li>{renderLeaf(DASHBOARD)}</li>
        </ul>

        {SECTIONS.map((section) => (
          <div key={section.label} className="mt-4">
            {!collapsed && (
              <div className="px-3 pb-1.5 text-(length:--text-xs) uppercase tracking-wider text-(--color-text-subtle) system:font-mono system:[letter-spacing:var(--label-spacing)]">
                {section.label}
              </div>
            )}
            <ul className="space-y-1">
              {section.items.map((item) => (
                <li key={item.to}>{renderLeaf(item)}</li>
              ))}
            </ul>
          </div>
        ))}
      </nav>

      <div className="px-2 py-3">
        <ul className="space-y-1">
          <li>{renderLeaf(SETTINGS)}</li>
          <li>
            <button
              type="button"
              title={collapsed ? 'Déconnexion' : undefined}
              onClick={handleLogout}
              className={cn(
                'flex w-full items-center gap-3 rounded-(--radius-surface) px-3 py-2 text-(length:--text-sm) text-(--color-text-muted) hover:bg-(--color-surface-muted)',
                collapsed && 'justify-center px-0',
              )}
            >
              <LogoutIcon width={20} height={20} className="shrink-0" />
              {!collapsed && <span>Déconnexion</span>}
            </button>
          </li>
        </ul>
      </div>
    </aside>
  );
}
