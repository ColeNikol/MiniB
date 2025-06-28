<?php
// Start session and define constants
session_start();
define('POSTS_DIR', '.');

// Get post slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(404);
    echo "Post not found";
    exit;
}

// Validate slug (prevent directory traversal)
if (strpos($slug, '/') !== false || strpos($slug, '\\') !== false) {
    http_response_code(404);
    echo "Invalid post";
    exit;
}

// Check if post exists
$post_file = './' . $slug . '/index.php';

if (!file_exists($post_file)) {
    http_response_code(404);
    echo "Post not found";
    exit;
}

// Load metadata to get post information
$metadata = [];
if (file_exists('metadata.json')) {
    $metadata = json_decode(file_get_contents('metadata.json'), true) ?: [];
}

$post_info = $metadata[$slug] ?? null;

// Set content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post_info ? htmlspecialchars($post_info['title']) : 'Post' ?> - PHP Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="index.php" class="text-xl font-bold text-blue-600 flex items-center">
                        <i class="fas fa-blog mr-2"></i> PHP Blog
                    </a>
                </div>
                <div class="flex items-center">
                    <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Blog
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Post Content -->
    <main class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <article class="bg-white rounded-xl shadow-lg overflow-hidden">
                <?php if ($post_info): ?>
                <!-- Post Header -->
                <div class="p-8 border-b">
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php foreach($post_info['tags'] as $tag): ?>
                            <span class="bg-gray-100 text-gray-800 text-sm px-3 py-1 rounded-full"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($post_info['title']) ?></h1>
                    <div class="flex items-center text-gray-600 text-sm">
                        <i class="fas fa-calendar mr-2"></i>
                        <span><?= date('F d, Y', strtotime($post_info['created_at'])) ?></span>
                        <span class="mx-2">•</span>
                        <i class="fas fa-eye mr-2"></i>
                        <span><?= $post_info['views'] ?? 0 ?> views</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Post Content -->
                <div class="p-8">
                    <?php
                    // Include the post file - this will execute any PHP code in the post
                    include $post_file;
                    ?>
                </div>
            </article>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>© 2023 PHP Blog. All rights reserved. Built with PHP and Tailwind CSS.</p>
        </div>
    </footer>
</body>
</html> 