/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.phtml',
    './public/**/*.js',
    './src/**/*.php'
  ],
  theme: {
    extend: {
      colors: {
        primary: '#B87333',
        ink: '#0D0D0D',
        paper: '#FAFAFA',
        void: '#0D0D0D',
        muted: '#6B6B6B'
      },
      fontFamily: {
        serif: ['Playfair Display', 'serif'],
        mono: ['Space Mono', 'monospace']
      },
      borderRadius: {
        DEFAULT: '0px',
        none: '0px'
      },
      spacing: {
        128: '32rem'
      }
    }
  },
  plugins: []
}
