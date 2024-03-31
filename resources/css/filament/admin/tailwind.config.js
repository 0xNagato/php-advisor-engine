import preset from '../../../../vendor/filament/filament/tailwind.config.preset'
import plugin from "tailwindcss/plugin";

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/forms/**/*.blade.php',
        './resources/views/livewire/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    plugins: [
        require("daisyui"),
        plugin(function ({addVariant}) {
            return addVariant('prima-native', ['&.prima-native', '.prima-native &']);
        }),
    ]
}
