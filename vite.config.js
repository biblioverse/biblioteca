import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import symfony from 'vite-plugin-symfony';
export default defineConfig({
  plugins: [
    symfony({
      stimulus: './assets/controllers.json',
    }),
    vue(),
  ],
  build: {
    outDir: 'public/build',
    assetsDir: '',
    manifest: true,
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: './assets/app.js',
        login: './assets/login.js',
        'read-ebook': './assets/read-ebook.js',
      },
      output: {
        entryFileNames: '[name].[hash].js',
        chunkFileNames: '[name].[hash].js',
        assetFileNames: '[name].[hash][extname]',
      },
    },
  },
  server: {
    middlewareMode: false,
    host: true,
    port: 5173,
    origin: process.env.VITE_ORIGIN || 'http://localhost:5173',
    allowedHosts: String(process.env.ALLOWED_HOSTS).split(",") || ['localhost'],
    cors: {
      origin: String(process.env.VITE_CORS).split(","),
      credentials: true
    }
  },
});
