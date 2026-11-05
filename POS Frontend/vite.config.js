import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), 'POS_');
  const apiTarget = env.POS_API_TARGET || 'http://127.0.0.1:8000';
  return {
    plugins: [react(), tailwindcss()],
    server: {
      port: 5173,
      strictPort: true,
      proxy: Object.fromEntries(['/api', '/sanctum'].map(path => [path, {
        target: apiTarget, changeOrigin: false,
      }])),
    },
    test: { environment: 'jsdom', setupFiles: './src/test/setup.js', restoreMocks: true, maxWorkers: 1 },
  };
});
