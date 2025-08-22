# Gym Machines Website - Styling Guide

## Overview

This document outlines the comprehensive styling system implemented for the Gym Machines website, including responsive design, component classes, and interactive features.

## Technology Stack

- **CSS Framework**: Tailwind CSS 3.x
- **JavaScript**: Alpine.js + Custom ES6 modules
- **Build Tool**: Vite
- **Icons**: Heroicons (SVG)

## Component Classes

### Buttons

```css
.btn-primary     /* Primary blue button */
.btn-secondary   /* Outlined button */
.btn-danger      /* Red danger button */
.btn-mobile      /* Mobile-optimized button */
```

### Cards

```css
.card           /* Basic card styling */
.card-hover     /* Card with hover effects */
.admin-card     /* Admin panel card styling */
```

### Forms

```css
.form-input     /* Standard input field */
.form-textarea  /* Textarea styling */
.form-select    /* Select dropdown styling */
```

### Navigation

```css
.nav-link           /* Navigation link base */
.nav-link-active    /* Active navigation state */
.nav-link-inactive  /* Inactive navigation state */
```

### Status Badges

```css
.badge          /* Base badge styling */
.badge-blue     /* Blue status badge */
.badge-green    /* Green status badge */
.badge-red      /* Red status badge */
.badge-yellow   /* Yellow status badge */
```

## Image Gallery System

### Features

- **Zoom Functionality**: Click any image with `data-zoom` attribute
- **Gallery Navigation**: Arrow keys and navigation buttons
- **Responsive Design**: Adapts to all screen sizes
- **Loading States**: Smooth loading animations
- **Keyboard Support**: ESC to close, arrows to navigate

### Usage

```html
<!-- Single image with zoom -->
<img src="image.jpg" data-zoom data-title="Image Title" alt="Description">

<!-- Image gallery -->
<div data-gallery="gallery-name">
    <img src="image1.jpg" data-zoom data-title="Image 1" alt="Description">
    <img src="image2.jpg" data-zoom data-title="Image 2" alt="Description">
</div>
```

## Responsive Design

### Breakpoints

- **Mobile**: < 640px
- **Tablet**: 640px - 768px
- **Desktop**: 768px - 1024px
- **Large Desktop**: > 1024px

### Mobile Optimizations

- Touch-friendly button sizes (minimum 44px)
- Optimized navigation for mobile devices
- Responsive image galleries
- Mobile-first form layouts

### Touch Device Support

- Removes hover effects on touch devices
- Larger touch targets for better usability
- Optimized button spacing

## Animation System

### Available Animations

```css
.animate-fade-in-up      /* Fade in from bottom */
.animate-slide-in-right  /* Slide in from right */
.animate-pulse-slow      /* Slow pulsing animation */
```

### Accessibility

- Respects `prefers-reduced-motion` setting
- Provides alternative static states
- Focus indicators for keyboard navigation

## JavaScript Features

### Image Gallery

- Automatic modal creation
- Keyboard navigation support
- Touch/swipe support (mobile)
- Loading states and error handling

### Form Enhancements

- Real-time validation feedback
- Loading states on form submission
- Auto-disable submit buttons during processing

### Notification System

```javascript
// Show notifications
window.notifications.show('Message', 'success', 5000);
window.notifications.show('Error message', 'error');
window.notifications.show('Warning', 'warning');
window.notifications.show('Info', 'info');
```

### Utility Functions

```javascript
// Copy to clipboard
window.utils.copyToClipboard('Text to copy');

// Format currency
window.utils.formatCurrency(1234.56); // $1,234.56

// Debounce function calls
const debouncedFunction = window.utils.debounce(myFunction, 300);

// Throttle function calls
const throttledFunction = window.utils.throttle(myFunction, 100);
```

## Performance Optimizations

### CSS Optimizations

- Critical CSS separation
- Utility-first approach with Tailwind
- Minimal custom CSS
- Optimized for production builds

### JavaScript Optimizations

- Lazy loading for images
- Debounced scroll and resize events
- Efficient DOM manipulation
- Minimal external dependencies

### Image Optimizations

- Responsive image loading
- Fallback images for errors
- Optimized aspect ratios
- Lazy loading implementation

## Accessibility Features

### Keyboard Navigation

- Tab order optimization
- Focus indicators
- Keyboard shortcuts for image gallery
- Skip links for main content

### Screen Reader Support

- Proper ARIA labels
- Semantic HTML structure
- Alt text for all images
- Descriptive link text

### Color and Contrast

- WCAG AA compliant color ratios
- High contrast mode support
- Color-blind friendly palette

## Browser Support

### Modern Browsers

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Fallbacks

- Graceful degradation for older browsers
- Progressive enhancement approach
- CSS Grid with Flexbox fallbacks

## Development Guidelines

### CSS Best Practices

1. Use Tailwind utility classes first
2. Create component classes for repeated patterns
3. Follow mobile-first responsive design
4. Maintain consistent spacing scale

### JavaScript Best Practices

1. Use ES6+ features with appropriate fallbacks
2. Implement proper error handling
3. Follow accessibility guidelines
4. Optimize for performance

### Testing Checklist

- [ ] Test on mobile devices
- [ ] Verify keyboard navigation
- [ ] Check screen reader compatibility
- [ ] Validate responsive breakpoints
- [ ] Test image gallery functionality
- [ ] Verify form validation
- [ ] Check loading states
- [ ] Test notification system

## Customization

### Color Scheme

The website uses a blue-based color scheme that can be customized in `tailwind.config.js`:

```javascript
theme: {
    extend: {
        colors: {
            primary: {
                50: '#eff6ff',
                500: '#3b82f6',
                600: '#2563eb',
                700: '#1d4ed8',
            }
        }
    }
}
```

### Typography

Font family can be customized in the Tailwind configuration:

```javascript
fontFamily: {
    sans: ['Figtree', ...defaultTheme.fontFamily.sans],
}
```

## Maintenance

### Regular Tasks

1. Update dependencies monthly
2. Optimize images regularly
3. Monitor performance metrics
4. Test accessibility compliance
5. Review and update documentation

### Performance Monitoring

- Monitor Core Web Vitals
- Check image optimization
- Review JavaScript bundle size
- Analyze CSS usage

## Troubleshooting

### Common Issues

1. **Images not loading**: Check file paths and permissions
2. **Gallery not working**: Verify JavaScript is loaded
3. **Styles not applying**: Check Tailwind purge settings
4. **Mobile layout issues**: Review responsive classes

### Debug Tools

- Browser DevTools for responsive testing
- Lighthouse for performance auditing
- WAVE for accessibility testing
- Can I Use for browser compatibility