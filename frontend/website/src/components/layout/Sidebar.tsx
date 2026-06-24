import { useEffect, useState } from 'react';
import type { ComponentType, SVGProps } from 'react';
import { NavLink, useLocation, useNavigate } from 'react-router-dom';
import {
  ActivityIcon,
  BookIcon,
  CalendarIcon,
  ChartIcon,
  ChevronDownIcon,
  ChevronsLeftIcon,
  DumbbellIcon,
  FlagIcon,
  GearIcon,
  HomeIcon,
  LogoutIcon,
  StarIcon,
  TrophyIcon,
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

interface GroupItem {
  key: string;
  label: string;
  Icon: IconType;
  children: LeafItem[];
}

const DASHBOARD: LeafItem = { to: '/', label: 'Tableau de bord', end: true, Icon: HomeIcon };

const GROUPS: GroupItem[] = [
  {
    key: 'training',
    label: 'Entraînements',
    Icon: ActivityIcon,
    children: [
      { to: '/planning', label: 'Planning', Icon: CalendarIcon },
      { to: '/movements', label: 'Mouvements', Icon: DumbbellIcon },
      { to: '/achievements', label: 'Records', Icon: TrophyIcon },
      { to: '/statistics', label: 'Statistiques', Icon: ChartIcon },
    ],
  },
  {
    key: 'journal',
    label: 'Journal',
    Icon: BookIcon,
    children: [
      { to: '/leveling/journal', label: 'XP', Icon: StarIcon },
      { to: '/quests/unique', label: 'Quêtes', Icon: FlagIcon },
    ],
  },
];

const SETTINGS: LeafItem = { to: '/settings', label: 'Réglages', Icon: GearIcon };

function isPathActive(pathname: string, to: string, end?: boolean): boolean {
  if (end) return pathname === to;
  return pathname === to || pathname.startsWith(`${to}/`);
}

function activeGroupKey(pathname: string): string | null {
  for (const group of GROUPS) {
    if (group.children.some((c) => isPathActive(pathname, c.to, c.end))) return group.key;
  }
  return null;
}

export function Sidebar() {
  const { logout, isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const { pathname } = useLocation();

  const [collapsed, setCollapsed] = useState<boolean>(() => {
    try {
      return '1' === localStorage.getItem(STORAGE_KEY);
    } catch {
      return false;
    }
  });
  const [open, setOpen] = useState<Record<string, boolean>>(() => {
    const key = activeGroupKey(window.location.pathname);
    return key ? { [key]: true } : {};
  });

  // Keep the group that owns the active route expanded as the user navigates.
  useEffect(() => {
    const key = activeGroupKey(pathname);
    if (key) setOpen((prev) => (prev[key] ? prev : { ...prev, [key]: true }));
  }, [pathname]);

  if (!isAuthenticated) return null;

  const setCollapsedPersisted = (next: boolean) => {
    setCollapsed(next);
    try {
      localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
    } catch {
      /* ignore */
    }
  };

  const onGroupClick = (key: string) => {
    if (collapsed) {
      // Collapsed rail: clicking a parent expands the sidebar and opens its group.
      setCollapsedPersisted(false);
      setOpen((prev) => ({ ...prev, [key]: true }));
      return;
    }
    setOpen((prev) => ({ ...prev, [key]: !prev[key] }));
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  const leafClass = (isActive: boolean, indented: boolean) =>
    cn(
      'flex items-center gap-3 rounded-(--radius-md) px-3 py-2 text-(length:--text-sm)',
      collapsed && 'justify-center px-0',
      !collapsed && indented && 'pl-10',
      isActive
        ? 'bg-(--color-primary-soft) font-semibold text-(--color-primary)'
        : 'text-(--color-text-muted) hover:bg-(--color-surface-muted)',
    );

  const renderLeaf = (item: LeafItem, indented: boolean) => (
    <NavLink
      to={item.to}
      end={item.end ?? false}
      title={collapsed ? item.label : undefined}
      className={({ isActive }) => leafClass(isActive, indented)}
    >
      <item.Icon width={20} height={20} className="shrink-0" />
      {!collapsed && <span className="truncate">{item.label}</span>}
    </NavLink>
  );

  return (
    <aside
      className={cn(
        'sticky top-0 z-10 flex h-dvh shrink-0 flex-col border-r border-(--color-border) bg-(--color-surface) transition-[width] duration-200',
        collapsed ? 'w-16' : 'w-60',
      )}
    >
      <div className="flex items-center gap-2 border-b border-(--color-border) px-3 py-3">
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

      <div className={cn('border-b border-(--color-border) px-3 py-3', collapsed && 'flex justify-center')}>
        <PlayerLevelBadge compact={collapsed} />
      </div>

      <nav className="flex-1 overflow-y-auto px-2 py-3">
        <ul className="space-y-1">
          <li>{renderLeaf(DASHBOARD, false)}</li>

          {GROUPS.map((group) => {
            const groupActive = activeGroupKey(pathname) === group.key;
            const isOpen = !collapsed && open[group.key];
            return (
              <li key={group.key}>
                <button
                  type="button"
                  title={collapsed ? group.label : undefined}
                  aria-expanded={isOpen}
                  onClick={() => onGroupClick(group.key)}
                  className={cn(
                    'flex w-full items-center gap-3 rounded-(--radius-md) px-3 py-2 text-(length:--text-sm)',
                    collapsed && 'justify-center px-0',
                    groupActive
                      ? 'font-semibold text-(--color-text)'
                      : 'text-(--color-text-muted) hover:bg-(--color-surface-muted)',
                  )}
                >
                  <group.Icon width={20} height={20} className="shrink-0" />
                  {!collapsed && (
                    <>
                      <span className="flex-1 truncate text-left">{group.label}</span>
                      <ChevronDownIcon
                        width={16}
                        height={16}
                        className={cn('shrink-0 transition-transform', isOpen && 'rotate-180')}
                      />
                    </>
                  )}
                </button>
                {isOpen && (
                  <ul className="mt-1 space-y-1">
                    {group.children.map((child) => (
                      <li key={child.to}>{renderLeaf(child, true)}</li>
                    ))}
                  </ul>
                )}
              </li>
            );
          })}
        </ul>
      </nav>

      <div className="border-t border-(--color-border) px-2 py-3">
        <ul className="space-y-1">
          <li>{renderLeaf(SETTINGS, false)}</li>
          <li>
            <button
              type="button"
              title={collapsed ? 'Déconnexion' : undefined}
              onClick={handleLogout}
              className={cn(
                'flex w-full items-center gap-3 rounded-(--radius-md) px-3 py-2 text-(length:--text-sm) text-(--color-text-muted) hover:bg-(--color-surface-muted)',
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
