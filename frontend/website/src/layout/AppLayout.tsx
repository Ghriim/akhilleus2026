import { useEffect, useRef, useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { apiRequest } from '../api/httpClient';
import { BellIcon, GearIcon, LogoutIcon } from '../components/icons';

const TRAINING_PATHS = ['/planning', '/history', '/achievements'];

export function AppLayout() {
  const { token, logout } = useAuth();
  const location = useLocation();
  const [trainingOpen, setTrainingOpen] = useState(false);
  const trainingRef = useRef<HTMLDivElement>(null);

  const handleLogout = async () => {
    try {
      await apiRequest('/api/security/logout', { method: 'POST', token });
    } catch {
      // logout is best-effort; clear locally regardless
    }
    logout();
  };

  // Close the dropdown on route change (a NavLink click is a route change).
  useEffect(() => {
    setTrainingOpen(false);
  }, [location.pathname]);

  // Close on outside click + Escape.
  useEffect(() => {
    if (!trainingOpen) return;
    const onClick = (event: MouseEvent) => {
      if (!trainingRef.current?.contains(event.target as Node)) {
        setTrainingOpen(false);
      }
    };
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setTrainingOpen(false);
    };
    document.addEventListener('mousedown', onClick);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('mousedown', onClick);
      document.removeEventListener('keydown', onKey);
    };
  }, [trainingOpen]);

  const trainingActive = TRAINING_PATHS.some((p) => location.pathname.startsWith(p));

  return (
    <div className="layout-shell">
      <header className="layout-header">
        <strong>Akhilleus</strong>
        <nav>
          <NavLink to="/" end>
            Dashboard
          </NavLink>
          <div className="nav-dropdown" ref={trainingRef}>
            <button
              type="button"
              className={`nav-dropdown-trigger${trainingActive ? ' active' : ''}`}
              aria-haspopup="menu"
              aria-expanded={trainingOpen}
              onClick={() => setTrainingOpen((v) => !v)}
            >
              <span className="nav-dropdown-trigger__label">Training</span>
              <span aria-hidden="true">▾</span>
            </button>
            {trainingOpen && (
              <div className="nav-dropdown-menu" role="menu">
                <NavLink to="/planning" role="menuitem">
                  Planning
                </NavLink>
                <NavLink to="/history" role="menuitem">
                  History
                </NavLink>
                <NavLink to="/achievements" role="menuitem">
                  Achievements
                </NavLink>
              </div>
            )}
          </div>
        </nav>
        <div className="layout-header__actions">
          <button
            type="button"
            className="icon-button"
            aria-label="Notifications"
            title="Notifications"
            disabled
          >
            <BellIcon />
          </button>
          <button
            type="button"
            className="icon-button"
            aria-label="Settings"
            title="Settings"
            disabled
          >
            <GearIcon />
          </button>
          <button
            type="button"
            className="icon-button"
            aria-label="Logout"
            title="Logout"
            onClick={handleLogout}
          >
            <LogoutIcon />
          </button>
        </div>
      </header>
      <main className="layout-main">
        <Outlet />
      </main>
    </div>
  );
}
