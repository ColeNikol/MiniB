# MiniB Theme System

A WordPress-like theme system for MiniB blog that allows you to easily switch between themes and customize your blog's appearance.

## 🎨 Features

- **WordPress-like Structure**: Familiar theme organization with `style.css`, `functions.php`, and template files
- **Multiple Themes**: Support for multiple themes with easy switching
- **Theme Options**: Customizable theme settings stored in session
- **Template System**: Modular template files (header, footer, index, single)
- **Theme Functions**: WordPress-like functions for theme customization
- **SEO Support**: Built-in meta tags and Open Graph support
- **Analytics Integration**: Google Analytics, Tag Manager, and Facebook Pixel support
- **Responsive Design**: Mobile-first responsive layouts
- **Dark Mode**: Built-in dark mode support with theme toggle

## 📁 Theme Structure

```
themes/
├── default/                 # Default theme
│   ├── style.css           # Main stylesheet with theme headers
│   ├── functions.php       # Theme-specific functions
│   ├── header.php          # Header template
│   ├── footer.php          # Footer template
│   ├── index.php           # Homepage template
│   ├── single.php          # Single post template
│   └── assets/             # Theme assets
│       ├── css/            # Additional CSS files
│       ├── js/             # JavaScript files
│       └── images/         # Theme images
├── dark/                   # Dark theme
│   ├── style.css
│   └── ...
└── your-theme/             # Your custom theme
    ├── style.css
    └── ...
```

## 🚀 Getting Started

### 1. Theme Configuration

The theme system is configured through `theme-config.php`:

```php
// Include theme configuration
require_once 'theme-config.php';

// Use theme templates
includeThemeTemplate('header', $data);
includeThemeTemplate('footer', $data);
```

### 2. Creating a New Theme

1. **Create theme directory**:
   ```bash
   mkdir themes/your-theme-name
   ```

2. **Create `style.css`** with WordPress-style headers:
   ```css
   /*
   Theme Name: Your Theme Name
   Description: Your theme description
   Version: 1.0.0
   Author: Your Name
   Author URI: https://yoursite.com
   */
   ```

3. **Create template files**:
   - `header.php` - Header template
   - `footer.php` - Footer template
   - `index.php` - Homepage template
   - `single.php` - Single post template
   - `functions.php` - Theme functions (optional)

### 3. Theme Functions

Create `functions.php` for theme-specific functionality:

```php
<?php
// Theme setup
function theme_setup() {
    add_theme_support('custom-logo');
    add_theme_support('post-thumbnails');
}

// Custom functions
function get_blog_title() {
    return getThemeOption('blog_title', 'My Blog');
}

// Initialize theme
theme_setup();
?>
```

## 🎛️ Theme Options

### Available Functions

- `getCurrentTheme()` - Get current theme name
- `setCurrentTheme($theme_name)` - Switch to a theme
- `getThemeOption($option_name, $default)` - Get theme option
- `setThemeOption($option_name, $value)` - Set theme option
- `getAvailableThemes()` - Get list of available themes
- `getThemeInfo($theme_name)` - Get theme information

### Built-in Options

- `blog_title` - Blog title
- `blog_description` - Blog description
- `custom_logo_text` - Logo text
- `primary_color` - Primary color
- `accent_color` - Accent color
- `footer_text` - Footer text
- `social_links` - Social media links

### Example Usage

```php
// Get theme option
$blog_title = getThemeOption('blog_title', 'Default Blog Title');

// Set theme option
setThemeOption('primary_color', '#3b82f6');

// Get social links
$social_links = getThemeOption('social_links', []);
```

## 📄 Template System

### Template Functions

- `includeThemeTemplate($template_name, $data)` - Include a template with data
- `getThemeTemplate($template_name)` - Get template file path
- `enqueueThemeStyles()` - Enqueue theme stylesheets
- `enqueueThemeScripts()` - Enqueue theme scripts

### Template Variables

Templates receive data through the `$data` array:

```php
includeThemeTemplate('header', [
    'page_title' => 'My Page',
    'page_description' => 'Page description',
    'is_admin' => true
]);
```

### Available Templates

- `header` - Header template
- `footer` - Footer template
- `index` - Homepage template
- `single` - Single post template

## 🎨 Styling

### CSS Classes

The theme system provides consistent CSS classes:

- `.post-card` - Post card styling
- `.btn`, `.btn-primary`, `.btn-success`, `.btn-danger` - Button styles
- `.theme-toggle` - Theme toggle button
- `.logo` - Logo styling
- `.fade-in`, `.slide-in` - Animation classes

### Dark Mode

Dark mode is automatically supported through CSS classes:

```css
.dark body {
    background-color: #111827;
    color: #f9fafb;
}

.dark .bg-white {
    background-color: #1f2937;
}
```

## 🔧 Admin Features

### Theme Switcher

Access the theme switcher at `/theme-switcher.php` (admin only):

- Switch between themes
- Customize theme options
- Preview theme changes
- Manage social media links

### Admin Functions

- Theme switching
- Theme option management
- Theme preview
- Analytics configuration

## 📱 Responsive Design

Themes are mobile-first and responsive by default:

```css
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .search-container {
        width: 100%;
    }
}
```

## 🔍 SEO Features

### Meta Tags

Templates automatically include SEO meta tags:

```php
// Set page variables
$page_title = 'My Page Title';
$page_description = 'Page description for SEO';

// Include header with SEO data
includeThemeTemplate('header', [
    'page_title' => $page_title,
    'page_description' => $page_description,
    'og_title' => $page_title,
    'og_description' => $page_description,
    'og_image' => $og_image
]);
```

### Open Graph

Automatic Open Graph meta tags for social sharing:

- `og:title`
- `og:description`
- `og:image`
- `og:type`
- `og:url`

### Twitter Cards

Twitter Card meta tags for better social sharing:

- `twitter:card`
- `twitter:title`
- `twitter:description`
- `twitter:image`

## 📊 Analytics

### Google Analytics

Configure Google Analytics in theme options:

```php
setThemeOption('google_analytics', 'GA_MEASUREMENT_ID');
```

### Google Tag Manager

Configure Google Tag Manager:

```php
setThemeOption('google_tag_manager', 'GTM_CONTAINER_ID');
```

### Facebook Pixel

Configure Facebook Pixel:

```php
setThemeOption('facebook_pixel', 'PIXEL_ID');
```

## 🎯 Best Practices

### Theme Development

1. **Use WordPress-style headers** in `style.css`
2. **Create modular templates** for reusability
3. **Use theme functions** for customization
4. **Follow responsive design** principles
5. **Test across browsers** and devices

### Performance

1. **Minimize CSS and JS** files
2. **Use lazy loading** for images
3. **Optimize images** for web
4. **Cache theme options** when possible

### Security

1. **Validate theme options** before saving
2. **Sanitize user input** in templates
3. **Use proper escaping** for output
4. **Check file permissions** for theme directories

## 🔄 Migration from Old System

To migrate from the old hardcoded system:

1. **Backup your current files**
2. **Include theme-config.php** in your main files
3. **Replace hardcoded HTML** with template includes
4. **Move styles** to theme `style.css` files
5. **Test thoroughly** before going live

## 📝 Example Theme

See the `themes/default/` directory for a complete example theme with all features implemented.

## 🤝 Contributing

To contribute themes:

1. Follow the theme structure
2. Include proper documentation
3. Test across different devices
4. Ensure accessibility compliance
5. Optimize for performance

## 📄 License

This theme system is part of MiniB and follows the same license terms.

---

For more information, see the main MiniB documentation or contact the development team. 