<?php
/**
 * Single Post Template
 * Displays individual blog posts
 */

// Include theme configuration
require_once '../../theme-config.php';

// Set page variables
$page_title = $post_info ? htmlspecialchars($post_info['title']) : 'Post';
$page_description = $post_info ? get_post_excerpt($content ?? '', 160) : '';
$og_title = $page_title;
$og_description = $page_description;
$og_image = !empty($post_info['thumbnail']) ? $post_info['thumbnail'] : '';

// Include header
includeThemeTemplate('header', [
    'page_title' => $page_title,
    'page_description' => $page_description,
    'og_title' => $og_title,
    'og_description' => $og_description,
    'og_image' => $og_image
]);
?>

<!-- Post Content -->
<main class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <article class="bg-white rounded-xl shadow-lg overflow-hidden" data-post-slug="<?= htmlspecialchars($slug) ?>">
            <?php if ($post_info): ?>
            <!-- Post Header -->
            <div class="p-8 border-b">
                <!-- Back to Blog Link -->
                <div class="mb-6">
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Blog
                    </a>
                </div>
                
                <!-- Post Tags -->
                <?php if (!empty($post_info['tags'])): ?>
                <div class="flex flex-wrap gap-2 mb-4">
                    <?php foreach($post_info['tags'] as $tag): ?>
                        <span class="bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Post Title -->
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($post_info['title']) ?></h1>
                
                <!-- Post Meta -->
                <div class="flex flex-wrap items-center text-gray-600 text-sm space-x-6">
                    <span class="flex items-center">
                        <i class="fas fa-calendar mr-2"></i>
                        <?= format_post_date($post_info['created_at']) ?>
                    </span>
                    <span class="flex items-center view-count">
                        <i class="fas fa-eye mr-2"></i>
                        <?= $post_info['views'] ?? 0 ?> views
                    </span>
                    <?php 
                    $post_file = './' . $slug . '/index.php';
                    if (file_exists($post_file)) {
                        $content = file_get_contents($post_file);
                        $content = preg_replace('/<\?php.*?\?>/s', '', $content);
                        $reading_time = get_post_reading_time($content);
                        echo '<span class="flex items-center"><i class="fas fa-clock mr-2"></i>' . $reading_time . '</span>';
                    }
                    ?>
                    <span class="flex items-center">
                        <i class="fas fa-folder mr-2"></i>
                        <?= get_post_category($post_info['tags']) ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Post Content -->
            <div class="p-8 prose prose-lg max-w-none">
                <?php
                // Include the post file - this will execute any PHP code in the post
                include $post_file;
                ?>
            </div>
            
            <!-- Post Footer -->
            <div class="p-8 border-t bg-gray-50">
                <!-- Share Buttons -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Share this post:</h3>
                    <div class="flex space-x-3">
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($post_info['title'] ?? '') ?>" 
                           class="bg-blue-500 text-white p-2 rounded-full hover:bg-blue-600 transition" target="_blank" rel="noopener">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                           class="bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition" target="_blank" rel="noopener">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                           class="bg-blue-700 text-white p-2 rounded-full hover:bg-blue-800 transition" target="_blank" rel="noopener">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="mailto:?subject=<?= urlencode($post_info['title'] ?? '') ?>&body=<?= urlencode('Check out this post: ' . $_SERVER['REQUEST_URI']) ?>" 
                           class="bg-gray-500 text-white p-2 rounded-full hover:bg-gray-600 transition">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Tags -->
                <?php if (!empty($post_info['tags'])): ?>
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Tags:</h4>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($post_info['tags'] as $tag): ?>
                            <span class="bg-gray-200 text-gray-800 text-sm px-3 py-1 rounded-full"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </article>
        
        <!-- Related Posts -->
        <?php 
        $related_posts = get_related_posts($slug, $metadata, 3);
        if (!empty($related_posts)):
        ?>
        <section class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Posts</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($related_posts as $related_post): ?>
                <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                    <?php if (!empty($related_post['thumbnail'])): ?>
                    <img src="<?= htmlspecialchars($related_post['thumbnail']) ?>" alt="<?= htmlspecialchars($related_post['title']) ?>" class="w-full h-32 object-cover">
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <a href="view_post.php?slug=<?= htmlspecialchars($related_post['slug']) ?>" class="hover:text-blue-600 transition">
                                <?= htmlspecialchars($related_post['title']) ?>
                            </a>
                        </h3>
                        <div class="flex items-center text-sm text-gray-500">
                            <span class="flex items-center">
                                <i class="fas fa-calendar mr-1"></i>
                                <?= format_post_date($related_post['date']) ?>
                            </span>
                            <span class="mx-2">•</span>
                            <span class="flex items-center">
                                <i class="fas fa-eye mr-1"></i>
                                <?= $related_post['views'] ?> views
                            </span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Comments Section (Placeholder) -->
        <section class="mt-12 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Comments</h2>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-comments text-4xl mb-4"></i>
                <p>Comments are not available yet. This feature will be added in a future update.</p>
            </div>
        </section>
    </div>
</main>

<?php
// Include footer
includeThemeTemplate('footer', [
    'metadata' => $metadata
]);
?> 