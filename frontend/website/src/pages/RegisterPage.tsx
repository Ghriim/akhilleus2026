import { useState } from 'react';
import type { FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { apiRequest, HttpError } from '../api/httpClient';
import { useAuth } from '../auth/AuthContext';
import type { RegisterPlayerResponse } from '../api/types';

interface FieldErrors {
  email?: string[];
  plainPassword?: string[];
  displayName?: string[];
}

export function RegisterPage() {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [displayName, setDisplayName] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [globalError, setGlobalError] = useState<string | null>(null);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setFieldErrors({});
    setGlobalError(null);
    try {
      await apiRequest<RegisterPlayerResponse>('/api/player/registration', {
        method: 'POST',
        body: { email, plainPassword: password, displayName },
      });
      // Auto-login with the same credentials so the user lands on the dashboard.
      await login(email, password);
      navigate('/', { replace: true });
    } catch (err) {
      if (err instanceof HttpError && err.status === 422) {
        setFieldErrors(err.violations() as FieldErrors);
      } else {
        setGlobalError(err instanceof Error ? err.message : 'Unable to register.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="layout-shell">
      <main className="layout-main" style={{ maxWidth: 400 }}>
        <h1>Register</h1>
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
            {fieldErrors.email?.map((msg) => (
              <p key={msg} className="error">
                {msg}
              </p>
            ))}
          </div>
          <div className="field">
            <label htmlFor="displayName">Display name</label>
            <input
              id="displayName"
              type="text"
              autoComplete="nickname"
              value={displayName}
              onChange={(e) => setDisplayName(e.target.value)}
              required
              style={{ width: '100%' }}
            />
            {fieldErrors.displayName?.map((msg) => (
              <p key={msg} className="error">
                {msg}
              </p>
            ))}
          </div>
          <div className="field">
            <label htmlFor="password">Password</label>
            <input
              id="password"
              type="password"
              autoComplete="new-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              style={{ width: '100%' }}
            />
            {fieldErrors.plainPassword?.map((msg) => (
              <p key={msg} className="error">
                {msg}
              </p>
            ))}
            <p className="muted" style={{ fontSize: '0.85em', marginTop: 'var(--space-1)' }}>
              At least 8 characters with uppercase, lowercase, digit, and special character.
            </p>
          </div>
          {globalError && <p className="error" style={{ color: 'var(--color-danger)' }}>{globalError}</p>}
          <button type="submit" className="primary" disabled={submitting}>
            {submitting ? 'Registering…' : 'Register'}
          </button>
        </form>
        <p className="muted" style={{ marginTop: 'var(--space-4)' }}>
          Already have an account? <Link to="/login">Log in</Link>.
        </p>
      </main>
    </div>
  );
}
