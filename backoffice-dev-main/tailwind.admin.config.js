/** @type {import('tailwindcss').Config} */
module.exports = {
  prefix: 'tw-',
  corePlugins: {
    preflight: false,
  },
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      screens: {
        // Override with bootstrap breakpoints for compatibility

        'sm': '576px',
        // => @media (min-width: 576px) { ... }

        'md': '768px',
        // => @media (min-width: 768px) { ... }

        'lg': '992px',
        // => @media (min-width: 992px) { ... }

        'xl': '1200px',
        // => @media (min-width: 1200px) { ... }

        '2xl': '1400px',
        // => @media (min-width: 1400px) { ... }
      },
    },
  },
  plugins: [],
}
