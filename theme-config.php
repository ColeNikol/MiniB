<?php
/**
 * Theme System Configuration
 * WordPress-like theme system for MiniB
 */

// Theme configuration
define('THEMES_DIR', __DIR__ . '/themes');
define('DEFAULT_THEME', 'default');

// Get current theme from session or use default
function getCurrentTheme() {
    return $_SESSION['current_theme'] ?? DEFAULT_THEME;
}

// Set current theme
function setCurrentTheme($theme_name) {
    if (isValidTheme($theme_name)) {
        $_SESSION['current_theme'] = $theme_name;
        return true;
    }
    return false;
}

// Check if theme is valid
function isValidTheme($theme_name) {
    $theme_path = THEMES_DIR . '/' . $theme_name;
    return is_dir($theme_path) && file_exists($theme_path . '/style.css');
}

// Get theme path
function getThemePath($theme_name = null) {
    $theme_name = $theme_name ?: getCurrentTheme();
    return THEMES_DIR . '/' . $theme_name;
}

// Get theme URL
function getThemeUrl($theme_name = null) {
    $theme_name = $theme_name ?: getCurrentTheme();
    return './themes/' . $theme_name;
}

// Get theme assets URL
function getThemeAssetsUrl($theme_name = null) {
    return getThemeUrl($theme_name) . '/assets';
}

// Get available themes
function getAvailableThemes() {
    $themes = [];
    $themes_dir = THEMES_DIR;
    
    if (is_dir($themes_dir)) {
        $theme_dirs = scandir($themes_dir);
        foreach ($theme_dirs as $theme_dir) {
            if ($theme_dir !== '.' && $theme_dir !== '..' && is_dir($themes_dir . '/' . $theme_dir)) {
                $style_file = $themes_dir . '/' . $theme_dir . '/style.css';
                if (file_exists($style_file)) {
                    $theme_info = getThemeInfo($theme_dir);
                    $themes[$theme_dir] = $theme_info;
                }
            }
        }
    }
    
    return $themes;
}

// Get theme information from style.css header
function getThemeInfo($theme_name) {
    $style_file = getThemePath($theme_name) . '/style.css';
    $theme_info = [
        'name' => $theme_name,
        'title' => ucfirst($theme_name),
        'description' => '',
        'version' => '1.0.0',
        'author' => '',
        'author_url' => ''
    ];
    
    if (file_exists($style_file)) {
        $content = file_get_contents($style_file);
        
        // Parse theme headers (WordPress style)
        if (preg_match('/Theme Name:\s*(.+)$/m', $content, $matches)) {
            $theme_info['title'] = trim($matches[1]);
        }
        if (preg_match('/Description:\s*(.+)$/m', $content, $matches)) {
            $theme_info['description'] = trim($matches[1]);
        }
        if (preg_match('/Version:\s*(.+)$/m', $content, $matches)) {
            $theme_info['version'] = trim($matches[1]);
        }
        if (preg_match('/Author:\s*(.+)$/m', $content, $matches)) {
            $theme_info['author'] = trim($matches[1]);
        }
        if (preg_match('/Author URI:\s*(.+)$/m', $content, $matches)) {
            $theme_info['author_url'] = trim($matches[1]);
        }
    }
    
    return $theme_info;
}

// Load theme functions
function loadThemeFunctions($theme_name = null) {
    $theme_name = $theme_name ?: getCurrentTheme();
    $functions_file = getThemePath($theme_name) . '/functions.php';
    
    if (file_exists($functions_file)) {
        include_once $functions_file;
    }
}

// Get theme template
function getThemeTemplate($template_name, $theme_name = null) {
    $theme_name = $theme_name ?: getCurrentTheme();
    $template_file = getThemePath($theme_name) . '/' . $template_name . '.php';
    
    if (file_exists($template_file)) {
        return $template_file;
    }
    
    // Fallback to default theme
    if ($theme_name !== DEFAULT_THEME) {
        return getThemeTemplate($template_name, DEFAULT_THEME);
    }
    
    return false;
}

// Include theme template
function includeThemeTemplate($template_name, $data = []) {
    $template_file = getThemeTemplate($template_name);
    
    if ($template_file) {
        // Extract data to variables
        extract($data);
        include $template_file;
        return true;
    }
    
    return false;
}

