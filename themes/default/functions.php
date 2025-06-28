<?php
/**
 * Default Theme Functions
 * Theme-specific functions and customizations
 */

// Theme setup function
function theme_setup() {
    // Add theme support for various features
    add_theme_support('custom-logo');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-background');
    add_theme_support('custom-header');
}

// Add theme support function (WordPress-like)
function add_theme_support($feature) {
    // Store supported features in session
    if (!isset($_SESSION['theme_support'])) {
        $_SESSION['theme_support'] = [];
    }
    $_SESSION['theme_support'][] = $feature;
}

// Check if theme supports a feature
function current_theme_supports($feature) {
    $supported_features = $_SESSION['theme_support'] ?? [];
    return in_array($feature, $supported_features);
}

// Get blog title
function get_blog_title() {
    return getThemeOption('blog_title', 'MiniB Blog');
}

// Get blog description
function get_blog_description() {
    return getThemeOption('blog_description', 'A minimalistic blog powered by MiniB');
}

// Get custom logo URL
function get_custom_logo_url() {
    return getThemeOption('custom_logo_url', '');
}

// Get custom logo text
function get_custom_logo_text() {
    return getThemeOption('custom_logo_text', 'MiniB');
}

// Get primary color
function get_primary_color() {
    return getThemeOption('primary_color', '#3b82f6');
}

// Get accent color
function get_accent_color() {
    return getThemeOption('accent_color', '#10b981');
}

// Get footer text
function get_footer_text() {
    return getThemeOption('footer_text', '© 2023 MiniB Blog. All rights reserved.');
}

// Get social media links
function get_social_links() {
    return getThemeOption('social_links', [
        'twitter' => '',
        'facebook' => '',
        'instagram' => '',
        'github' => '',
        'linkedin' => ''
    ]);
}

// Get navigation menu items
function get_navigation_menu() {
    return getThemeOption('navigation_menu', [
        ['title' => 'Home', 'url' => './'],
        ['title' => 'About', 'url' => '#about'],
        ['title' => 'Contact', 'url' => '#contact']
    ]);
}

// Get sidebar widgets
function get_sidebar_widgets() {
    return getThemeOption('sidebar_widgets', [
        'search' => true,
        'categories' => true,
        'recent_posts' => true,
        'tags' => true
    ]);
}

// Get post display options
function get_post_display_options() {
    return getThemeOption('post_display_options', [
        'show_author' => false,
        'show_date' => true,
        'show_tags' => true,
        'show_views' => true,
        'show_excerpt' => true,
        'excerpt_length' => 150
    ]);
}

// Get theme layout
function get_theme_layout() {
    return getThemeOption('theme_layout', 'default'); // default, sidebar-left, sidebar-right, full-width
}

// Get theme style
function get_theme_style() {
    return getThemeOption('theme_style', 'modern'); // modern, classic, minimal, dark
}

// Get theme typography
function get_theme_typography() {
    return getThemeOption('theme_typography', [
        'heading_font' => 'Inter',
        'body_font' => 'Inter',
        'font_size' => '16px',
        'line_height' => '1.6'
    ]);
}

// Get theme spacing
function get_theme_spacing() {
    return getThemeOption('theme_spacing', [
        'container_padding' => '1rem',
        'section_spacing' => '3rem',
        'element_spacing' => '1rem'
    ]);
}

// Get theme colors
function get_theme_colors() {
    return getThemeOption('theme_colors', [
        'primary' => '#3b82f6',
        'secondary' => '#10b981',
        'accent' => '#f59e0b',
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
        'info' => '#3b82f6',
        'light' => '#f9fafb',
        'dark' => '#111827'
    ]);
}

// Get theme animations
function get_theme_animations() {
    return getThemeOption('theme_animations', [
        'enable_animations' => true,
        'animation_duration' => '0.3s',
        'animation_easing' => 'ease-out'
    ]);
}

// Get theme performance options
function get_theme_performance() {
    return getThemeOption('theme_performance', [
        'lazy_loading' => true,
        'minify_css' => false,
        'minify_js' => false,
        'cache_enabled' => false
    ]);
}

// Get theme SEO options
function get_theme_seo() {
    return getThemeOption('theme_seo', [
        'meta_description' => '',
        'meta_keywords' => '',
        'og_image' => '',
        'twitter_card' => 'summary',
        'structured_data' => true
    ]);
}

