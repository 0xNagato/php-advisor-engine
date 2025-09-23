# Venue Logos Scroll Web Component

A responsive, customizable web component for displaying venue logos in an animated, infinite scroll. Perfect for showcasing partner venues, sponsors, or featured locations on any website.

## 🚀 Features

- **Responsive Design**: Automatically adapts to mobile, tablet, and desktop screens
- **Smooth Animations**: Infinite scrolling with customizable speed and direction
- **Accessibility**: Proper ARIA labels, keyboard navigation support
- **Theme Support**: Built-in dark mode and customizable CSS properties
- **Performance**: Optimized animations using `requestAnimationFrame`
- **Browser Compatible**: Works in all modern browsers with no dependencies
- **Customizable**: Extensive configuration options for appearance and behavior

## 📦 Installation

### Option 1: Direct File Include

1. Download the `venue-logos-scroll.html` file to your project
2. Include it in your HTML document before using the component:

```html
<script src="path/to/venue-logos-scroll.html" type="module"></script>
```

### Option 2: CDN (Coming Soon)

```html
<script src="https://cdn.example.com/venue-logos-scroll/venue-logos-scroll.html" type="module"></script>
```

### Option 3: NPM Package (Coming Soon)

```bash
npm install venue-logos-scroll
```

## 🛠️ Basic Usage

### Dynamic API Example (Recommended)

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Website</title>
    <!-- Load the web component from your public directory -->
    <script src="/venue-logos-scroll/venue-logos-scroll.html" type="module"></script>
</head>
<body>
    <venue-logos-scroll
        api-endpoint="/api/venue-logos"
        refresh-interval="300"
        cache-time="600000"
        loading-text="Loading venues from PRIMA..."
        pause-on-hover>
    </venue-logos-scroll>
</body>
</html>
```

### Static Data Example

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Website</title>
    <script src="venue-logos-scroll.html" type="module"></script>
</head>
<body>
    <venue-logos-scroll
        first-row='[{"id":1,"name":"Restaurant A","logo_path":"logo1.jpg"},{"id":2,"name":"Bar B","logo_path":"logo2.jpg"}]'
        second-row='[{"id":3,"name":"Club C","logo_path":"logo3.jpg"},{"id":4,"name":"Lounge D","logo_path":"logo4.jpg"}]'>
    </venue-logos-scroll>
</body>
</html>
```

### With All Options

```html
<venue-logos-scroll
    api-endpoint="https://prima.test/api/venue-logos"
    refresh-interval="300"
    cache-time="600000"
    loading-text="Loading venues..."
    duration="30"
    direction="opposite"
    speed="normal"
    pause-on-hover
    show-placeholders>
</venue-logos-scroll>
```

## 🌐 Deployment & CORS

### Important Notes

**Web components must be served via HTTP/HTTPS** to avoid CORS restrictions. When testing locally:

1. **Use a local development server** (not `file://` protocol)
2. **Serve from your public directory** (like `/venue-logos-scroll/`)
3. **API endpoints should be relative** when on the same domain

### Local Development

```bash
# Using Laravel's built-in server
php artisan serve

# Or using a simple HTTP server
cd public && python3 -m http.server 8000
```

### Production Deployment

1. **Copy the component files** to your web server's public directory
2. **Update the script src** to match your public URL structure
3. **Ensure API endpoints are accessible** from your domain

## 🔌 Laravel API Integration

The web component is designed to work seamlessly with your PRIMA Laravel application. Here's how to set it up:

### 1. Laravel API Endpoint

The component expects data in this format:

```json
{
    "first_row": [
        {
            "id": 1,
            "name": "Venue Name",
            "logo_path": "https://prima.test/storage/venue-logos/venue-1.png"
        }
    ],
    "second_row": [
        {
            "id": 2,
            "name": "Another Venue",
            "logo_path": "https://prima.test/storage/venue-logos/venue-2.png"
        }
    ],
    "total_venues": 12,
    "generated_at": "2024-12-23T10:30:00Z"
}
```

### 2. API Endpoint Features

Your Laravel API endpoint supports these query parameters:

| Parameter | Description | Example |
|-----------|-------------|---------|
| `limit` | Number of venues to return | `?limit=8` |
| `tier` | Filter by venue tier | `?tier=premium` |
| `location` | Filter by city/location | `?location=New York` |
| `shuffle` | Randomize venue order | `?shuffle=true` |
| `fresh` | Bypass cache | `?fresh=true` |

### 3. Example Implementation

```html
<!-- On your website -->
<venue-logos-scroll
    api-endpoint="/api/venue-logos?limit=10&tier=premium"
    refresh-interval="300"
    cache-time="600000"
    loading-text="Loading PRIMA venues..."
    pause-on-hover>
</venue-logos-scroll>
```

### 4. Cache Management

The Laravel endpoint caches data for 5 minutes by default. Clear the cache with:

```bash
curl -X POST https://prima.test/api/venue-logos/clear-cache
```

Or in your Laravel application:

```php
Cache::forget('venue-logos-data');
```

## 📋 Data Format

The component expects venue data in the following JSON format:

```javascript
{
    "id": 1,                    // Unique identifier (number)
    "name": "Venue Name",       // Venue name (string) - required for placeholders
    "logo_path": "https://..."  // URL to logo image (string) - optional
}
```

### Example Data Arrays