// Enqueue theme styles
function enqueueThemeStyles() {
    $current_theme = getCurrentTheme();
    $theme_url = getThemeUrl($current_theme);
    
    // Main theme stylesheet
    echo '<link rel="stylesheet" href="' . $theme_url . '/style.css">' . "\n";
    
    // Additional CSS files
    $css_dir = getThemePath($current_theme) . '/assets/css';
    if (is_dir($css_dir)) {
        $css_files = glob($css_dir . '/*.css');
        foreach ($css_files as $css_file) {
            $css_name = basename($css_file);
            echo '<link rel="stylesheet" href="' . getThemeAssetsUrl($current_theme) . '/css/' . $css_name . '">' . "\n";
        }
    }
}

// Enqueue theme scripts
function enqueueThemeScripts() {
    $current_theme = getCurrentTheme();
    $js_dir = getThemePath($current_theme) . '/assets/js';
    
    if (is_dir($js_dir)) {
        $js_files = glob($js_dir . '/*.js');
        foreach ($js_files as $js_file) {
            $js_name = basename($js_file);
            echo '<script src="' . getThemeAssetsUrl($current_theme) . '/js/' . $js_name . '"></script>' . "\n";
        }
    }
}

// Get theme option
function getThemeOption($option_name, $default = null) {
    $theme_options = $_SESSION['theme_options'] ?? [];
    $current_theme = getCurrentTheme();
    
    return $theme_options[$current_theme][$option_name] ?? $default;
}

// Set theme option
function setThemeOption($option_name, $value) {
    if (!isset($_SESSION['theme_options'])) {
        $_SESSION['theme_options'] = [];
    }
    
    $current_theme = getCurrentTheme();
    if (!isset($_SESSION['theme_options'][$current_theme])) {
        $_SESSION['theme_options'][$current_theme] = [];
    }
    
    $_SESSION['theme_options'][$current_theme][$option_name] = $value;
}

// Initialize theme system
function initThemeSystem() {
    // Load theme functions
    loadThemeFunctions();
    
    // Set default theme if none is set
    if (!isset($_SESSION['current_theme'])) {
        setCurrentTheme(DEFAULT_THEME);
    }
}

// Initialize theme system when this file is included
initThemeSystem();

// Apply theme customizations
function applyThemeCustomizations() {
    $current_theme = getCurrentTheme();
    $theme_options_file = getThemePath($current_theme) . '/options.json';
    
    // Debug: Log theme loading process
    error_log("Applying theme customizations for theme: " . $current_theme);
    error_log("Theme options file: " . $theme_options_file);
    
    // Load options from file
    $file_options = [];
    if (file_exists($theme_options_file)) {
        $file_options = json_decode(file_get_contents($theme_options_file), true) ?: [];
        error_log("Loaded file options: " . json_encode($file_options));
    } else {
        error_log("Theme options file does not exist");
    }
    
    // Load options from session
    $session_options = $_SESSION['theme_options'][$current_theme] ?? [];
    error_log("Session options: " . json_encode($session_options));
    
    // Merge options (file takes precedence over session for immediate updates)
    $options = array_merge($session_options, $file_options);
    error_log("Final merged options: " . json_encode($options));
    
    // Update session with file options to keep them in sync
    if (!empty($file_options)) {
        if (!isset($_SESSION['theme_options'])) {
            $_SESSION['theme_options'] = [];
        }
        $_SESSION['theme_options'][$current_theme] = $file_options;
        error_log("Updated session with file options");
    }
    
    // Generate dynamic CSS based on customizations
    $dynamic_css = generateDynamicCSS($options);
    
    // Output the dynamic CSS
    echo '<style id="theme-customizations">' . $dynamic_css . '</style>';
    
    // Output custom JavaScript
    if (!empty($options['custom_js'])) {
        echo '<script id="theme-custom-js">' . $options['custom_js'] . '</script>';
    }
    
    // Output analytics code
    if (!empty($options['analytics_code'])) {
        echo $options['analytics_code'];
    }
    
    return $options;
}