// Get theme analytics
function get_theme_analytics() {
    return getThemeOption('theme_analytics', [
        'google_analytics' => '',
        'google_tag_manager' => '',
        'facebook_pixel' => ''
    ]);
}

// Get theme custom CSS
function get_theme_custom_css() {
    return getThemeOption('custom_css', '');
}

// Get theme custom JS
function get_theme_custom_js() {
    return getThemeOption('custom_js', '');
}

// Format post date
function format_post_date($date_string) {
    $date = new DateTime($date_string);
    return $date->format('F d, Y');
}

// Get post excerpt
function get_post_excerpt($content, $length = 150) {
    $text = strip_tags($content);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// Get post reading time
function get_post_reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Average reading speed: 200 words per minute
    return $reading_time . ' min read';
}

// Get post category
function get_post_category($tags) {
    if (empty($tags)) {
        return 'Uncategorized';
    }
    return ucfirst($tags[0]);
}

// Get post thumbnail
function get_post_thumbnail($post_slug, $metadata) {
    if (!empty($metadata['thumbnail'])) {
        return $metadata['thumbnail'];
    }
    
    // Check for default thumbnail in post directory
    $thumbnail_path = './' . $post_slug . '/thumbnail.png';
    if (file_exists($thumbnail_path)) {
        return $post_slug . '/thumbnail.png';
    }
    
    // Check for any image in post directory
    $post_dir = './' . $post_slug;
    if (is_dir($post_dir)) {
        $images = glob($post_dir . '/*.{jpg,jpeg,png,gif,avif}', GLOB_BRACE);
        if (!empty($images)) {
            return $post_slug . '/' . basename($images[0]);
        }
    }
    
    return '';
}

// Get related posts
function get_related_posts($current_slug, $metadata, $limit = 3) {
    $related_posts = [];
    $current_tags = $metadata[$current_slug]['tags'] ?? [];
    
    foreach ($metadata as $slug => $post) {
        if ($slug === $current_slug) {
            continue;
        }
        
        $post_tags = $post['tags'] ?? [];
        $common_tags = array_intersect($current_tags, $post_tags);
        
        if (!empty($common_tags)) {
            $related_posts[] = [
                'slug' => $slug,
                'title' => $post['title'],
                'thumbnail' => get_post_thumbnail($slug, $metadata),
                'date' => $post['created_at'],
                'views' => $post['views'] ?? 0,
                'tags' => $post['tags']
            ];
        }
        
        if (count($related_posts) >= $limit) {
            break;
        }
    }
    
    return $related_posts;
}

// Get popular posts
function get_popular_posts($metadata, $limit = 5) {
    $posts = [];
    
    foreach ($metadata as $slug => $post) {
        $posts[] = [
            'slug' => $slug,
            'title' => $post['title'],
            'thumbnail' => get_post_thumbnail($slug, $metadata),
            'date' => $post['created_at'],
            'views' => $post['views'] ?? 0,
            'tags' => $post['tags']
        ];
    }
    
    // Sort by views (descending)
    usort($posts, function($a, $b) {
        return $b['views'] - $a['views'];
    });
    
    return array_slice($posts, 0, $limit);
}

// Get recent posts
function get_recent_posts($metadata, $limit = 5) {
    $posts = [];
    
    foreach ($metadata as $slug => $post) {
        $posts[] = [
            'slug' => $slug,
            'title' => $post['title'],
            'thumbnail' => get_post_thumbnail($slug, $metadata),
            'date' => $post['created_at'],
            'views' => $post['views'] ?? 0,
            'tags' => $post['tags']
        ];
    }
    
    // Sort by date (descending)
    usort($posts, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return array_slice($posts, 0, $limit);
}

// Get all categories
function get_all_categories($metadata) {
    $categories = [];
    
    foreach ($metadata as $post) {
        $tags = $post['tags'] ?? [];
        foreach ($tags as $tag) {
            if (!isset($categories[$tag])) {
                $categories[$tag] = 0;
            }
            $categories[$tag]++;
        }
    }
    
    arsort($categories);
    return $categories;
}

// Get all tags
function get_all_tags($metadata) {
    $tags = [];
    
    foreach ($metadata as $post) {
        $post_tags = $post['tags'] ?? [];
        foreach ($post_tags as $tag) {
            if (!isset($tags[$tag])) {
                $tags[$tag] = 0;
            }
            $tags[$tag]++;
        }
    }
    
    arsort($tags);
    return $tags;
}

// Initialize theme
theme_setup();
?> 