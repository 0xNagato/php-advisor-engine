import js from '@eslint/js';
import eslintPluginVue from 'eslint-plugin-vue';
import ts from 'typescript-eslint';
import tailwind from 'eslint-plugin-tailwindcss';
import prettier from 'eslint-config-prettier';
import pluginPrettier from 'eslint-plugin-prettier';

export default ts.config(
  js.configs.recommended,
  ...ts.configs.recommended,
  ...eslintPluginVue.configs['flat/recommended'],
  ...tailwind.configs['flat/recommended'],
  prettier,
  {
    files: ['*.vue', '**/*.vue'],
    languageOptions: {
      parserOptions: {
        parser: '@typescript-eslint/parser',
      },
    },
    rules: {
      'tailwindcss/no-custom-classname': 'off',
      'vue/component-tags-order': [
        'error',
        {
          order: [['script', 'template'], 'style'],
        },
      ],
      'prettier/prettier': 'error',
    },
    plugins: {
      prettier: pluginPrettier,
    },
  },
);
