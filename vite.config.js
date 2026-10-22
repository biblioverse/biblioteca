import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import symfony from '@symfony/reprise/vite';

export default defineConfig({
  plugins: [
    symfony({
      outputPath: 'public/build',
      publicPath: '/build',
      stimulus: 'assets/controllers.json',
    }),
    vue(),
  ],
  build: {
    rollupOptions: {
      input: {
        styles: './assets/styles/global.scss',
        app: './assets/app.js',
        login: './assets/login.js',
        'read-ebook': './assets/read-ebook.js',
      },
    },
  },
  optimizeDeps: {
    // The Reprise plugin resolves this virtual module; esbuild's dep pre-bundler
    // cannot, so keep @symfony/reprise out of dependency optimization.
    exclude: ['@symfony/reprise'],
  },
  server: {
    host: true,
    port: 5173,
    origin: process.env.VITE_ORIGIN || 'http://localhost:5173',
    allowedHosts: String(process.env.ALLOWED_HOSTS || 'localhost').split(','),
    cors: {
      origin: String(process.env.VITE_CORS || 'http://localhost:5173').split(','),
      credentials: true,
    },
  },
});
