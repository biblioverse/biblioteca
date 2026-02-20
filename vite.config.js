import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import symfony from 'vite-plugin-symfony';

const isTraefik = process.env.VITE_TRAEFIK === 'true';

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
    origin: isTraefik ? 'https://biblioteca-vite.docker.test' : 'http://localhost:5173',
    allowedHosts: String(process.env.ALLOWED_HOSTS).split(",") || ['localhost'],
    cors: isTraefik ? {
      origin: ['https://biblioteca.docker.test', 'https://biblioteca-vite.docker.test'],
      credentials: true
    } : true
  },
});
