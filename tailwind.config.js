import preset from './vendor/filament/support/tailwind.config.preset'
import plugin from "tailwindcss/plugin";

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        "./vendor/robsontenorio/mary/src/View/Components/**/*.php"
    ],

    plugins: [
        require("daisyui"),
        plugin(function ({addVariant}) {
            return addVariant('prima-native', ['&.prima-native', '.prima-native &']);
        }),
    ],

    daisyui: {
        themes: false,
        darkTheme: 'light'
    },

    theme: {
        extend: {
            colors: {
                brand: '#4736dd',
            },
        },
    },
};
