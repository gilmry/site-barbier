import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import { resolve } from 'node:path';

// Îlots compilés en modules ES statiques dans www/islands/ (servis tels quels par l'hébergement, sans Node).
// Noms stables (admin.js, booking.js, …) : les gabarits PHP les référencent ; le gate `front` vérifie que
// le JS committé correspond aux sources.
export default defineConfig({
  plugins: [svelte()],
  build: {
    outDir: process.env.ISLANDS_OUT || resolve(import.meta.dirname, '../www/islands'),
    emptyOutDir: true,
    target: 'es2020',
    sourcemap: false,
    rollupOptions: {
      input: {
        admin: resolve(import.meta.dirname, 'src/admin/main.js'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: '[name][extname]',
      },
    },
  },
});
