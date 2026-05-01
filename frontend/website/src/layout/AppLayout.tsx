import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { apiRequest } from '../api/httpClient';

export function AppLayout() {
  const { token, logout } = useAuth();

  const handleLogout = async () => {
    try {
      await apiRequest('/api/security/logout', { method: 'POST', token });
    } catch {
      // logout is best-effort; clear locally regardless
    }
    logout();
  };

  return (
    <div className="layout-shell">
      <header className="layout-header">
        <strong>Akhilleus</strong>
        <nav>
          <NavLink to="/" end>
            Dashboard
          </NavLink>
          <NavLink to="/upcoming">Upcoming</NavLink>
          <NavLink to="/history">History</NavLink>
          <NavLink to="/achievements">Achievements</NavLink>
        </nav>
        <button type="button" onClick={handleLogout}>
          Logout
        </button>
      </header>
      <main className="layout-main">
        <Outlet />
      </main>
    </div>
  );
}
