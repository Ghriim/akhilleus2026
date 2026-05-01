import { useState } from 'react';
import type { FormEvent } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { HttpError } from '../api/httpClient';

interface LocationState {
  from?: string;
}

export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const fromPath = (location.state as LocationState | null)?.from ?? '/';

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [globalError, setGlobalError] = useState<string | null>(null);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setGlobalError(null);
    try {
      await login(email, password);
      navigate(fromPath, { replace: true });
    } catch (err) {
      if (err instanceof HttpError && err.status === 401) {
        setGlobalError('Invalid email or password.');
      } else {
        setGlobalError(err instanceof Error ? err.message : 'Unable to log in.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="layout-shell">
      <main className="layout-main" style={{ maxWidth: 400 }}>
        <h1>Log in</h1>
        <form onSubmit={handleSubmit} className="card">
          <div className="field">
            <label htmlFor="email">Email</label>
            <input
              id="email"
              type="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              style={{ width: '100%' }}
            />
          </div>
          <div className="field">
            <label htmlFor="password">Password</label>
            <input
              id="password"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              style={{ width: '100%' }}
            />
          </div>
          {globalError && <p className="error" style={{ color: 'var(--color-danger)' }}>{globalError}</p>}
          <button type="submit" className="primary" disabled={submitting}>
            {submitting ? 'Logging in…' : 'Log in'}
          </button>
        </form>
        <p className="muted" style={{ marginTop: 'var(--space-4)' }}>
          No account yet? <Link to="/register">Register</Link>.
        </p>
      </main>
    </div>
  );
}
