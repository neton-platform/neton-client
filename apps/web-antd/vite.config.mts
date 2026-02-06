import { defineConfig } from '@vben/vite-config';

export default defineConfig(async () => {
  return {
    application: {},
    vite: {
      server: {
        allowedHosts: true,
        proxy: {
          '/app-api': {
            changeOrigin: true,
            target: 'http://localhost:8088',
            ws: true,
          },
        },
      },
    },
  };
});
