import { defineConfig } from "vite";
import path from "path";

export default defineConfig({
  root: ".",
  build: {
    outDir: "assets/dist",
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, "assets/src/main.js"),
      }
    },
  },
});
