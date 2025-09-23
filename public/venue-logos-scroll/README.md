# Venue Logos Scroll Web Component

A responsive, customizable web component for displaying venue logos in an animated, infinite scroll. Perfect for showcasing partner venues, sponsors, or featured locations on any website.

## ✨ Features

- **🚀 Dynamic API Integration**: Fetches real venue data from your Laravel API
- **📱 Responsive Design**: Automatically adapts to mobile, tablet, and desktop screens
- **🎨 Smooth Animations**: Infinite scrolling with customizable speed and direction
- **♿ Accessibility**: Proper ARIA labels, keyboard navigation support
- **🎭 Theme Support**: Built-in dark mode and customizable CSS properties
- **⚡ Performance**: Optimized animations using `requestAnimationFrame`
- **🌐 Browser Compatible**: Works in all modern browsers with no dependencies
- **🔧 Customizable**: Extensive configuration options for appearance and behavior

## 📦 Quick Start

### 1. Include the Component

```html
<script src="/venue-logos-scroll/venue-logos-scroll.html" type="module"></script>
```

### 2. Add to Your Page

```html
<venue-logos-scroll
    api-endpoint="/api/venue-logos"
    refresh-interval="300"
    loading-text="Loading venues from PRIMA..."
    pause-on-hover>
</venue-logos-scroll>
```

### 3. Customize (Optional)

```html
<venue-logos-scroll
    api-endpoint="https://prima.test/api/venue-logos?limit=8&tier=premium"
    duration="40"
    direction="opposite"
    speed="slow"
    refresh-interval="600"
    cache-time="900000"
    pause-on-hover
    show-placeholders>
</venue-logos-scroll>
```

## 🔌 Laravel Integration

The component is designed to work seamlessly with your PRIMA Laravel application:

### API Endpoint

```php
// In your Laravel routes
Route::get('/venue-logos', [VenueLogosController::class, 'index']);
```

### Response Format

```json
{
    "first_row": [
        {
            "id": 1,
            "name": "The Rooftop",
            "logo_path": "https://prima.test/storage/venue-logos/rooftop.png"
        }
    ],
    "second_row": [
        {
            "id": 2,
            "name": "Sky Lounge",
            "logo_path": "https://prima.test/storage/venue-logos/sky.png"
        }
    ],
    "total_venues": 12,
    "generated_at": "2024-12-23T10:30:00Z"
}
```

## ⚙️ Configuration

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `api-endpoint` | string | `null` | API endpoint URL to fetch venue data |
| `refresh-interval` | number | `0` | Auto-refresh interval in seconds |
| `cache-time` | number | `300000` | Cache duration in milliseconds |
| `loading-text` | string | `"Loading venues..."` | Loading message text |
| `duration` | number | `35` | Animation duration in seconds |
| `direction` | string | `"opposite"` | Scroll direction |
| `speed` | string | `"normal"` | Animation speed |
| `pause-on-hover` | boolean | `false` | Pause on hover |
| `show-placeholders` | boolean | `true` | Show venue names for missing logos |

## 🎨 Theming

### CSS Custom Properties

```css
venue-logos-scroll {
    --background-color: #ffffff;
    --placeholder-bg: #f3f4f6;
    --placeholder-text: #374151;
    --animation-duration: 35s;
}
```

### Dark Theme

```html
<venue-logos-scroll class="dark" ...></venue-logos-scroll>
```

## 📱 Responsive Behavior

- **Mobile** (< 640px): 96px × 60px logos
- **Tablet & Desktop** (≥ 640px): 128px × 80px logos
- Automatically adjusts on window resize

## 🔧 Advanced Usage

### Static Data (for development/testing)

```html
<venue-logos-scroll
    first-row='[{"id":1,"name":"Venue","logo_path":"logo.jpg"}]'
    second-row='[{"id":2,"name":"Another","logo_path":"logo2.jpg"}]'>
</venue-logos-scroll>
```

### Programmatic Control

```javascript
const component = document.querySelector('venue-logos-scroll');

// Refresh data
component.fetchData();

// Pause animation
component.pauseAnimation();

// Resume animation
component.resumeAnimation();
```

## 🌐 Browser Support

- ✅ Chrome 61+
- ✅ Firefox 63+
- ✅ Safari 10.1+
- ✅ Edge 79+
- ✅ Mobile browsers

## 📁 Files

```
public/venue-logos-scroll/
├── venue-logos-scroll.html    # Main component file
├── demo.html                  # Demo page
├── docs/
│   └── documentation.md       # Full documentation
└── README.md                  # This file
```

## 🌐 Deployment

### Important: CORS Requirements

**Web components must be served via HTTP/HTTPS** to avoid CORS restrictions:

1. **Copy to public directory**: `cp -r web-components/venue-logos-scroll public/`
2. **Serve from web server**: Use `php artisan serve` or similar
3. **Use relative URLs**: API endpoints should be relative when on same domain

### Local Development

```bash
# Laravel built-in server
php artisan serve

# Access at: http://localhost:8000/venue-logos-scroll/demo.html
```

### Production

1. Copy component files to your web server's public directory
2. Update script src paths to match your URL structure
3. Ensure API endpoints are accessible from your domain

## 🐛 Troubleshooting

### Component not rendering
- Ensure script is loaded with `type="module"`
- Check browser console for errors
- Verify API endpoint is accessible

### Logos not loading
- Check image URLs are correct
- Verify CORS settings for external images
- Ensure images are in supported formats

### Animation issues
- Check for conflicting CSS animations
- Verify no JavaScript errors blocking animation

## 📄 License

MIT License - Part of the PRIMA Hospitality Platform.

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📞 Support

For support and questions, please contact the PRIMA development team.

---

**Version**: 1.0.0
**Last Updated**: December 2024
**Compatibility**: Modern browsers with ES6+ support
