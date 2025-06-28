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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['slug']) || !isset($input['code'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$slug = $input['slug'];
$code = $input['code'];

// Validate slug (prevent directory traversal)
if (strpos($slug, '/') !== false || strpos($slug, '\\') !== false) {
    echo json_encode(['success' => false, 'error' => 'Invalid slug']);
    exit;
}

// Build file path
$post_file = './' . $slug . '/index.php';

// Check if post directory exists
$post_dir = './' . $slug;
if (!is_dir($post_dir)) {
    echo json_encode(['success' => false, 'error' => 'Post directory not found']);
    exit;
}

// Validate that the code starts with PHP tags
if (!preg_match('/^<\?php/', trim($code))) {
    echo json_encode(['success' => false, 'error' => 'Invalid PHP code format']);
    exit;
}

// Save the edited code to the file
if (file_put_contents($post_file, $code)) {
    echo json_encode(['success' => true, 'message' => 'Code saved successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save code']);
}
?> 