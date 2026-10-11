import { defineConfig } from 'vite';

export default defineConfig({
  root: 'owner',
  base: '/',
  publicDir: 'public',
  server: {
    host: '127.0.0.1',
    port: 5174,
    strictPort: true,
    proxy: {
      '/api': {
        target: process.env.VITE_API_TARGET || 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: '../dist-owner',
    emptyOutDir: true,
  },
});
