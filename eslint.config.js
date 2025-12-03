import js from "@eslint/js";
import globals from "globals";
import pluginReact from "eslint-plugin-react";
import { defineConfig } from "eslint/config";
import reactPerfPlugin from "eslint-plugin-react-perf";

export default defineConfig([
  { 
    files: ["**/*.{js,mjs,cjs,jsx}"], 
    plugins: { 
      js 
    }, 
    extends: ["js/recommended"], 
    languageOptions: 
    { globals: globals.browser }
  },
  pluginReact.configs.flat.recommended,
  reactPerfPlugin.configs.flat.recommended,
]);
