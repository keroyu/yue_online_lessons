/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          cream: '#F6F1E9',
          orange: '#FAA45E',
          red: '#FF4438',
          navy: '#373557',
          teal: '#3F83A3',
        },
      },
    },
  },
  plugins: [],
}
