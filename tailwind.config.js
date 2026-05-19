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
        muted: '#6B6B6B',
        charcoal: '#1A1A1A',
        'copper-lt': '#D4956A'
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
      },
      keyframes: {
        'word-reveal': {
          '0%': { opacity: '0', transform: 'translateY(1.2em) skewY(6deg)' },
          '100%': { opacity: '1', transform: 'translateY(0) skewY(0deg)' }
        },
        'slide-up': {
          '0%': { opacity: '0', transform: 'translateY(40px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
        'fade-in': {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        }
      },
      animation: {
        'word-reveal': 'word-reveal 0.7s cubic-bezier(0.16, 1, 0.3, 1) both',
        'slide-up': 'slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
        'fade-in': 'fade-in 0.5s ease-out both'
      }
    }
  },
  safelist: [
    'reveal-delay-1',
    'reveal-delay-2',
    'reveal-delay-3',
    'reveal-delay-4'
  ],
  plugins: []
}
