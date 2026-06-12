import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import * as authApi from '@/api/endpoints/auth';
import { useAuth } from '@/auth/useAuth';

export function RegisterPage() {
  const [email, setEmail] = useState('');
  const [displayName, setDisplayName] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const auth = useAuth();
  const navigate = useNavigate();

  return (
    <div className="min-h-full flex items-center justify-center px-4 py-10">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <h1 className="text-(length:--text-xl) font-semibold">Inscription</h1>
        </CardHeader>
        <CardBody>
          <form
            className="space-y-4"
            onSubmit={async (e) => {
              e.preventDefault();
              setError(null);
              setLoading(true);
              try {
                await authApi.register({ email, displayName, plainPassword: password });
                await auth.login(email, password);
                navigate('/', { replace: true });
              } catch (err) {
                setError((err as Error).message);
              } finally {
                setLoading(false);
              }
            }}
          >
            {error && <Alert tone="danger">{error}</Alert>}
            <div>
              <Label htmlFor="displayName">Nom affiché</Label>
              <Input
                id="displayName"
                value={displayName}
                onChange={(e) => setDisplayName(e.target.value)}
                required
              />
            </div>
            <div>
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>
            <div>
              <Label htmlFor="password">Mot de passe</Label>
              <Input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </div>
            <Button type="submit" isLoading={loading} className="w-full">
              Créer le compte
            </Button>
            <p className="text-(length:--text-sm) text-(--color-text-muted) text-center">
              Déjà inscrit ?{' '}
              <Link to="/login" className="text-(--color-primary) hover:underline">
                Se connecter
              </Link>
            </p>
          </form>
        </CardBody>
      </Card>
    </div>
  );
}
