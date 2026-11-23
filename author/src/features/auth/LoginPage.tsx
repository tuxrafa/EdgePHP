import { useState, type FormEvent } from 'react';
import { api } from '../../services/api';
import { useEditorStore } from '../../stores/editorStore';

export default function LoginPage() {
  const setUser = useEditorStore((s) => s.setUser);
  const [email, setEmail] = useState('author@edgephp.local');
  const [password, setPassword] = useState('author');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const res = await api.login(email, password);
      setUser(res.data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha no login');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="login">
      <form className="login-card" onSubmit={onSubmit} data-testid="login-form">
        <h1>EdgePHP Author</h1>
        <p>Edite páginas JSON e visualize o runtime EDS real no preview.</p>
        <div className="field">
          <label htmlFor="email">Email</label>
          <input
            id="email"
            name="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            autoComplete="username"
          />
        </div>
        <div className="field">
          <label htmlFor="password">Senha</label>
          <input
            id="password"
            name="password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoComplete="current-password"
          />
        </div>
        <button
          className="btn primary"
          type="button"
          data-testid="login-submit"
          disabled={loading}
          onClick={(event) => void onSubmit(event as unknown as FormEvent)}
        >
          {loading ? 'Entrando…' : 'Entrar'}
        </button>
        {error ? <div className="error">{error}</div> : null}
        <p className="muted" style={{ marginTop: 14 }}>
          Demo: author@edgephp.local / author
        </p>
      </form>
    </div>
  );
}
