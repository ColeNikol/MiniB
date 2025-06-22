<?php
// Start session and define constants
session_start();
define('POSTS_DIR', 'posts');
define('METADATA_FILE', 'metadata.json');

// Create directories if they don't exist
if (!is_dir(POSTS_DIR)) mkdir(POSTS_DIR, 0777, true);

// Load metadata
$metadata = [];
if (file_exists(METADATA_FILE)) {
    $metadata = json_decode(file_get_contents(METADATA_FILE), true) ?: [];
}

// Function to extract first paragraph from content
function getFirstParagraph($content) {
    // Remove HTML tags and get first paragraph
    $text = strip_tags($content);
    $sentences = explode('.', $text);
    $firstSentence = trim($sentences[0]);
    if (strlen($firstSentence) > 150) {
        $firstSentence = substr($firstSentence, 0, 150) . '...';
    }
    return $firstSentence;
}

// Function to save base64 image as file
function saveBase64Image($base64Data, $postSlug, $filename) {
    // Remove data:image prefix while preserving the image type
    $base64Data = preg_replace('/^data:image\/(png|jpeg|jpg|gif);base64,/', '', $base64Data);
    
    // Decode base64 data
    $imageData = base64_decode($base64Data);
    
    if ($imageData === false) {
        return false;
    }
    
    // Create post images directory (same as thumbnails)
    $imagesDir = POSTS_DIR . '/' . $postSlug . '/images';
    if (!is_dir($imagesDir)) {
        mkdir($imagesDir, 0777, true);
    }
    
    // Save image file in images directory
    $filePath = $imagesDir . '/' . $filename;
    if (file_put_contents($filePath, $imageData)) {
        return $filePath;
    }
    
    return false;
}

// Function to save uploaded image as file
function saveUploadedImage($uploadedFile, $postSlug, $filename) {
    // Create post images directory (same as thumbnails)
    $imagesDir = POSTS_DIR . '/' . $postSlug . '/images';
    if (!is_dir($imagesDir)) {
        mkdir($imagesDir, 0777, true);
    }
    
    // Save image file
    $filePath = $imagesDir . '/' . $filename;
    if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
        return $filePath;
    }
    
    return false;
}

// Function to process content and save images as files
function processContentImages($content, $postSlug) {
    $processed_content = $content;
    $imageCounter = 1;
    
    // Process base64 images
    if (preg_match_all('/<img[^>]+src="data:image\/[^"]+"/', $content, $matches)) {
        foreach ($matches[0] as $match) {
            if (preg_match('/src="(data:image\/[^"]+)"/', $match, $srcMatch)) {
                $base64Data = $srcMatch[1];
                $filename = 'image_' . $imageCounter . '.png';
                $savedPath = saveBase64Image($base64Data, $postSlug, $filename);
                
                if ($savedPath) {
                    // Replace base64 data with relative file path (images/filename)
                    $relativePath = 'images/' . $filename;
                    $processed_content = str_replace($base64Data, $relativePath, $processed_content);
                    $imageCounter++;
                }
            }
        }
    }
    
    // Process uploaded images (if any are embedded in content)
    if (preg_match_all('/<img[^>]+src="uploaded:\/\/[^"]+"/', $content, $matches)) {
        foreach ($matches[0] as $match) {
            if (preg_match('/src="uploaded:\/\/([^"]+)"/', $match, $srcMatch)) {
                $uploadedPath = $srcMatch[1];
                $filename = 'uploaded_' . $imageCounter . '.jpg';
                $savedPath = saveUploadedImage(['tmp_name' => $uploadedPath], $postSlug, $filename);
                
                if ($savedPath) {
                    // Replace uploaded path with relative file path (images/filename)
                    $relativePath = 'images/' . $filename;
                    $processed_content = str_replace('uploaded://' . $uploadedPath, $relativePath, $processed_content);
                    $imageCounter++;
                }
            }
        }
    }
    
    return $processed_content;
}

// Function to convert HTML content to PHP format
function convertContentToPHP($content, $postSlug) {
    // Start with PHP opening tag
    $phpContent = "<?php\n";
    $phpContent .= "// Post: " . $postSlug . "\n";
    $phpContent .= "// Generated: " . date('Y-m-d H:i:s') . "\n";
    $phpContent .= "?>\n\n";
    
    // Add the HTML content
    $phpContent .= $content;
    
    return $phpContent;
}

