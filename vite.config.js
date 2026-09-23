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
        app: './assets/app.js',
        login: './assets/login.js',
        'read-ebook': './assets/read-ebook.js',
      },
    },
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
