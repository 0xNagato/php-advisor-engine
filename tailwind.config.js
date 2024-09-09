import preset from './vendor/filament/support/tailwind.config.preset';
import plugin from 'tailwindcss/plugin';

export default {
  presets: [preset],
  theme: {
    extend: {
      colors: {
        'input-border': '#A7A4F2',
        'input-ring': '#D1CFF5',
      },
    },
  },
  content: [
    './app/Filament/**/*.php',
    './resources/views/**/*.blade.php',
    './vendor/filament/**/*.blade.php',

    './app/Filament/**/*.php',
    './app/Livewire/**/*.php',
    './app/Traits/**/*.php',
    './resources/views/**/*.blade.php',
    './vendor/filament/**/*.blade.php',
    './resources/js/**/*.{vue,js,ts,jsx,tsx}',
  ],

  plugins: [
    plugin(function ({ addVariant }) {
      return addVariant('prima-native', ['&.prima-native', '.prima-native &']);
    }),
  ],
  theme: {
    extend: {
      colors: {
        brand: '#4736dd',
      },
    },
  },
};