// Generate dynamic CSS from theme options
function generateDynamicCSS($options) {
    $css = '';
    
    // Color customizations
    if (!empty($options['primary_color'])) {
        $css .= ":root { --primary-color: {$options['primary_color']}; }\n";
        $css .= ".bg-blue-600, .bg-blue-500 { background-color: {$options['primary_color']} !important; }\n";
        $css .= ".text-blue-600, .text-blue-500 { color: {$options['primary_color']} !important; }\n";
        $css .= ".border-blue-500, .border-blue-600 { border-color: {$options['primary_color']} !important; }\n";
    }
    
    if (!empty($options['secondary_color'])) {
        $css .= ":root { --secondary-color: {$options['secondary_color']}; }\n";
        $css .= ".bg-gray-800, .bg-gray-900 { background-color: {$options['secondary_color']} !important; }\n";
    }
    
    if (!empty($options['accent_color'])) {
        $css .= ":root { --accent-color: {$options['accent_color']}; }\n";
        $css .= ".bg-green-500, .bg-green-600 { background-color: {$options['accent_color']} !important; }\n";
        $css .= ".text-green-500, .text-green-600 { color: {$options['accent_color']} !important; }\n";
    }
    
    if (!empty($options['text_color'])) {
        $css .= ":root { --text-color: {$options['text_color']}; }\n";
        $css .= ".text-gray-800, .text-gray-900 { color: {$options['text_color']} !important; }\n";
    }
    
    if (!empty($options['background_color'])) {
        $css .= ":root { --background-color: {$options['background_color']}; }\n";
        $css .= "body { background-color: {$options['background_color']} !important; }\n";
    }
    
    // Hero section customizations
    if (!empty($options['hero_background_type'])) {
        if ($options['hero_background_type'] === 'color' && !empty($options['hero_background_color'])) {
            $css .= ".hero-section { background-color: {$options['hero_background_color']} !important; }\n";
        } elseif ($options['hero_background_type'] === 'image' && !empty($options['hero_background_image'])) {
            $css .= ".hero-section { background-image: url('{$options['hero_background_image']}') !important; background-size: cover; background-position: center; }\n";
        }
    }
    
    // Custom CSS
    if (!empty($options['custom_css'])) {
        $css .= $options['custom_css'] . "\n";
    }
    
    return $css;
}

// Get theme option with fallback
function getThemeOptionWithFallback($option_name, $default = null) {
    $value = getThemeOption($option_name, $default);
    
    // If the option is empty, try to get it from the options.json file
    if (empty($value)) {
        $current_theme = getCurrentTheme();
        $theme_options_file = getThemePath($current_theme) . '/options.json';
        
        if (file_exists($theme_options_file)) {
            $options = json_decode(file_get_contents($theme_options_file), true) ?: [];
            $value = $options[$option_name] ?? $default;
        }
    }
    
    return $value;
}

// Output theme meta tags
function outputThemeMetaTags() {
    $options = applyThemeCustomizations();
    
    // Site title
    $site_title = $options['site_title'] ?? 'MiniB - Minimal Blog';
    echo '<title>' . htmlspecialchars($site_title) . '</title>' . "\n";
    
    // Site description
    if (!empty($options['site_description'])) {
        echo '<meta name="description" content="' . htmlspecialchars($options['site_description']) . '">' . "\n";
    }
    
    // Favicon
    if (!empty($options['favicon_url'])) {
        echo '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars($options['favicon_url']) . '">' . "\n";
    }
    
    // Open Graph tags
    if (!empty($options['og_title'])) {
        echo '<meta property="og:title" content="' . htmlspecialchars($options['og_title']) . '">' . "\n";
    }
    if (!empty($options['og_description'])) {
        echo '<meta property="og:description" content="' . htmlspecialchars($options['og_description']) . '">' . "\n";
    }
    if (!empty($options['og_image_url'])) {
        echo '<meta property="og:image" content="' . htmlspecialchars($options['og_image_url']) . '">' . "\n";
    }
    
    // Logo
    if (!empty($options['logo_url'])) {
        echo '<meta property="og:logo" content="' . htmlspecialchars($options['logo_url']) . '">' . "\n";
    }
}
?> 