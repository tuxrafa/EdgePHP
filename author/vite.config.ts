import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
  plugins: [react()],
  root: path.resolve(__dirname),
  base: '/author/',
  build: {
    outDir: path.resolve(__dirname, '../public/author'),
    emptyOutDir: true,
  },
  server: {
    port: 5173,
    proxy: {
      '/api': 'http://127.0.0.1:8765',
      '/__preview': 'http://127.0.0.1:8765',
      '/blocks': 'http://127.0.0.1:8765',
      '/scripts': 'http://127.0.0.1:8765',
      '/styles': 'http://127.0.0.1:8765',
    },
  },
});