```javascript
// First row venues
const firstRow = [
    {
        "id": 1,
        "name": "The Rooftop",
        "logo_path": "https://example.com/rooftop-logo.png"
    },
    {
        "id": 2,
        "name": "Sky Lounge",
        "logo_path": "https://example.com/sky-logo.jpg"
    }
];

// Second row venues
const secondRow = [
    {
        "id": 3,
        "name": "Garden Bar",
        "logo_path": "https://example.com/garden-logo.svg"
    },
    {
        "id": 4,
        "name": "Jazz Club",
        "logo_path": "" // Will show placeholder
    }
];
```

## ⚙️ Configuration Options

### Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `api-endpoint` | string | `null` | API endpoint URL to fetch venue data from |
| `refresh-interval` | number | `0` | Auto-refresh interval in seconds (0 = no refresh) |
| `cache-time` | number | `300000` | Cache duration in milliseconds (5 minutes default) |
| `loading-text` | string | `"Loading venues..."` | Text to show while loading data |
| `first-row` | JSON string | `[]` | Array of venue objects for the top row (static data) |
| `second-row` | JSON string | `[]` | Array of venue objects for the bottom row (static data) |
| `duration` | number | `35` | Animation duration in seconds |
| `direction` | string | `"opposite"` | Scroll direction: `"opposite"`, `"same"`, `"none"` |
| `speed` | string | `"normal"` | Animation speed: `"slow"`, `"normal"`, `"fast"` |
| `pause-on-hover` | boolean | `false` | Pause animation when hovering over the component |
| `show-placeholders` | boolean | `true` | Show venue names when logos are not available |

### CSS Classes

| Class | Description |
|-------|-------------|
| `.dark` | Enables dark theme styling |
| `.fast` | Uses faster animation speed |
| `.slow` | Uses slower animation speed |

## 🎨 Styling & Theming

### CSS Custom Properties

You can customize the appearance using CSS custom properties:

```css
venue-logos-scroll {
    --background-color: #f8fafc;
    --placeholder-bg: #e2e8f0;
    --placeholder-text: #475569;
    --animation-duration: 40s;
    --animation-easing: linear;
}
```

### Available Properties

- `--background-color`: Background color of the component
- `--placeholder-bg`: Background color for venue name placeholders
- `--placeholder-text`: Text color for venue name placeholders
- `--animation-duration`: Animation duration in seconds
- `--animation-easing`: Animation timing function (e.g., `linear`, `ease-in-out`)

### Dark Theme Example

```css
.dark-theme venue-logos-scroll {
    --background-color: #1e293b;
    --placeholder-bg: #334155;
    --placeholder-text: #cbd5e1;
}
```

```html
<venue-logos-scroll class="dark-theme" ...></venue-logos-scroll>
```

## 📱 Responsive Behavior

The component automatically adapts to different screen sizes:

### Mobile (< 640px)
- Logo width: 96px (6rem)
- Logo height: 60px (3.75rem)
- Smaller margins for better fit

### Tablet & Desktop (≥ 640px)
- Logo width: 128px (8rem)
- Logo height: 80px (5rem)
- Larger margins for better spacing

### Responsive Features

- Automatically adjusts logo dimensions on window resize
- Maintains aspect ratios across all screen sizes
- Preserves animation smoothness on all devices
- Touch-friendly hover states on mobile devices

## 🔧 Advanced Usage

### Dynamic Data Updates

You can update the venue data dynamically using JavaScript:

```javascript
const component = document.querySelector('venue-logos-scroll');

// Update first row
component.setAttribute('first-row', JSON.stringify(newFirstRowData));

// Update second row
component.setAttribute('second-row', JSON.stringify(newSecondRowData));
```

### Event Listeners

Listen for component events:

```javascript
const component = document.querySelector('venue-logos-scroll');

component.addEventListener('load', () => {
    console.log('Component loaded successfully');
});

component.addEventListener('animationstart', () => {
    console.log('Animation started');
});

component.addEventListener('animationpause', () => {
    console.log('Animation paused');
});
```

### Programmatic Control

Control the animation programmatically:

```javascript
const component = document.querySelector('venue-logos-scroll');

// Pause animation
component.pauseAnimation();

// Resume animation
component.resumeAnimation();

// Restart animation
component.restartAnimation();

// Stop animation completely
component.stopAnimation();
```

## 🌐 Browser Support

- ✅ Chrome 61+
- ✅ Firefox 63+
- ✅ Safari 10.1+
- ✅ Edge 79+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

### Polyfills

For older browsers, include web components polyfill:

```html
<script src="https://unpkg.com/@webcomponents/webcomponentsjs@2.6.0/webcomponents-bundle.js"></script>
```

## 🐛 Troubleshooting

### Common Issues

**Component not rendering:**
- Ensure the script is loaded with `type="module"`
- Check that the JSON data is valid
- Verify browser support for web components

**Logos not loading:**
- Check image URLs are accessible
- Verify CORS settings for external images
- Ensure images are in supported formats (PNG, JPG, SVG, WebP)

**Animation not smooth:**
- Check for conflicting CSS animations
- Ensure no JavaScript errors are blocking the animation loop
- Try reducing the number of logos for better performance

### Debug Mode

Enable debug logging by setting a global variable:

```javascript
window.VENUE_LOGOS_DEBUG = true;
```

## 📄 License

This component is part of the PRIMA Hospitality Platform and is available under the MIT License.

## 🤝 Contributing

Contributions are welcome! Please see our contribution guidelines for more information.

## 📞 Support

For support and questions, please contact the PRIMA development team.

---

**Version**: 1.0.0
**Last Updated**: December 2024
**Compatibility**: Modern browsers with ES6+ support
