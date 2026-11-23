import { useEffect, useState } from 'react';
import { api } from './services/api';
import { useEditorStore } from './stores/editorStore';
import LoginPage from './features/auth/LoginPage';
import AuthorShell from './components/AuthorShell';

export default function App() {
  const user = useEditorStore((s) => s.user);
  const setUser = useEditorStore((s) => s.setUser);
  const [booting, setBooting] = useState(true);

  useEffect(() => {
    api
      .me()
      .then((res) => setUser(res.data))
      .catch(() => setUser(null))
      .finally(() => setBooting(false));
  }, [setUser]);

  if (booting) {
    return (
      <div className="login">
        <div className="login-card">Carregando Author…</div>
      </div>
    );
  }

  if (!user) {
    return <LoginPage />;
  }

  return <AuthorShell />;
}