// Function to process executable code blocks
function processExecutableCode($content) {
    // Find all executable code blocks
    $pattern = '/<div class="executable-code[^"]*">\s*<div[^>]*>.*?<\/div>\s*<div class="executable-content[^"]*">(.*?)<\/div>\s*<\/div>/s';
    
    return preg_replace_callback($pattern, function($matches) {
        $executableCode = $matches[1];
        
        // Decode HTML entities that might have been encoded
        $executableCode = html_entity_decode($executableCode, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Check if it's PHP code (contains <?php or <?=)
        if (preg_match('/<\?php|\<\?=/', $executableCode)) {
            // It's PHP code, return as-is for execution
            return $executableCode;
        } else {
            // It's JavaScript or other executable code, wrap in script tags
            return '<script>' . $executableCode . '</script>';
        }
    }, $content);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Admin login
    if (isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === 'admin' && $password === 'password123') {
            $_SESSION['admin'] = true;
        } else {
            $error = "Invalid credentials!";
        }
    }
    
    // Admin logout
    if (isset($_POST['logout'])) {
        unset($_SESSION['admin']);
    }
    
    // Increment visit counter
    if (isset($_POST['increment_view'])) {
        $slug = $_POST['slug'] ?? '';
        if (!empty($slug) && isset($metadata[$slug])) {
            // Get visitor IP for tracking
            $visitor_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $current_time = time();
            
            // Create a simple tracking file to prevent multiple counts from same IP
            $tracking_file = POSTS_DIR . '/' . $slug . '/view_tracking.json';
            $tracking_data = [];
            
            if (file_exists($tracking_file)) {
                $tracking_data = json_decode(file_get_contents($tracking_file), true) ?: [];
            }
            
            // Check if this IP has viewed this post in the last 24 hours
            $last_view_time = $tracking_data[$visitor_ip] ?? 0;
            $time_diff = $current_time - $last_view_time;
            
            // Only count view if it's been more than 24 hours since last view from this IP
            if ($time_diff > 86400) { // 24 hours = 86400 seconds
                $metadata[$slug]['views'] = ($metadata[$slug]['views'] ?? 0) + 1;
                file_put_contents(METADATA_FILE, json_encode($metadata, JSON_PRETTY_PRINT));
                
                // Update tracking data
                $tracking_data[$visitor_ip] = $current_time;
                file_put_contents($tracking_file, json_encode($tracking_data, JSON_PRETTY_PRINT));
                
                echo json_encode(['success' => true, 'views' => $metadata[$slug]['views']]);
            } else {
                // Return current view count without incrementing
                echo json_encode(['success' => true, 'views' => $metadata[$slug]['views'] ?? 0, 'already_counted' => true]);
            }
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid post slug']);
            exit;
        }
    }
    
    // Save post
    if (isset($_POST['save_post']) && isset($_SESSION['admin'])) {
        $title = trim($_POST['title']);
        $slug = trim($_POST['slug']);
        $content = trim($_POST['content']);
        $tags = array_slice(array_filter(array_map('trim', explode(',', $_POST['tags']))), 0, 3);
        $thumbnail = $_FILES['thumbnail'] ?? null;
        $selected_thumbnail = $_POST['selected_thumbnail'] ?? '';
        
        // Validate input
        if (empty($title) || empty($slug) || empty($content)) {
            $error = "Title, slug, and content are required!";
        } else {
            // Create post directory
            $post_dir = POSTS_DIR . '/' . $slug;
            if (!is_dir($post_dir)) mkdir($post_dir, 0777, true);
            
            // Create images directory for this post
            $images_dir = $post_dir . '/images';
            if (!is_dir($images_dir)) mkdir($images_dir, 0777, true);
            
            // Handle thumbnail upload
            $thumbnail_path = '';
            if ($thumbnail && $thumbnail['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($thumbnail['name'], PATHINFO_EXTENSION);
                $thumbnail_filename = 'thumbnail.' . $ext;
                $thumbnail_path = $images_dir . '/' . $thumbnail_filename;
                move_uploaded_file($thumbnail['tmp_name'], $thumbnail_path);
            } elseif (!empty($selected_thumbnail)) {
                // Use selected image as thumbnail
                $thumbnail_path = $selected_thumbnail;
            }
            
            // Process content to save images as files and fix paths
            $processed_content = processContentImages($content, $slug);
            
            // Convert content to PHP format
            $php_content = convertContentToPHP($processed_content, $slug);
            
            // Save post content as PHP file
            $post_file = $post_dir . '/index.php';
            file_put_contents($post_file, $php_content);
            
            // Check if this is an update or new post
            $is_update = isset($metadata[$slug]);
            $current_views = $is_update ? ($metadata[$slug]['views'] ?? 0) : 0;
            
            // Save metadata
            $metadata[$slug] = [
                'title' => $title,
                'slug' => $slug,
                'tags' => $tags,
                'thumbnail' => $thumbnail_path,
                'created_at' => $is_update ? $metadata[$slug]['created_at'] : date('Y-m-d H:i:s'),
                'views' => $current_views
            ];
            
            file_put_contents(METADATA_FILE, json_encode($metadata, JSON_PRETTY_PRINT));
            $success = "Post saved successfully!";
        }
    }
    
    // Delete post
    if (isset($_POST['delete_post']) && isset($_SESSION['admin'])) {
        $slug = $_POST['slug'];
        
        if (isset($metadata[$slug])) {
            // Delete post directory and all contents recursively
            $post_dir = POSTS_DIR . '/' . $slug;
            if (is_dir($post_dir)) {
                // Function to recursively delete directory contents
                function deleteDirectory($dir) {
                    if (!is_dir($dir)) {
                        return false;
                    }
                    
                    $files = array_diff(scandir($dir), array('.', '..'));
                    foreach ($files as $file) {
                        $path = $dir . '/' . $file;
                        if (is_dir($path)) {
                            deleteDirectory($path);
                        } else {
                            unlink($path);
                        }
                    }
                    return rmdir($dir);
                }
                
                deleteDirectory($post_dir);
            }
            
            // Remove from metadata
            unset($metadata[$slug]);
            file_put_contents(METADATA_FILE, json_encode($metadata, JSON_PRETTY_PRINT));
            $success = "Post deleted successfully!";
        }
    }
    
    // Theme preference handler
    if (isset($_POST['set_theme'])) {
        $theme = $_POST['theme'] ?? 'light';
        if (in_array($theme, ['light', 'dark'])) {
            $_SESSION['theme'] = $theme;
            echo json_encode(['success' => true, 'theme' => $theme]);
            exit;
        }
    }
}

// Sort posts by creation date (newest first)
usort($metadata, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get posts for homepage (first 6)
$posts = array_slice($metadata, 0, 6);
$recent_posts = array_slice($metadata, 0, 3);

// Add dummy posts if no posts exist
if (empty($metadata)) {
    $dummy_posts = [
        [
            'title' => 'Getting Started with PHP',
            'slug' => 'getting-started-php',
            'tags' => ['php', 'web', 'tutorial'],
            'thumbnail' => '',
            'created_at' => '2023-06-15 10:30:00',
            'views' => 42
        ],
        [
            'title' => 'Building Responsive Websites',
            'slug' => 'responsive-websites',
            'tags' => ['css', 'responsive', 'design'],
            'thumbnail' => '',
            'created_at' => '2023-06-12 14:20:00',
            'views' => 35
        ],
        [
            'title' => 'JavaScript Fundamentals',
            'slug' => 'javascript-fundamentals',
            'tags' => ['javascript', 'web', 'tutorial'],
            'thumbnail' => '',
            'created_at' => '2023-06-10 09:15:00',
            'views' => 58
        ],
        [
            'title' => 'Introduction to Databases',
            'slug' => 'introduction-databases',
            'tags' => ['database', 'sql', 'backend'],
            'thumbnail' => '',
            'created_at' => '2023-06-08 11:45:00',
            'views' => 27
        ],
        [
            'title' => 'Creating APIs with PHP',
            'slug' => 'creating-apis-php',
            'tags' => ['api', 'php', 'backend'],
            'thumbnail' => '',
            'created_at' => '2023-06-05 16:30:00',
            'views' => 39
        ]
    ];
    
    foreach ($dummy_posts as $post) {
        $post_dir = POSTS_DIR . '/' . $post['slug'];
        if (!is_dir($post_dir)) mkdir($post_dir, 0777, true);
        
        $post_file = $post_dir . '/index.php';
        $dummy_content = "<?php\n// Post: {$post['slug']}\n// Generated: " . date('Y-m-d H:i:s') . "\n?>\n\n<h1>{$post['title']}</h1><p>This is a sample post about {$post['title']}. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>";
        file_put_contents($post_file, $dummy_content);
        
        $metadata[$post['slug']] = $post;
    }
    
    file_put_contents(METADATA_FILE, json_encode($metadata, JSON_PRETTY_PRINT));
    
    // Reload metadata
    $metadata = json_decode(file_get_contents(METADATA_FILE), true) ?: [];
    usort($metadata, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $posts = array_slice($metadata, 0, 6);
    $recent_posts = array_slice($metadata, 0, 3);
}

// Check if admin is logged in
$is_admin = isset($_SESSION['admin']);

// Function to get uploaded images for a post
function getPostImages($slug) {
    $images_dir = POSTS_DIR . '/' . $slug . '/images';
    $images = [];
    
    if (is_dir($images_dir)) {
        $files = glob($images_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $images[] = $file;
            }
        }
    }
    
    return $images;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniB is a Minimalistic Static Blog CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/tailwind-dark.css">
    <style>
        /* Code block styles */
        .code-block {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .code-header {
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .code-block pre {
            margin: 0;
            padding: 1rem;
            background-color: #1f2937;
            color: #10b981;
            overflow-x: auto;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        
        .code-block pre code {
            background: none;
            padding: 0;
            color: inherit;
        }
        
        .js-code pre {
            color: #fbbf24;
        }
        
        .css-code pre {
            color: #3b82f6;
        }
        
        .php-code pre {
            color: #10b981;
        }
        
        .html-code pre {
            color: #f97316;
        }
        
        .embed-block {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .embed-header {
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .embed-content {
            padding: 1rem;
            background-color: #f9fafb;
        }
        
        .executable-code {
            border: 2px solid #6366f1;
            border-radius: 0.5rem;
            overflow: hidden;
            margin: 1rem 0;
            position: relative;
        }
        
        .executable-header {
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-bottom: 2px solid #6366f1;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }
        
        .executable-content {
            padding: 1rem;
            background-color: #f8fafc;
            border-left: 4px solid #6366f1;
        }
        
        .executable-code::before {
            content: "⚡";
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            font-size: 1rem;
            color: #fbbf24;
            z-index: 10;
        }
        
        /* Syntax highlighting for code blocks */
        .language-html .tag { color: #f97316; }
        .language-html .attr-name { color: #3b82f6; }
        .language-html .attr-value { color: #10b981; }
        
        .language-javascript .keyword { color: #8b5cf6; }
        .language-javascript .string { color: #10b981; }
        .language-javascript .number { color: #f59e0b; }
        .language-javascript .function { color: #3b82f6; }
        
        .language-css .selector { color: #f97316; }
        .language-css .property { color: #3b82f6; }
        .language-css .value { color: #10b981; }
        
        .language-php .keyword { color: #8b5cf6; }
        .language-php .string { color: #10b981; }
        .language-php .variable { color: #3b82f6; }
        .language-php .function { color: #f59e0b; }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="#" class="text-xl font-bold text-blue-600 flex items-center">
                        <i class="fas fa-blog mr-2"></i> MiniB
                    </a>
                </div>
                <div class="flex items-center">
                    <div class="relative w-64 mr-4">
                        <input type="text" id="searchInput" placeholder="Search posts..." class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                        <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                    </div>
                    <!-- Theme Toggle Button -->
                    <button id="themeToggle" class="theme-toggle mr-4" title="Toggle dark mode">
                        <i class="fas fa-sun sun-icon"></i>
                        <i class="fas fa-moon moon-icon" style="display: none;"></i>
                    </button>
                    <?php if($is_admin): ?>
                        <button id="newPostBtn" class="bg-green-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-600 transition mr-2">
                            <i class="fas fa-plus mr-2"></i>New Post
                        </button>
                        <form method="post">
                            <button name="logout" class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </button>
                        </form>
                    <?php else: ?>
                        <button id="loginBtn" class="bg-blue-600 text-white p-3 rounded-lg font-medium hover:bg-blue-700 transition">
                            <i class="fas fa-user"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome to MiniB</h1>
            <p class="text-xl mb-8 max-w-3xl mx-auto">SEO friendly minimalistic static blog CMS with advanced features</p>
            <div class="flex justify-center space-x-4">
                <a href="#posts" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">Explore Posts</a>
            </div>
        </div>
    </header>

    <!-- Admin Dashboard -->
    <?php if($is_admin): ?>
    <section id="dashboard" class="py-12 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Dashboard Header -->
                <div class="bg-blue-600 text-white p-6">
                    <h2 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-tachometer-alt mr-3"></i>Admin Dashboard
                    </h2>
                    <p class="opacity-80 mt-1">Manage your blog content and analytics</p>
                </div>
                
                <!-- Dashboard Content -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 p-6">
                    <!-- Stats Cards -->
                    <div class="dashboard-card bg-white rounded-lg shadow p-6 border-l-4 border-blue-600">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500">Total Posts</p>
                                <h3 id="totalPosts" class="text-3xl font-bold mt-2"><?= count($metadata) ?></h3>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500">Total Views</p>
                                <h3 id="totalViews" class="text-3xl font-bold mt-2">
                                    <?= array_sum(array_column($metadata, 'views')) ?>
                                </h3>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-eye text-green-500 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500">Popular Tag</p>
                                <h3 id="popularTag" class="text-3xl font-bold mt-2">
                                    <?php
                                    $tagCounts = [];
                                    foreach ($metadata as $post) {
                                        foreach ($post['tags'] as $tag) {
                                            $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                                        }
                                    }
                                    
                                    if (!empty($tagCounts)) {
                                        arsort($tagCounts);
                                        echo array_keys($tagCounts)[0];
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </h3>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-tag text-yellow-500 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500">Avg. Views</p>
                                <h3 id="avgViews" class="text-3xl font-bold mt-2">
                                    <?= count($metadata) > 0 ? round(array_sum(array_column($metadata, 'views')) / count($metadata)) : 0 ?>
                                </h3>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-chart-line text-purple-500 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Post Management -->
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Manage Posts</h3>
                    </div>
                    
                    <?php if(isset($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($success)): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                            <?= $success ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b">Title</th>
                                    <th class="py-2 px-4 border-b">Tags</th>
                                    <th class="py-2 px-4 border-b">Views</th>
                                    <th class="py-2 px-4 border-b">Date</th>
                                    <th class="py-2 px-4 border-b">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($metadata as $post): ?>
                                    <tr>
                                        <td class="py-3 px-4 border-b"><?= $post['title'] ?></td>
                                        <td class="py-3 px-4 border-b">
                                            <?php foreach($post['tags'] as $tag): ?>
                                                <span class="tag bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full mr-1"><?= $tag ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td class="py-3 px-4 border-b"><?= $post['views'] ?></td>
                                        <td class="py-3 px-4 border-b"><?= date('M d, Y', strtotime($post['created_at'])) ?></td>
                                        <td class="py-3 px-4 border-b">
                                            <button class="edit-post bg-blue-100 text-blue-600 px-3 py-1 rounded-lg hover:bg-blue-200" data-slug="<?= $post['slug'] ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="post" class="inline-block">
                                                <input type="hidden" name="delete_post" value="1">
                                                <input type="hidden" name="slug" value="<?= $post['slug'] ?>">
                                                <button type="submit" class="bg-red-100 text-red-600 px-3 py-1 rounded-lg hover:bg-red-200 ml-2">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Posts Carousel -->
    <section id="carousel" class="py-8 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Featured Posts</h2>
            <div id="postCarousel" class="flex overflow-x-auto pb-4 scrollbar-hide" style="scrollbar-width: none;">
                <?php foreach($recent_posts as $post): ?>
                    <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-4">
                        <div class="h-full cursor-pointer transition-transform hover:scale-105" data-slug="<?= $post['slug'] ?>" data-post-title="<?= htmlspecialchars($post['title']) ?>">
                            <div class="relative">
                                <?php if(!empty($post['thumbnail']) && file_exists($post['thumbnail'])): ?>
                                    <img src="<?= $post['thumbnail'] ?>" alt="<?= $post['title'] ?>" class="w-full h-48 object-cover rounded-t-xl">
                                <?php else: ?>
                                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-full h-48 flex items-center justify-center text-gray-400">
                                        <i class="fas fa-image fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-4 right-4 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded view-count">
                                    <?= $post['views'] ?> views
                                </div>
                            </div>
                            <div class="p-6 bg-white rounded-b-xl">
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <?php foreach($post['tags'] as $tag): ?>
                                        <a href="#" class="tag bg-gray-100 text-gray-800 text-xs px-3 py-1 rounded-full hover:bg-blue-100 hover:text-blue-800" onclick="event.stopPropagation(); filterByTag('<?= $tag ?>')"><?= $tag ?></a>
                                    <?php endforeach; ?>
                                </div>
                                <h3 class="text-xl font-bold mb-2"><?= $post['title'] ?></h3>
                                <?php 
                                $post_content = '';
                                if (file_exists(POSTS_DIR . '/' . $post['slug'] . '/index.php')) {
                                    $post_content = file_get_contents(POSTS_DIR . '/' . $post['slug'] . '/index.php');
                                    // Remove PHP tags and comments for excerpt
                                    $post_content = preg_replace('/<\?php.*?\?>\s*/s', '', $post_content);
                                }
                                $excerpt = getFirstParagraph($post_content);
                                ?>
                                <p class="text-gray-700 text-sm mb-4"><?= $excerpt ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-center mt-4 space-x-2">
                <button id="prevBtn" class="p-2 rounded-full bg-white shadow hover:bg-gray-100">
                    <i class="fas fa-chevron-left text-gray-600"></i>
                </button>
                <button id="nextBtn" class="p-2 rounded-full bg-white shadow hover:bg-gray-100">
                    <i class="fas fa-chevron-right text-gray-600"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- Blog Posts Section -->
    <section id="posts" class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Latest Posts</h2>
            <div id="postContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($posts as $post): ?>
                    <div class="post-card bg-white rounded-xl shadow-lg overflow-hidden cursor-pointer transition-transform hover:scale-105" data-title="<?= strtolower($post['title']) ?>" data-tags="<?= strtolower(implode(' ', $post['tags'])) ?>" data-slug="<?= $post['slug'] ?>" data-post-title="<?= htmlspecialchars($post['title']) ?>">
                        <div>
                            <div class="relative">
                                <?php if(!empty($post['thumbnail']) && file_exists($post['thumbnail'])): ?>
                                    <img src="<?= $post['thumbnail'] ?>" alt="<?= $post['title'] ?>" class="w-full h-48 object-cover">
                                <?php else: ?>
                                    <div class="bg-gray-200 border-2 border-dashed rounded-t-xl w-full h-48 flex items-center justify-center text-gray-400">
                                        <i class="fas fa-image fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-4 right-4 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded view-count">
                                    <?= $post['views'] ?> views
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <?php foreach($post['tags'] as $tag): ?>
                                        <span class="tag bg-gray-100 text-gray-800 text-xs px-3 py-1 rounded-full hover:bg-blue-100 hover:text-blue-800"><?= $tag ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <h3 class="text-xl font-bold mb-2"><?= $post['title'] ?></h3>
                                <?php 
                                $post_content = '';
                                if (file_exists(POSTS_DIR . '/' . $post['slug'] . '/index.php')) {
                                    $post_content = file_get_contents(POSTS_DIR . '/' . $post['slug'] . '/index.php');
                                    // Remove PHP tags and comments for excerpt
                                    $post_content = preg_replace('/<\?php.*?\?>\s*/s', '', $post_content);
                                }
                                $excerpt = getFirstParagraph($post_content);
                                ?>
                                <p class="text-gray-700 text-sm mb-4"><?= $excerpt ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="loader" class="text-center py-8 hidden">
                <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12 mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading more posts...</p>
            </div>
        </div>
    </section>

    <!-- Post Viewer Section -->
    <section id="postViewer" class="py-12 bg-gray-50 hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="border-b p-6 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800" id="viewerTitle">Post Title</h2>
                    <button id="closeViewer" class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 lg:p-8">
                    <div id="postViewerContent" class="post-content">
                        <!-- Post content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="border-b p-4 flex justify-between items-center">
                <h3 class="text-xl font-bold">Admin Login</h3>
                <button id="closeLogin" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="post" class="p-6">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Username</label>
                    <input type="text" name="username" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                </div>
                <button type="submit" name="login" class="w-full bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700">
                    Login
                </button>
            </form>
        </div>
    </div>

    <!-- Post Editor Modal -->
    <div id="editorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-7xl max-h-screen overflow-y-auto">
            <div class="border-b p-4 flex justify-between items-center">
                <h3 class="text-xl font-bold" id="editorTitle">New Post</h3>
                <button id="closeEditor" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="save_post" value="1">
                <input type="hidden" id="postSlug" name="slug" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 mb-2">Title</label>
                        <input type="text" id="postTitle" name="title" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Slug (URL)</label>
                        <input type="text" id="slugInput" name="slug" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                    </div>
                </div>
                
                <!-- Enhanced Thumbnail and Image Management -->
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">Thumbnail & Images</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Thumbnail Section -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-800 mb-3">Post Thumbnail</h4>
                            <div class="flex items-center">
                                <div id="thumbnailPreview" class="thumbnail-preview w-24 h-24 bg-gray-200 border-2 border-dashed rounded-lg mr-4 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                <div>
                                    <input type="file" id="thumbnailUpload" name="thumbnail" class="hidden" accept="image/*">
                                    <button type="button" id="uploadThumbnail" class="bg-blue-100 hover:bg-blue-200 px-4 py-2 rounded-lg mr-2 text-blue-700">
                                        <i class="fas fa-upload mr-2"></i>Upload Thumbnail
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Post Images Section -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-semibold text-gray-800">Post Images</h4>
                                <button type="button" id="refreshImages" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-sync-alt mr-1"></i>Refresh
                                </button>
                            </div>
                            <div id="postImagesContainer" class="space-y-3">
                                <div class="text-sm text-gray-600 mb-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Images uploaded in content will appear here. 
                                    <span class="text-blue-600">Click the star icon to use as thumbnail.</span>
                                </div>
                                <div id="postImagesList" class="grid grid-cols-2 gap-2">
                                    <!-- Post images will be displayed here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">Tags (comma separated, max 3)</label>
                    <input type="text" id="postTags" name="tags" placeholder="tag1, tag2, tag3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>
                
                <!-- Enhanced Content Editor with Code Insertion -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-gray-700">Content</label>
                        <div class="flex space-x-2">
                            <button type="button" id="insertHtml" class="bg-green-100 hover:bg-green-200 px-3 py-1 rounded text-green-700 text-sm">
                                <i class="fas fa-code mr-1"></i>HTML
                            </button>
                            <button type="button" id="insertJs" class="bg-yellow-100 hover:bg-yellow-200 px-3 py-1 rounded text-yellow-700 text-sm">
                                <i class="fab fa-js-square mr-1"></i>JavaScript
                            </button>
                            <button type="button" id="insertCss" class="bg-purple-100 hover:bg-purple-200 px-3 py-1 rounded text-purple-700 text-sm">
                                <i class="fab fa-css3-alt mr-1"></i>CSS
                            </button>
                            <button type="button" id="insertPhp" class="bg-blue-100 hover:bg-blue-200 px-3 py-1 rounded text-blue-700 text-sm">
                                <i class="fab fa-php mr-1"></i>PHP
                            </button>
                            <button type="button" id="insertEmbed" class="bg-red-100 hover:bg-red-200 px-3 py-1 rounded text-red-700 text-sm">
                                <i class="fas fa-link mr-1"></i>Embed
                            </button>
                            <button type="button" id="showGeneratedCode" class="bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded text-gray-700 text-sm">
                                <i class="fas fa-eye mr-1"></i>Show Generated Code
                            </button>
                        </div>
                    </div>
                    <div id="editor"></div>
                    <textarea id="postContent" name="content" style="display: none;"></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700">
                        Publish Post
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Code Insertion Modal -->
    <div id="codeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl">
            <div class="border-b p-4 flex justify-between items-center">
                <h3 class="text-xl font-bold" id="codeModalTitle">Insert Code</h3>
                <button id="closeCodeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Code Type</label>
                    <select id="codeType" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                        <option value="html">HTML</option>
                        <option value="javascript">JavaScript</option>
                        <option value="css">CSS</option>
                        <option value="php">PHP</option>
                        <option value="embed">Embed (iframe, video, etc.)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Code Content</label>
                    <textarea id="codeContent" class="w-full h-64 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 font-mono text-sm" placeholder="Paste your code here..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancelCode" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="button" id="insertCode" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Insert Code
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Generated Code Modal -->
    <div id="generatedCodeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl max-h-screen overflow-y-auto">
            <div class="border-b p-4 flex justify-between items-center">
                <h3 class="text-xl font-bold">Generated PHP Code</h3>
                <button id="closeGeneratedCodeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <p class="text-gray-600 mb-4">This is the PHP code that will be generated when you save the post:</p>
                    
                    <!-- Tab Navigation -->
                    <div class="flex border-b mb-4">
                        <button type="button" id="codeTab" class="px-4 py-2 border-b-2 border-blue-500 text-blue-600 font-semibold">
                            Generated Code
                        </button>
                        <button type="button" id="previewTab" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            Preview
                        </button>
                    </div>
                    
                    <!-- Code Tab Content -->
                    <div id="codeTabContent">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-gray-700 font-semibold">Generated Code:</label>
                            <div class="flex space-x-2">
                                <button type="button" id="editCode" class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700">
                                    <i class="fas fa-edit mr-1"></i>Edit Code
                                </button>
                                <button type="button" id="copyGeneratedCode" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                                    <i class="fas fa-copy mr-1"></i>Copy Code
                                </button>
                            </div>
                        </div>
                        <pre id="generatedCodeDisplay" class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm font-mono border"></pre>
                        <textarea id="editableGeneratedCode" class="w-full h-96 bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono border hidden" style="resize: vertical;"></textarea>
                        <div id="editControls" class="mt-4 hidden">
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Edit the code below. Clicking "Save" will update the editor and the post file.
                                </div>
                                <div class="flex space-x-2">
                                    <button type="button" id="cancelEdit" class="bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-400">
                                        Cancel
                                    </button>
                                    <button type="button" id="save" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                                        <i class="fas fa-save mr-1"></i>Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Tab Content -->
                    <div id="previewTabContent" class="hidden">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-gray-700 font-semibold">Post Preview:</label>
                            <span class="text-sm text-gray-500">How the post will look when viewed</span>
                        </div>
                        <div id="postPreview" class="bg-white border rounded-lg p-6 max-h-96 overflow-y-auto">
                            <!-- Preview content will be inserted here -->
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="closeGeneratedCode" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">MiniB</h3>
                    <p class="text-gray-400">Minimalistic static blog CMS</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Navigation</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="#posts" class="text-gray-400 hover:text-white">All Posts</a></li>
                        <?php if($is_admin): ?>
                        <li><a href="#dashboard" class="text-gray-400 hover:text-white">Admin Dashboard</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Resources</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Documentation</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">GitHub Repository</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Support</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Subscribe</h4>
                    <p class="text-gray-400 mb-2">Get the latest posts delivered to your inbox</p>
                    <div class="flex">
                        <input type="email" placeholder="Your email" class="px-4 py-2 bg-gray-700 text-white rounded-l-lg w-full focus:outline-none">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>© <script type="text/javascript">var year = new Date();document.write(year.getFullYear());</script> MiniB by <a href="https://bit.ly/colenikol" target="_blank"><u>ColeNikol</u></a>. If you like MiniB please <a href="https://cwallet.com/t/JZWJW87H" target="_blank"><b><u>support</u></b></a> my next project</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Initialize Quill editor
        let quill;
        
        // ========================================
        // THEME MANAGEMENT
        // ========================================
        
        // Theme management
        const themeToggle = document.getElementById('themeToggle');
        const sunIcon = document.querySelector('.sun-icon');
        const moonIcon = document.querySelector('.moon-icon');
        
        // Function to get current theme
        function getCurrentTheme() {
            return localStorage.getItem('theme') || 'light';
        }
        
        // Function to set theme
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            
            // Update toggle button appearance
            if (theme === 'dark') {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            } else {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            }
        }
        
        // Function to toggle theme
        function toggleTheme() {
            const currentTheme = getCurrentTheme();
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme);
        }
        
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = getCurrentTheme();
            setTheme(savedTheme);
            
            // Add click event to theme toggle
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }
        });
        
        // ========================================
        // EXISTING FUNCTIONALITY
        // ========================================
        
        // Carousel functionality
        const postCarousel = document.getElementById('postCarousel');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        
        if (postCarousel) {
            prevBtn.addEventListener('click', () => {
                postCarousel.scrollBy({ left: -postCarousel.offsetWidth * 0.8, behavior: 'smooth' });
            });
            
            nextBtn.addEventListener('click', () => {
                postCarousel.scrollBy({ left: postCarousel.offsetWidth * 0.8, behavior: 'smooth' });
            });
        }
        
        // Post Viewer Section
        const postViewer = document.getElementById('postViewer');
        const closeViewer = document.getElementById('closeViewer');
        const backToTopBtn = document.getElementById('backToTop');
        
        if (closeViewer) {
            closeViewer.addEventListener('click', () => {
                postViewer.classList.add('hidden');
                backToTopBtn.classList.remove('show');
                // Scroll back to top of page
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
        
        // Back to top button functionality
        if (backToTopBtn) {
            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
        
        // Function to open post in inline viewer
        function openPostInViewer(slug, title) {
            document.getElementById('viewerTitle').textContent = title;
            document.getElementById('postViewerContent').innerHTML = '<div class="text-center py-8"><div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12 mx-auto"></div><p class="mt-2 text-gray-600">Loading post...</p></div>';
            postViewer.classList.remove('hidden');
            backToTopBtn.classList.add('show');
            
            // Scroll to the post viewer section
            postViewer.scrollIntoView({ behavior: 'smooth' });
            
            // Increment visit counter
            const formData = new FormData();
            formData.append('increment_view', '1');
            formData.append('slug', slug);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update view count in the UI if needed
                    const viewElements = document.querySelectorAll(`[data-slug="${slug}"] .view-count`);
                    viewElements.forEach(el => {
                        el.textContent = data.views + ' views';
                    });
                }
            })
            .catch(error => {
                console.error('Error incrementing view count:', error);
            });
            
            // Load post content
            fetch(`posts/${slug}/index.php`)
                .then(response => response.text())
                .then(content => {
                    // Remove PHP tags and comments for display
                    content = content.replace(/<\?php[\s\S]*?\?>\s*/g, '');
                    
                    // Fix image paths in the content
                    // Handle both relative paths and absolute paths
                    let fixedContent = content;
                    
                    // Fix relative image paths (images/filename)
                    fixedContent = fixedContent.replace(/src="images\//g, `src="posts/${slug}/images/`);
                    
                    // Fix absolute paths that might be stored incorrectly
                    fixedContent = fixedContent.replace(new RegExp(`src="posts/${slug}/[^"]*"`, 'g'), function(match) {
                        // If the path doesn't already point to the images directory, fix it
                        if (!match.includes('/images/')) {
                            const filename = match.match(/posts\/[^\/]+\/([^"]+)/);
                            if (filename && filename[1]) {
                                return `src="posts/${slug}/images/${filename[1]}"`;
                            }
                        }
                        return match;
                    });
                    
                    document.getElementById('postViewerContent').innerHTML = fixedContent;
                })
                .catch(error => {
                    console.error('Error loading post:', error);
                    document.getElementById('postViewerContent').innerHTML = '<div class="text-center py-8 text-red-600">Error loading post content.</div>';
                });
        }
        
        // Login Modal
        const loginModal = document.getElementById('loginModal');
        const loginBtn = document.getElementById('loginBtn');
        const closeLogin = document.getElementById('closeLogin');
        
        if (loginBtn) {
            loginBtn.addEventListener('click', () => {
                loginModal.classList.remove('hidden');
            });
        }
        
        if (closeLogin) {
            closeLogin.addEventListener('click', () => {
                loginModal.classList.add('hidden');
            });
        }
        
        // Editor Modal
        const editorModal = document.getElementById('editorModal');
        const newPostBtn = document.getElementById('newPostBtn');
        const closeEditor = document.getElementById('closeEditor');
        const editButtons = document.querySelectorAll('.edit-post');
        
        if (newPostBtn) {
            newPostBtn.addEventListener('click', () => {
                document.getElementById('editorTitle').textContent = 'New Post';
                document.getElementById('postTitle').value = '';
                document.getElementById('slugInput').value = '';
                document.getElementById('postSlug').value = '';
                document.getElementById('postTags').value = '';
                
                // Initialize Quill editor
                quill = initializeQuillEditor();
                quill.setContents([]);
                
                document.getElementById('thumbnailPreview').innerHTML = '<i class="fas fa-image text-gray-400"></i>';
                document.getElementById('thumbnailPreview').style.backgroundImage = '';
                updatePostImagesDisplay('');
                editorModal.classList.remove('hidden');
            });
        }
        
        if (closeEditor) {
            closeEditor.addEventListener('click', () => {
                editorModal.classList.add('hidden');
            });
        }
        
        // Edit post buttons
        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                const slug = button.getAttribute('data-slug');
                <?php foreach($metadata as $post): ?>
                    if (slug === '<?= $post['slug'] ?>') {
                        document.getElementById('editorTitle').textContent = 'Edit Post';
                        document.getElementById('postTitle').value = '<?= addslashes($post['title']) ?>';
                        document.getElementById('slugInput').value = '<?= $post['slug'] ?>';
                        document.getElementById('postSlug').value = '<?= $post['slug'] ?>';
                        document.getElementById('postTags').value = '<?= implode(', ', $post['tags']) ?>';
                        
                        // Load post content via AJAX
                        fetch(`posts/<?= $post['slug'] ?>/index.php`)
                            .then(response => response.text())
                            .then(content => {
                                // Remove PHP tags and comments for editor
                                content = content.replace(/<\?php[\s\S]*?\?>\s*/g, '');
                                
                                // Fix image paths for editor display
                                let fixedContent = content;
                                
                                // Fix relative image paths (images/filename) to absolute paths for editor
                                fixedContent = fixedContent.replace(/src="images\//g, `src="posts/<?= $post['slug'] ?>/images/`);
                                
                                // Fix absolute paths that might be stored incorrectly
                                fixedContent = fixedContent.replace(new RegExp(`src="posts/<?= $post['slug'] ?>/[^"]*"`, 'g'), function(match) {
                                    // If the path doesn't already point to the images directory, fix it
                                    if (!match.includes('/images/')) {
                                        const filename = match.match(/posts\/[^\/]+\/([^"]+)/);
                                        if (filename && filename[1]) {
                                            return `src="posts/<?= $post['slug'] ?>/images/${filename[1]}"`;
                                        }
                                    }
                                    return match;
                                });
                                
                                if (quill) {
                                    quill.root.innerHTML = fixedContent;
                                } else {
                                    quill = initializeQuillEditor();
                                    quill.root.innerHTML = fixedContent;
                                }
                                
                                // Update post images display
                                updatePostImagesDisplay('<?= $post['slug'] ?>');
                            });
                        
                        <?php if(!empty($post['thumbnail']) && file_exists($post['thumbnail'])): ?>
                            document.getElementById('thumbnailPreview').innerHTML = '';
                            document.getElementById('thumbnailPreview').style.backgroundImage = "url('<?= $post['thumbnail'] ?>')";
                            document.getElementById('thumbnailPreview').classList.add('thumbnail-preview');
                        <?php else: ?>
                            document.getElementById('thumbnailPreview').innerHTML = '<i class="fas fa-image text-gray-400"></i>';
                            document.getElementById('thumbnailPreview').style.backgroundImage = '';
                        <?php endif; ?>
                        
                        editorModal.classList.remove('hidden');
                    }
                <?php endforeach; ?>
            });
        });
        
        // Thumbnail upload
        const uploadThumbnail = document.getElementById('uploadThumbnail');
        const thumbnailUpload = document.getElementById('thumbnailUpload');
        const thumbnailPreview = document.getElementById('thumbnailPreview');
        
        if (uploadThumbnail) {
            uploadThumbnail.addEventListener('click', () => {
                thumbnailUpload.click();
            });
        }
        
        if (thumbnailUpload) {
            thumbnailUpload.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        thumbnailPreview.innerHTML = '';
                        thumbnailPreview.style.backgroundImage = `url('${event.target.result}')`;
                        thumbnailPreview.classList.add('thumbnail-preview');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        
        // Slug generator
        const titleInput = document.getElementById('postTitle');
        const slugInput = document.getElementById('slugInput');
        
        if (titleInput && slugInput) {
            titleInput.addEventListener('input', function() {
                if (!slugInput.value) {
                    const slug = this.value.toLowerCase()
                        .replace(/[^\w\s]/g, '')
                        .replace(/\s+/g, '-')
                        .substring(0, 50);
                    slugInput.value = slug;
                }
            });
        }
        
        // Tag filtering functionality
        function filterByTag(tag) {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = tag;
                searchInput.dispatchEvent(new Event('input'));
                // Scroll to posts section
                const postsSection = document.getElementById('posts');
                if (postsSection) {
                    postsSection.scrollIntoView({ behavior: 'smooth' });
                }
            }
        }
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const postCards = document.querySelectorAll('.post-card');
                
                postCards.forEach(card => {
                    const title = card.getAttribute('data-title') || '';
                    const tags = card.getAttribute('data-tags') || '';
                    
                    if (title.includes(searchTerm) || tags.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
                // Scroll to posts section
                const postsSection = document.getElementById('posts');
                if (postsSection) {
                    postsSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }
        
        // Form submission - sync Quill content and refresh images
        const editorForm = document.querySelector('#editorModal form');
        if (editorForm) {
            editorForm.addEventListener('submit', function(e) {
                if (quill) {
                    document.getElementById('postContent').value = quill.root.innerHTML;
                }
                
                // Get the current slug for refreshing images after save
                const currentSlug = document.getElementById('postSlug').value || document.getElementById('slugInput').value;
                
                // Store the slug for later use
                this.dataset.currentSlug = currentSlug;
            });
        }
        
        // Listen for successful form submission and refresh images
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's a success message (indicating successful save)
            const successMessage = document.querySelector('.bg-green-100');
            if (successMessage && successMessage.textContent.includes('saved successfully')) {
                // Get the current slug from the editor form
                const editorForm = document.querySelector('#editorModal form');
                if (editorForm && editorForm.dataset.currentSlug) {
                    // Refresh images display after a short delay
                    setTimeout(() => {
                        updatePostImagesDisplay(editorForm.dataset.currentSlug);
                    }, 500);
                }
            }
        });
        
        // Infinite scroll
        let isLoading = false;
        let page = 1;
        const postsPerPage = 6;
        const loader = document.getElementById('loader');
        const postContainer = document.getElementById('postContainer');
        
        // Get total posts count from PHP
        const totalPosts = <?= count($metadata) ?>;
        
        // Function to load more posts
        function loadMorePosts() {
            if (isLoading) return;
            
            // Show loader
            loader.classList.remove('hidden');
            isLoading = true;
            
            // Calculate how many posts we have left to load
            const loadedCount = page * postsPerPage;
            const remainingPosts = totalPosts - loadedCount;
            const postsToLoad = Math.min(postsPerPage, remainingPosts);
            
            if (postsToLoad > 0) {
                // Fetch next set of posts
                fetch(`?page=${page}`)
                    .then(response => response.text())
                    .then(html => {
                        // Create a temporary container
                        const tempContainer = document.createElement('div');
                        tempContainer.innerHTML = html;
                        
                        // Extract the posts
                        const newPosts = tempContainer.querySelectorAll('.post-card');
                        
                        // Append new posts to the container
                        newPosts.forEach(post => {
                            postContainer.appendChild(post);
                        });
                        
                        page++;
                    })
                    .catch(error => {
                        console.error('Error loading posts:', error);
                    })
                    .finally(() => {
                        // Hide loader
                        loader.classList.add('hidden');
                        isLoading = false;
                    });
            } else {
                // No more posts to load
                loader.classList.add('hidden');
                isLoading = false;
            }
        }
        
        // Listen for scroll events
        window.addEventListener('scroll', () => {
            const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
            
            // Check if we've reached the bottom of the page
            if (scrollTop + clientHeight >= scrollHeight - 100) {
                loadMorePosts();
            }
        });
        
        // Add click handlers to all post cards (including carousel items)
        document.addEventListener('click', function(e) {
            // Check if clicked element is a post card or carousel item
            let postCard = e.target.closest('.post-card, .carousel-item > div');
            if (postCard) {
                e.preventDefault();
                e.stopPropagation();
                
                // Get post data from data attributes
                const slug = postCard.getAttribute('data-slug');
                const title = postCard.getAttribute('data-post-title') || postCard.querySelector('h3')?.textContent || 'Post Title';
                
                if (slug) {
                    openPostInViewer(slug, title);
                }
            }
        });
        
        // Code Insertion Modal
        const codeModal = document.getElementById('codeModal');
        const closeCodeModal = document.getElementById('closeCodeModal');
        const cancelCode = document.getElementById('cancelCode');
        const insertCode = document.getElementById('insertCode');
        const codeType = document.getElementById('codeType');
        const codeContent = document.getElementById('codeContent');
        
        // Code insertion buttons
        const insertHtml = document.getElementById('insertHtml');
        const insertJs = document.getElementById('insertJs');
        const insertCss = document.getElementById('insertCss');
        const insertPhp = document.getElementById('insertPhp');
        const insertEmbed = document.getElementById('insertEmbed');
        const showGeneratedCode = document.getElementById('showGeneratedCode');
        
        function openCodeModal(type) {
            codeType.value = type;
            codeContent.value = '';
            codeModal.classList.remove('hidden');
        }
        
        if (insertHtml) insertHtml.addEventListener('click', () => openCodeModal('html'));
        if (insertJs) insertJs.addEventListener('click', () => openCodeModal('javascript'));
        if (insertCss) insertCss.addEventListener('click', () => openCodeModal('css'));
        if (insertPhp) insertPhp.addEventListener('click', () => openCodeModal('php'));
        if (insertEmbed) insertEmbed.addEventListener('click', () => openCodeModal('embed'));
        
        // Show Generated Code functionality
        if (showGeneratedCode) {
            showGeneratedCode.addEventListener('click', () => {
                if (quill) {
                    const content = quill.root.innerHTML;
                    const slug = document.getElementById('slugInput').value || 'your-post-slug';
                    
                    // Process content similar to how it would be processed when saving
                    let processedContent = content;
                    
                    // Fix image paths for display (convert absolute paths to relative)
                    processedContent = processedContent.replace(/src="posts\/[^\/]+\/images\//g, 'src="images/');
                    
                    // Generate the PHP code that would be created
                    const generatedCode = `<?php
// Post: ${slug}
// Generated: ${new Date().toISOString().slice(0, 19).replace('T', ' ')}
?>

${processedContent}`;
                    
                    // Display the generated code with syntax highlighting
                    const codeDisplay = document.getElementById('generatedCodeDisplay');
                    codeDisplay.textContent = generatedCode;
                    
                    // Add syntax highlighting classes
                    codeDisplay.className = 'bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm font-mono border language-php';
                    
                    // Update preview content
                    document.getElementById('postPreview').innerHTML = content;
                    
                    document.getElementById('generatedCodeModal').classList.remove('hidden');
                } else {
                    alert('Please initialize the editor first.');
                }
            });
        }
        
        if (closeCodeModal) {
            closeCodeModal.addEventListener('click', () => {
                codeModal.classList.add('hidden');
            });
        }
        
        if (cancelCode) {
            cancelCode.addEventListener('click', () => {
                codeModal.classList.add('hidden');
            });
        }
        
        if (insertCode) {
            insertCode.addEventListener('click', () => {
                const type = codeType.value;
                const content = codeContent.value.trim();
                
                if (content) {
                    let codeBlock = '';
                    
                    // Escape HTML entities in the code content
                    const escapedContent = content
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                    
                    switch (type) {
                        case 'html':
                            codeBlock = `<div class="code-block html-code my-4">
                                <div class="code-header bg-orange-100 text-orange-800 px-4 py-2 font-semibold border-b">HTML Code</div>
                                <pre class="bg-gray-900 text-green-400 p-4 overflow-x-auto"><code class="language-html">${escapedContent}</code></pre>
                            </div>`;
                            break;
                        case 'javascript':
                            codeBlock = `<div class="code-block js-code my-4">
                                <div class="code-header bg-yellow-100 text-yellow-800 px-4 py-2 font-semibold border-b">JavaScript Code</div>
                                <pre class="bg-gray-900 text-yellow-400 p-4 overflow-x-auto"><code class="language-javascript">${escapedContent}</code></pre>
                            </div>`;
                            break;
                        case 'css':
                            codeBlock = `<div class="code-block css-code my-4">
                                <div class="code-header bg-purple-100 text-purple-800 px-4 py-2 font-semibold border-b">CSS Code</div>
                                <pre class="bg-gray-900 text-blue-400 p-4 overflow-x-auto"><code class="language-css">${escapedContent}</code></pre>
                            </div>`;
                            break;
                        case 'php':
                            codeBlock = `<div class="code-block php-code my-4">
                                <div class="code-header bg-blue-100 text-blue-800 px-4 py-2 font-semibold border-b">PHP Code</div>
                                <pre class="bg-gray-900 text-green-400 p-4 overflow-x-auto"><code class="language-php">${escapedContent}</code></pre>
                            </div>`;
                            break;
                        case 'embed':
                            codeBlock = `<div class="embed-block my-4">
                                <div class="embed-header bg-red-100 text-red-800 px-4 py-2 font-semibold border-b">Embedded Content</div>
                                <div class="embed-content p-4 bg-gray-50 border">${content}</div>
                            </div>`;
                            break;
                    }
                    
                    // Insert the code block at cursor position in Quill editor
                    if (quill) {
                        const range = quill.getSelection();
                        if (range) {
                            // Insert a newline first if not at the beginning
                            if (range.index > 0) {
                                quill.insertText(range.index, '\n');
                                range.index++;
                            }
                            // Insert the HTML content
                            quill.clipboard.dangerouslyPasteHTML(range.index, codeBlock);
                            // Move cursor after the inserted content
                            quill.setSelection(range.index + 1);
                        } else {
                            // If no selection, insert at the end
                            quill.clipboard.dangerouslyPasteHTML(quill.getLength(), '\n' + codeBlock);
                        }
                    }
                    
                    codeModal.classList.add('hidden');
                }
            });
        }
        
        // Enhanced Quill editor with custom code blocks and image handling
        function initializeQuillEditor() {
            if (quill) {
                return quill;
            }
            
            // Custom image handler
            function imageHandler() {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.click();
                
                input.onchange = () => {
                    const file = input.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = () => {
                            const range = quill.getSelection();
                            if (range) {
                                // Create image element with custom attributes
                                const img = document.createElement('img');
                                img.src = reader.result;
                                img.style.maxWidth = '100%';
                                img.style.height = 'auto';
                                
                                // Insert image and get its index
                                quill.insertEmbed(range.index, 'image', reader.result);
                                
                                // Show image options modal
                                showImageOptionsModal(range.index, img);
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                };
            }
            
            // Custom link handler
            function linkHandler() {
                const range = quill.getSelection();
                if (range) {
                    const text = quill.getText(range.index, range.length);
                    showLinkOptionsModal(text, range);
                }
            }
            
            quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'align': [] }],
                            ['link', 'image', 'video'],
                            ['clean']
                        ],
                        handlers: {
                            image: imageHandler,
                            link: linkHandler
                        }
                    }
                },
                placeholder: 'Write your post content here...'
            });
            
            // Add image click handlers after editor is initialized
            setTimeout(() => {
                addImageClickHandlers();
                addLinkClickHandlers();
            }, 100);
            
            return quill;
        }
        
        // Image options modal functionality
        function showImageOptionsModal(imageIndex, imgElement) {
            // Create modal HTML
            const modalHTML = `
                <div id="imageOptionsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl">
                        <div class="border-b p-4 flex justify-between items-center">
                            <h3 class="text-xl font-bold">Image Options</h3>
                            <button id="closeImageModal" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-gray-700 mb-2">Alt Text</label>
                                    <input type="text" id="imageAlt" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" placeholder="Describe the image for accessibility">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Description</label>
                                    <input type="text" id="imageDesc" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" placeholder="Optional image description">
                                </div>
                            </div>
                            <div class="mt-6">
                                <label class="block text-gray-700 mb-2">Alignment</label>
                                <div class="flex space-x-4">
                                    <button type="button" class="image-align-btn px-4 py-2 border rounded-lg hover:bg-gray-100" data-align="left">
                                        <i class="fas fa-align-left mr-2"></i>Left
                                    </button>
                                    <button type="button" class="image-align-btn px-4 py-2 border rounded-lg hover:bg-gray-100" data-align="center">
                                        <i class="fas fa-align-center mr-2"></i>Center
                                    </button>
                                    <button type="button" class="image-align-btn px-4 py-2 border rounded-lg hover:bg-gray-100" data-align="right">
                                        <i class="fas fa-align-right mr-2"></i>Right
                                    </button>
                                </div>
                            </div>
                            <div class="mt-6">
                                <label class="block text-gray-700 mb-2">Size</label>
                                <div class="flex items-center space-x-4">
                                    <input type="range" id="imageSize" min="25" max="100" value="100" class="flex-1">
                                    <span id="sizeValue" class="text-sm text-gray-600 w-12">100%</span>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end space-x-3">
                                <button type="button" id="cancelImageOptions" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                                    Cancel
                                </button>
                                <button type="button" id="applyImageOptions" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            const modal = document.getElementById('imageOptionsModal');
            const closeBtn = document.getElementById('closeImageModal');
            const cancelBtn = document.getElementById('cancelImageOptions');
            const applyBtn = document.getElementById('applyImageOptions');
            const sizeSlider = document.getElementById('imageSize');
            const sizeValue = document.getElementById('sizeValue');
            const alignBtns = document.querySelectorAll('.image-align-btn');
            const altInput = document.getElementById('imageAlt');
            const descInput = document.getElementById('imageDesc');
            
            let selectedAlignment = 'left';
            let selectedSize = 100;
            
            // Pre-fill existing values if editing
            if (imgElement) {
                altInput.value = imgElement.alt || '';
                descInput.value = imgElement.getAttribute('data-description') || '';
                
                // Detect current alignment
                if (imgElement.style.float === 'right') {
                    selectedAlignment = 'right';
                    alignBtns[2].classList.add('bg-blue-100', 'border-blue-500');
                } else if (imgElement.style.margin === '0 auto' || imgElement.style.textAlign === 'center') {
                    selectedAlignment = 'center';
                    alignBtns[1].classList.add('bg-blue-100', 'border-blue-500');
                } else {
                    selectedAlignment = 'left';
                    alignBtns[0].classList.add('bg-blue-100', 'border-blue-500');
                }
                
                // Detect current size
                const currentSize = parseInt(imgElement.style.maxWidth) || 100;
                selectedSize = currentSize;
                sizeSlider.value = currentSize;
                sizeValue.textContent = currentSize + '%';
            }
            
            // Size slider handler
            sizeSlider.addEventListener('input', function() {
                selectedSize = this.value;
                sizeValue.textContent = selectedSize + '%';
            });
            
            // Alignment button handlers
            alignBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    alignBtns.forEach(b => b.classList.remove('bg-blue-100', 'border-blue-500'));
                    this.classList.add('bg-blue-100', 'border-blue-500');
                    selectedAlignment = this.dataset.align;
                });
            });
            
            // Close modal handlers
            function closeModal() {
                modal.remove();
            }
            
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            
            // Apply options handler
            applyBtn.addEventListener('click', function() {
                const altText = altInput.value;
                const description = descInput.value;
                
                if (imgElement) {
                    // Update existing image
                    imgElement.alt = altText;
                    imgElement.style.maxWidth = selectedSize + '%';
                    imgElement.style.height = 'auto';
                    
                    // Apply alignment
                    imgElement.style.float = '';
                    imgElement.style.display = '';
                    imgElement.style.margin = '';
                    
                    if (selectedAlignment === 'center') {
                        imgElement.style.display = 'block';
                        imgElement.style.margin = '0 auto';
                    } else if (selectedAlignment === 'right') {
                        imgElement.style.float = 'right';
                        imgElement.style.margin = '0 0 1rem 1rem';
                    } else {
                        imgElement.style.float = 'left';
                        imgElement.style.margin = '0 1rem 1rem 0';
                    }
                    
                    // Handle description
                    let nextElement = imgElement.nextElementSibling;
                    if (description) {
                        imgElement.setAttribute('data-description', description);
                        if (nextElement && nextElement.classList.contains('image-description')) {
                            nextElement.textContent = description;
                            nextElement.style.textAlign = selectedAlignment;
                        } else {
                            const descElement = document.createElement('p');
                            descElement.className = 'image-description';
                            descElement.textContent = description;
                            descElement.style.textAlign = selectedAlignment;
                            imgElement.parentNode.insertBefore(descElement, imgElement.nextSibling);
                        }
                    } else {
                        imgElement.removeAttribute('data-description');
                        if (nextElement && nextElement.classList.contains('image-description')) {
                            nextElement.remove();
                        }
                    }
                } else {
                    // Handle new image insertion
                    const delta = quill.getContents();
                    const imageOp = delta.ops[imageIndex];
                    
                    if (imageOp && imageOp.insert && imageOp.insert.image) {
                        // Create new image element with attributes
                        const img = document.createElement('img');
                        img.src = imageOp.insert.image;
                        img.alt = altText;
                        img.style.maxWidth = selectedSize + '%';
                        img.style.height = 'auto';
                        
                        // Apply alignment
                        if (selectedAlignment === 'center') {
                            img.style.display = 'block';
                            img.style.margin = '0 auto';
                        } else if (selectedAlignment === 'right') {
                            img.style.float = 'right';
                            img.style.margin = '0 0 1rem 1rem';
                        } else {
                            img.style.float = 'left';
                            img.style.margin = '0 1rem 1rem 0';
                        }
                        
                        // Add description if provided
                        let htmlContent = img.outerHTML;
                        if (description) {
                            htmlContent += `<p class="text-sm text-gray-600 mt-2 image-description" style="text-align: ${selectedAlignment};">${description}</p>`;
                        }
                        
                        // Replace the image in Quill
                        quill.deleteText(imageIndex, 1);
                        quill.clipboard.dangerouslyPasteHTML(imageIndex, htmlContent);
                    }
                }
                
                closeModal();
            });
        }
        
        // Add click handler for existing images in editor
        function addImageClickHandlers() {
            if (quill) {
                const editor = quill.root;
                editor.addEventListener('click', function(e) {
                    if (e.target.tagName === 'IMG') {
                        e.preventDefault();
                        e.stopPropagation();
                        showImageOptionsModal(null, e.target);
                    }
                });
            }
        }
        
        // Function to update post images display
        function updatePostImagesDisplay(slug) {
            const postImagesList = document.getElementById('postImagesList');
            if (!postImagesList) return;
            
            // Clear current display
            postImagesList.innerHTML = '';
            
            if (slug) {
                // Fetch images for this post using a PHP endpoint
                fetch(`get_post_images.php?slug=${encodeURIComponent(slug)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.images.length > 0) {
                            data.images.forEach(image => {
                                const imgDiv = document.createElement('div');
                                imgDiv.className = 'relative group cursor-pointer';
                                imgDiv.innerHTML = `
                                    <img src="posts/${slug}/images/${image}" alt="${image}" class="w-full h-20 object-cover rounded border">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2">
                                        <button type="button" class="text-white text-xs bg-blue-500 hover:bg-blue-600 px-2 py-1 rounded" onclick="event.stopPropagation(); selectAsThumbnail('${slug}', '${image}')" title="Use as thumbnail">
                                            <i class="fas fa-star"></i>
                                        </button>
                                        <button type="button" class="text-white text-xs bg-red-500 hover:bg-red-600 px-2 py-1 rounded" onclick="event.stopPropagation(); deleteImage('${slug}', '${image}')" title="Delete image">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                `;
                                postImagesList.appendChild(imgDiv);
                            });
                        } else {
                            postImagesList.innerHTML = '<div class="text-gray-500 text-sm">No images found</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching images:', error);
                        postImagesList.innerHTML = '<div class="text-gray-500 text-sm">No images found</div>';
                    });
            } else {
                postImagesList.innerHTML = '<div class="text-gray-500 text-sm">Save post first to see images</div>';
            }
        }
        
        // Function to delete image
        window.deleteImage = function(slug, filename) {
            if (confirm('Are you sure you want to delete this image?')) {
                fetch(`delete_image.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        slug: slug,
                        filename: filename
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updatePostImagesDisplay(slug);
                    } else {
                        alert('Error deleting image: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting image');
                });
            }
        };
        
        // Function to select image as thumbnail
        window.selectAsThumbnail = function(slug, filename) {
            const thumbnailPreview = document.getElementById('thumbnailPreview');
            if (thumbnailPreview) {
                thumbnailPreview.innerHTML = '';
                thumbnailPreview.style.backgroundImage = `url('posts/${slug}/images/${filename}')`;
                thumbnailPreview.classList.add('thumbnail-preview');
                
                // Add a hidden input to store the selected thumbnail path
                let hiddenInput = document.getElementById('selected_thumbnail');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'selected_thumbnail';
                    hiddenInput.id = 'selected_thumbnail';
                    document.querySelector('#editorModal form').appendChild(hiddenInput);
                }
                hiddenInput.value = `posts/${slug}/images/${filename}`;
                
                // Show success message
                const successMsg = document.createElement('div');
                successMsg.className = 'absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded';
                successMsg.textContent = 'Thumbnail set!';
                thumbnailPreview.appendChild(successMsg);
                
                setTimeout(() => {
                    successMsg.remove();
                }, 2000);
            }
        };
        
        // Refresh images button
        const refreshImagesBtn = document.getElementById('refreshImages');
        if (refreshImagesBtn) {
            refreshImagesBtn.addEventListener('click', function() {
                const currentSlug = document.getElementById('postSlug').value || document.getElementById('slugInput').value;
                if (currentSlug) {
                    updatePostImagesDisplay(currentSlug);
                    // Show a brief loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Refreshing...';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-sync-alt mr-1"></i>Refresh';
                    }, 1000);
                } else {
                    alert('Please save the post first to see images.');
                }
            });
        }
        
        // Initialize image click handlers when editor is created
        function initializeQuillEditor() {
            if (quill) {
                return quill;
            }
            
            // Custom image handler
            function imageHandler() {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.click();
                
                input.onchange = () => {
                    const file = input.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = () => {
                            const range = quill.getSelection();
                            if (range) {
                                // Create image element with custom attributes
                                const img = document.createElement('img');
                                img.src = reader.result;
                                img.style.maxWidth = '100%';
                                img.style.height = 'auto';
                                
                                // Insert image and get its index
                                quill.insertEmbed(range.index, 'image', reader.result);
                                
                                // Show image options modal
                                showImageOptionsModal(range.index, img);
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                };
            }
            
            // Custom link handler
            function linkHandler() {
                const range = quill.getSelection();
                if (range) {
                    const text = quill.getText(range.index, range.length);
                    showLinkOptionsModal(text, range);
                }
            }
            
            quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'align': [] }],
                            ['link', 'image', 'video'],
                            ['clean']
                        ],
                        handlers: {
                            image: imageHandler,
                            link: linkHandler
                        }
                    }
                },
                placeholder: 'Write your post content here...'
            });
            
            // Add image click handlers after editor is initialized
            setTimeout(() => {
                addImageClickHandlers();
                addLinkClickHandlers();
            }, 100);
            
            return quill;
        }
        
        // Link options modal functionality
        function showLinkOptionsModal(selectedText, range, existingLink = null) {
            // Create modal HTML
            const modalHTML = `
                <div id="linkOptionsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                        <div class="border-b p-4 flex justify-between items-center">
                            <h3 class="text-xl font-bold">Link Options</h3>
                            <button id="closeLinkModal" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Link Text</label>
                                <input type="text" id="linkText" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" value="${selectedText || ''}" placeholder="Link text">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">URL</label>
                                <input type="url" id="linkUrl" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" placeholder="https://example.com">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Open Link</label>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="linkTarget" value="_self" class="mr-2" checked>
                                        <span>Same window</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="linkTarget" value="_blank" class="mr-2">
                                        <span>New tab</span>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">REL Attributes</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" id="relNoopener" class="mr-2">
                                        <span class="text-sm">noopener (security)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" id="relNoreferrer" class="mr-2">
                                        <span class="text-sm">noreferrer (privacy)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" id="relNofollow" class="mr-2">
                                        <span class="text-sm">nofollow (SEO)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" id="relSponsored" class="mr-2">
                                        <span class="text-sm">sponsored (advertising)</span>
                                    </label>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" id="cancelLinkOptions" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                                    Cancel
                                </button>
                                <button type="button" id="applyLinkOptions" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            const modal = document.getElementById('linkOptionsModal');
            const closeBtn = document.getElementById('closeLinkModal');
            const cancelBtn = document.getElementById('cancelLinkOptions');
            const applyBtn = document.getElementById('applyLinkOptions');
            const linkText = document.getElementById('linkText');
            const linkUrl = document.getElementById('linkUrl');
            
            // Pre-fill existing values if editing
            if (existingLink) {
                linkText.value = existingLink.textContent || '';
                linkUrl.value = existingLink.getAttribute('href') || '';
                
                const target = existingLink.getAttribute('target') || '_self';
                document.querySelector(`input[name="linkTarget"][value="${target}"]`).checked = true;
                
                const rel = existingLink.getAttribute('rel') || '';
                if (rel.includes('noopener')) document.getElementById('relNoopener').checked = true;
                if (rel.includes('noreferrer')) document.getElementById('relNoreferrer').checked = true;
                if (rel.includes('nofollow')) document.getElementById('relNofollow').checked = true;
                if (rel.includes('sponsored')) document.getElementById('relSponsored').checked = true;
            }
            
            // Close modal handlers
            function closeModal() {
                modal.remove();
            }
            
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            
            // Apply link options handler
            applyBtn.addEventListener('click', function() {
                const text = linkText.value.trim();
                const url = linkUrl.value.trim();
                const target = document.querySelector('input[name="linkTarget"]:checked').value;
                
                if (url) {
                    // Build REL attributes
                    const relAttributes = [];
                    if (document.getElementById('relNoopener').checked) relAttributes.push('noopener');
                    if (document.getElementById('relNoreferrer').checked) relAttributes.push('noreferrer');
                    if (document.getElementById('relNofollow').checked) relAttributes.push('nofollow');
                    if (document.getElementById('relSponsored').checked) relAttributes.push('sponsored');
                    
                    const rel = relAttributes.length > 0 ? relAttributes.join(' ') : '';
                    
                    // Create link HTML
                    let linkHTML = `<a href="${url}"`;
                    if (target === '_blank') linkHTML += ' target="_blank"';
                    if (rel) linkHTML += ` rel="${rel}"`;
                    linkHTML += `>${text || url}</a>`;
                    
                    if (existingLink) {
                        // Update existing link
                        existingLink.href = url;
                        existingLink.textContent = text || url;
                        existingLink.target = target === '_blank' ? '_blank' : '';
                        existingLink.rel = rel;
                    } else {
                        // Insert new link
                        if (range.length > 0) {
                            quill.deleteText(range.index, range.length);
                        }
                        quill.clipboard.dangerouslyPasteHTML(range.index, linkHTML);
                    }
                }
                
                closeModal();
            });
        }
        
        // Add click handler for existing links in editor
        function addLinkClickHandlers() {
            if (quill) {
                const editor = quill.root;
                editor.addEventListener('click', function(e) {
                    if (e.target.tagName === 'A') {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Get the range of the link
                        const range = quill.getSelection();
                        if (range) {
                            showLinkOptionsModal(e.target.textContent, range, e.target);
                        }
                    }
                });
            }
        }
        
        // Generated Code Modal handlers
        const generatedCodeModal = document.getElementById('generatedCodeModal');
        const closeGeneratedCodeModal = document.getElementById('closeGeneratedCodeModal');
        const closeGeneratedCode = document.getElementById('closeGeneratedCode');
        const copyGeneratedCode = document.getElementById('copyGeneratedCode');
        const codeTab = document.getElementById('codeTab');
        const previewTab = document.getElementById('previewTab');
        const codeTabContent = document.getElementById('codeTabContent');
        const previewTabContent = document.getElementById('previewTabContent');
        const editCode = document.getElementById('editCode');
        const editableGeneratedCode = document.getElementById('editableGeneratedCode');
        const generatedCodeDisplay = document.getElementById('generatedCodeDisplay');
        const editControls = document.getElementById('editControls');
        const cancelEdit = document.getElementById('cancelEdit');
        const saveBtn = document.getElementById('save');

        function closeGeneratedCodeModalFunc() {
            generatedCodeModal.classList.add('hidden');
        }

        // Edit Code functionality
        if (editCode) {
            editCode.addEventListener('click', () => {
                const codeDisplay = document.getElementById('generatedCodeDisplay');
                const editableCode = document.getElementById('editableGeneratedCode');
                
                // Switch to edit mode
                codeDisplay.classList.add('hidden');
                editableCode.classList.remove('hidden');
                editControls.classList.remove('hidden');
                editCode.classList.add('hidden');
                
                // Populate the editable textarea with current code
                editableCode.value = codeDisplay.textContent;
                
                // Focus on the textarea
                editableCode.focus();
            });
        }

        // Cancel Edit functionality
        if (cancelEdit) {
            cancelEdit.addEventListener('click', () => {
                const codeDisplay = document.getElementById('generatedCodeDisplay');
                const editableCode = document.getElementById('editableGeneratedCode');
                
                // Switch back to view mode
                codeDisplay.classList.remove('hidden');
                editableCode.classList.add('hidden');
                editControls.classList.add('hidden');
                editCode.classList.remove('hidden');
            });
        }

        // Combined Save functionality for the new "Save" button
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const editableCode = document.getElementById('editableGeneratedCode');
                const codeDisplay = document.getElementById('generatedCodeDisplay');
                const slug = document.getElementById('slugInput').value || document.getElementById('postSlug').value;
                let editedCode = editableCode.value;

                if (!slug) {
                    alert('Please enter a slug for the post first.');
                    return;
                }

                let codeForFile = editedCode;

                // This regex, which is inside a JS string, is escaped for the PHP parser.
                // It correctly checks for the presence of '<?php' at the start.
                if (!editedCode.match(/^<\\?php/)) {
                    // We build the PHP tag using string concatenation.
                    // This is a standard way to prevent the PHP parser from misinterpreting the tag.
                    codeForFile = '<' + `?php
// Post: ${slug}
// Generated: ${new Date().toISOString().slice(0, 19).replace('T', ' ')}
?>

${editedCode}`;
                }

                // Send the correctly formatted code to the server
                fetch('save_edited_code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ slug: slug, code: codeForFile })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // On success, update the editor with the new content
                        let htmlContent = '';
                        const phpMatch = codeForFile.match(/^<\\?php[\\s\\S]*?\\?>\\s*([\\s\\S]*)/);
                        if (phpMatch && phpMatch[1]) {
                            htmlContent = phpMatch[1].trim();
                        } else {
                            htmlContent = codeForFile.trim(); // Fallback for safety
                        }

                        if (quill) {
                            quill.root.innerHTML = htmlContent;
                        }

                        // Update the display with the code that was actually saved
                        codeDisplay.textContent = codeForFile;

                        // Switch back to view mode
                        codeDisplay.classList.remove('hidden');
                        editableCode.classList.add('hidden');
                        editControls.classList.add('hidden');
                        editCode.classList.remove('hidden');

                        // Show success feedback on the button
                        const originalText = saveBtn.innerHTML;
                        saveBtn.innerHTML = '<i class="fas fa-check mr-1"></i>Saved!';
                        saveBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                        saveBtn.classList.add('bg-blue-600');

                        setTimeout(() => {
                            saveBtn.innerHTML = originalText;
                            saveBtn.classList.remove('bg-blue-600');
                            saveBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                        }, 2000);

                    } else {
                        alert('Error saving file: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving file. Please try again.');
                });
            });
        }

        // Tab functionality
        if (codeTab && previewTab) {
            codeTab.addEventListener('click', () => {
                codeTab.classList.add('border-b-2', 'border-blue-500', 'text-blue-600');
                codeTab.classList.remove('text-gray-600');
                previewTab.classList.remove('border-b-2', 'border-blue-500', 'text-blue-600');
                previewTab.classList.add('text-gray-600');
                codeTabContent.classList.remove('hidden');
                previewTabContent.classList.add('hidden');
            });
            
            previewTab.addEventListener('click', () => {
                previewTab.classList.add('border-b-2', 'border-blue-500', 'text-blue-600');
                previewTab.classList.remove('text-gray-600');
                codeTab.classList.remove('border-b-2', 'border-blue-500', 'text-blue-600');
                codeTab.classList.add('text-gray-600');
                previewTabContent.classList.remove('hidden');
                codeTabContent.classList.add('hidden');
                
                // Update preview content
                if (quill) {
                    const content = quill.root.innerHTML;
                    document.getElementById('postPreview').innerHTML = content;
                }
            });
        }
        
        if (closeGeneratedCodeModal) {
            closeGeneratedCodeModal.addEventListener('click', closeGeneratedCodeModalFunc);
        }
        
        if (closeGeneratedCode) {
            closeGeneratedCode.addEventListener('click', closeGeneratedCodeModalFunc);
        }
        
        if (copyGeneratedCode) {
            copyGeneratedCode.addEventListener('click', () => {
                const codeDisplay = document.getElementById('generatedCodeDisplay');
                const textArea = document.createElement('textarea');
                textArea.value = codeDisplay.textContent;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Show feedback
                const originalText = copyGeneratedCode.innerHTML;
                copyGeneratedCode.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
                copyGeneratedCode.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                copyGeneratedCode.classList.add('bg-green-600');
                
                setTimeout(() => {
                    copyGeneratedCode.innerHTML = originalText;
                    copyGeneratedCode.classList.remove('bg-green-600');
                    copyGeneratedCode.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }, 2000);
            });
        }
    </script>
</body>
</html>