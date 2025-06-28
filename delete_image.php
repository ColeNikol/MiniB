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

if (!$input || !isset($input['slug']) || !isset($input['filename'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$slug = $input['slug'];
$filename = $input['filename'];

// Validate inputs (prevent directory traversal)
if (strpos($slug, '/') !== false || strpos($slug, '\\') !== false || 
    strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Build file path
$image_path = './' . $slug . '/' . $filename;

// Check if file exists and is within the allowed directory
if (!file_exists($image_path) || !is_file($image_path)) {
    echo json_encode(['success' => false, 'error' => 'File not found']);
    exit;
}

// Validate file extension
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($extension, $allowed_extensions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit;
}

// Delete the file
if (unlink($image_path)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
}
?> 