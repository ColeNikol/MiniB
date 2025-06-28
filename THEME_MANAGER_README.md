# Theme Manager - Advanced Theme Customization System

## Overview

The Theme Manager is a comprehensive system that allows administrators to customize their blog's appearance and branding through an intuitive modal interface. It provides extensive customization options for colors, typography, media, social links, and advanced features.

## Features

### 🎨 **Visual Customization**
- **Color Scheme**: Customize primary, secondary, accent, text, and background colors
- **Hero Section**: Modify hero title, subtitle, button text, and background (color or image)
- **Typography**: Adjust text colors and styling
- **Real-time Preview**: See changes immediately in the interface

### 📱 **Media Management**
- **Logo Upload**: Upload and manage your site logo
- **Favicon**: Customize your site's favicon
- **Open Graph Images**: Set images for social media sharing
- **Hero Background Images**: Upload background images for the hero section
- **File Support**: Supports JPG, PNG, GIF, SVG, ICO, and WebP formats

### 🌐 **Site Information**
- **Site Name**: Customize your blog's name
- **Site Title**: Set the page title for SEO
- **Site Description**: Add meta descriptions for better SEO
- **Contact Information**: Add email and phone contact details
- **Footer Text**: Customize footer content

### 📢 **Social Media Integration**
- **Facebook**: Add your Facebook page URL
- **Twitter**: Link to your Twitter profile
- **Instagram**: Connect your Instagram account
- **LinkedIn**: Add your LinkedIn profile
- **Automatic Display**: Social links appear in the footer when configured

### 🔧 **Advanced Features**
- **Custom CSS**: Add your own CSS styles
- **Custom JavaScript**: Include custom JavaScript code
- **Google Analytics**: Add Google Analytics tracking code
- **Open Graph Tags**: Configure social media sharing metadata

## How to Use

### Accessing the Theme Manager

1. **Login as Admin**: Use the admin login (username: `admin`, password: `password123`)
2. **Navigate to Theme Manager**: Click the "Theme Manager" button in the admin dashboard
3. **Start Customizing**: Use the modal interface to make changes

### Theme Customization Process

1. **Open the Modal**: Click "Customize Theme" on any theme
2. **Use the Tabs**: Navigate between different customization sections:
   - **General**: Site information and contact details
   - **Colors**: Color scheme customization
   - **Hero Section**: Hero area content and styling
   - **Media**: Logo, favicon, and image uploads
   - **Social**: Social media links
   - **Advanced**: Custom CSS, JavaScript, and analytics

3. **Save Changes**: Click "Save Changes" to apply your customizations
4. **View Results**: Your changes will be applied immediately to the site

### File Upload Process

1. **Select File Type**: Choose what you're uploading (logo, favicon, etc.)
2. **Click Upload**: Use the upload button for the specific file type
3. **Choose File**: Select your image file from your computer
4. **Automatic Processing**: The file will be uploaded and the URL will be automatically filled

## Technical Details

### File Structure

```
themes/
├── default/
│   ├── options.json          # Theme customizations
│   ├── assets/
│   │   └── uploads/          # Uploaded media files
│   ├── style.css            # Theme stylesheet
│   └── functions.php        # Theme functions
├── dark/
│   └── ...                  # Dark theme files
└── theme-manager.php        # Theme management interface
```

### Customization Storage

- **Session Storage**: Customizations are stored in PHP sessions for immediate access
- **File Storage**: Customizations are saved to `options.json` files for persistence
- **Media Files**: Uploaded files are stored in theme-specific upload directories

### CSS Customization

The system generates dynamic CSS based on your color choices:

```css
:root {
    --primary-color: #your-color;
    --secondary-color: #your-color;
    --accent-color: #your-color;
    --text-color: #your-color;
    --background-color: #your-color;
}
```

### Supported File Types

- **Images**: JPG, JPEG, PNG, GIF, SVG, WebP
- **Icons**: ICO files for favicons
- **Maximum Size**: 10MB per file (configurable)

## Security Features

- **Admin Only Access**: Theme manager is restricted to admin users
- **File Type Validation**: Only allowed file types can be uploaded
- **Path Traversal Protection**: Secure file handling prevents directory traversal
- **XSS Protection**: All user input is properly escaped

## Browser Compatibility

- **Modern Browsers**: Chrome, Firefox, Safari, Edge (latest versions)
- **Color Picker**: Native HTML5 color picker support
- **File Upload**: Modern file upload with drag-and-drop support
- **Responsive Design**: Works on desktop, tablet, and mobile devices

## Troubleshooting

### Common Issues

1. **Changes Not Appearing**: Clear your browser cache (Ctrl+F5)
2. **Upload Failed**: Check file size and type restrictions
3. **Colors Not Updating**: Ensure you're using valid hex color codes
4. **Modal Not Opening**: Check for JavaScript errors in browser console

### File Permissions

Ensure the following directories are writable:
- `themes/default/assets/uploads/`
- `themes/default/` (for options.json)

### Performance Tips

- **Image Optimization**: Compress images before uploading for faster loading
- **CSS Optimization**: Use efficient CSS selectors in custom CSS
- **JavaScript**: Minimize custom JavaScript for better performance

## Future Enhancements

- **Theme Templates**: Pre-built theme templates
- **Import/Export**: Backup and restore theme settings
- **Live Preview**: Real-time preview of changes
- **Advanced Typography**: Font family and size customization
- **Layout Options**: Different layout templates
- **Mobile Customization**: Separate mobile theme settings

## Support

For issues or questions about the Theme Manager:

1. Check the troubleshooting section above
2. Review browser console for JavaScript errors
3. Verify file permissions on the server
4. Ensure all required files are present

---

**Note**: The Theme Manager is designed to be user-friendly while providing powerful customization options. All changes are applied immediately and are persistent across sessions. 