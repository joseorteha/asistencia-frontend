import { defineConfig } from 'vite'
import { resolve } from 'path'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],

  build: {
    outDir: 'public/assets/bundle',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/js/main.js'),
      },
    },
    manifest: true,
  },

  publicDir: false,

  server: {
    port: 5173,
    origin: 'http://localhost:5173',
    cors: true,
  },
})
