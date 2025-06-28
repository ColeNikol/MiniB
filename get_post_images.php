<?php
// Start session and define constants
session_start();
define('POSTS_DIR', '.');

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get post slug from request
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    echo json_encode(['success' => false, 'error' => 'No slug provided']);
    exit;
}

// Get images for the post
$images_dir = './' . $slug;
$images = [];

if (is_dir($images_dir)) {
    // Get images from the main slug directory only
    $files = glob($images_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $images[] = basename($file);
        }
    }
}

echo json_encode(['success' => true, 'images' => $images]);
?> 