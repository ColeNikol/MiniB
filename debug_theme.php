<?php
session_start();

// Include theme configuration
require_once 'theme-config.php';

echo "<h1>Theme Debug</h1>";

// Test current theme
$current_theme = getCurrentTheme();
echo "<p>Current theme: $current_theme</p>";

// Test theme path
$theme_path = getThemePath($current_theme);
echo "<p>Theme path: $theme_path</p>";

// Test options file
$options_file = $theme_path . '/options.json';
echo "<p>Options file: $options_file</p>";
echo "<p>File exists: " . (file_exists($options_file) ? 'Yes' : 'No') . "</p>";
echo "<p>File writable: " . (is_writable($options_file) ? 'Yes' : 'No') . "</p>";

// Test current options
$current_options = [];
if (file_exists($options_file)) {
    $current_options = json_decode(file_get_contents($options_file), true) ?: [];
}
echo "<p>Current options: " . json_encode($current_options) . "</p>";

// Test session options
echo "<p>Session theme options: " . json_encode($_SESSION['theme_options'] ?? []) . "</p>";

// Test POST data if any
if ($_POST) {
    echo "<h2>POST Data:</h2>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Test saving
    $test_options = [
        'site_name' => $_POST['site_name'] ?? 'Test Site',
        'site_title' => $_POST['site_title'] ?? 'Test Title',
        'primary_color' => $_POST['primary_color'] ?? '#FF0000'
    ];
    
    $save_result = file_put_contents($options_file, json_encode($test_options, JSON_PRETTY_PRINT));
    echo "<p>Save result: " . ($save_result !== false ? "Success ($save_result bytes)" : "Failed") . "</p>";
    
    if ($save_result !== false) {
        echo "<p style='color: green;'>Theme saved successfully!</p>";
        
        // Test reading back
        $saved_options = json_decode(file_get_contents($options_file), true) ?: [];
        echo "<p>Saved options: " . json_encode($saved_options) . "</p>";
    } else {
        echo "<p style='color: red;'>Failed to save theme!</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Theme Debug</title>
</head>
<body>
    <h2>Test Form</h2>
    
    <form method="post">
        <p>
            <label>Site Name:</label>
            <input type="text" name="site_name" value="Test Site">
        </p>
        <p>
            <label>Site Title:</label>
            <input type="text" name="site_title" value="Test Title">
        </p>
        <p>
            <label>Primary Color:</label>
            <input type="color" name="primary_color" value="#FF0000">
        </p>
        <p>
            <button type="submit">Test Save</button>
        </p>
    </form>
    
    <h2>Current Theme Options:</h2>
    <?php
    if (file_exists($options_file)) {
        $options = json_decode(file_get_contents($options_file), true) ?: [];
        echo "<pre>" . print_r($options, true) . "</pre>";
    } else {
        echo "<p>No options file found.</p>";
    }
    ?>
</body>
</html> 