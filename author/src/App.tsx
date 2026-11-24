import { useEffect, useState } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { api } from './services/api';
import { useEditorStore } from './stores/editorStore';
import LoginPage from './features/auth/LoginPage';
import AuthorShell from './components/AuthorShell';
import PagesConsole from './features/pages-console/PagesConsole';

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

  return (
    <BrowserRouter basename="/author">
      {booting ? (
        <div className="login">
          <div className="login-card">Carregando Author…</div>
        </div>
      ) : !user ? (
        <LoginPage />
      ) : (
        <Routes>
          <Route path="pages" element={<PagesConsole />} />
          <Route path="editor/:pageId" element={<AuthorShell />} />
          <Route path="/" element={<AuthorShell />} />
          <Route path="*" element={<Navigate to="/pages" replace />} />
        </Routes>
      )}
    </BrowserRouter>
  );
}
