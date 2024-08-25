import js from '@eslint/js';
import eslintPluginVue from 'eslint-plugin-vue';
import ts from 'typescript-eslint';
import tailwind from 'eslint-plugin-tailwindcss';

export default ts.config(
    js.configs.recommended,
    ...ts.configs.recommended,
    ...eslintPluginVue.configs['flat/recommended'],
    ...tailwind.configs['flat/recommended'],
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
        },
    },
);
