/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.php',
    './public/**/*.js',
    './src/**/*.php'
  ],
  theme: {
    extend: {
      colors: {
        primary: '#3B82F6',
        secondary: '#10B981',
        accent: '#F59E0B'
      },
      spacing: {
        128: '32rem'
      }
    }
  },
  plugins: []
}
