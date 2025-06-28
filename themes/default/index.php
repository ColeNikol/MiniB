<?php
/**
 * Index Template
 * Displays the homepage with blog posts
 */

// Include theme configuration
require_once '../../theme-config.php';

// Set page variables
$page_title = get_blog_title();
$page_description = get_blog_description();

// Include header
includeThemeTemplate('header', [
    'page_title' => $page_title,
    'page_description' => $page_description,
    'is_admin' => $is_admin ?? false
]);
?>

<!-- Hero Section -->
<header class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="hero-title text-4xl md:text-5xl font-bold mb-4"><?= htmlspecialchars(get_blog_title()) ?></h1>
        <p class="hero-subtitle text-xl mb-8 max-w-3xl mx-auto"><?= htmlspecialchars(get_blog_description()) ?></p>
        <div class="flex justify-center space-x-4">
            <a href="#posts" class="explore-posts bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">Explore Posts</a>
        </div>
    </div>
</header>

<!-- Admin Dashboard -->
<?php if(isset($is_admin) && $is_admin): ?>
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
                <div class="bg-blue-50 p-6 rounded-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-600 rounded-full">
                            <i class="fas fa-file-alt text-white"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-blue-600">Total Posts</p>
                            <p class="text-2xl font-bold text-blue-900"><?= count($metadata) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 p-6 rounded-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-600 rounded-full">
                            <i class="fas fa-eye text-white"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-green-600">Total Views</p>
                            <p class="text-2xl font-bold text-green-900"><?= array_sum(array_column($metadata, 'views')) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-purple-50 p-6 rounded-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-600 rounded-full">
                            <i class="fas fa-tags text-white"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-purple-600">Categories</p>
                            <p class="text-2xl font-bold text-purple-900"><?= count(get_all_categories($metadata)) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-orange-50 p-6 rounded-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-orange-600 rounded-full">
                            <i class="fas fa-calendar text-white"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-orange-600">Latest Post</p>
                            <p class="text-lg font-bold text-orange-900"><?= date('M d', strtotime(array_values($metadata)[0]['created_at'] ?? 'now')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Posts Section -->
<section id="posts" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Latest Posts</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Discover our latest articles, tutorials, and insights on web development, programming, and technology.</p>
        </div>
        
        <!-- Posts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($metadata as $slug => $post): ?>
            <article class="post-card bg-white rounded-xl shadow-lg overflow-hidden fade-in" data-post-slug="<?= htmlspecialchars($slug) ?>">
                <!-- Post Thumbnail -->
                <?php $thumbnail = get_post_thumbnail($slug, $metadata); ?>
                <?php if (!empty($thumbnail)): ?>
                <div class="aspect-w-16 aspect-h-9">
                    <img src="<?= htmlspecialchars($thumbnail) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-48 object-cover">
                </div>
                <?php endif; ?>
                
                <!-- Post Content -->
                <div class="p-6">
                    <!-- Post Tags -->
                    <?php if (!empty($post['tags'])): ?>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php foreach (array_slice($post['tags'], 0, 3) as $tag): ?>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full post-tags"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Post Title -->
                    <h3 class="post-title text-xl font-bold text-gray-900 mb-3">
                        <a href="view_post.php?slug=<?= htmlspecialchars($slug) ?>" class="hover:text-blue-600 transition">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h3>
                    
                    <!-- Post Excerpt -->
                    <?php 
                    $post_file = './' . $slug . '/index.php';
                    $excerpt = '';
                    if (file_exists($post_file)) {
                        $content = file_get_contents($post_file);
                        // Remove PHP tags and get content
                        $content = preg_replace('/<\?php.*?\?>/s', '', $content);
                        $excerpt = get_post_excerpt($content, get_post_display_options()['excerpt_length'] ?? 150);
                    }
                    ?>
                    <?php if (!empty($excerpt)): ?>
                    <p class="post-excerpt text-gray-600 mb-4"><?= htmlspecialchars($excerpt) ?></p>
                    <?php endif; ?>
                    
                    <!-- Post Meta -->
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <div class="flex items-center space-x-4">
                            <span class="flex items-center">
                                <i class="fas fa-calendar mr-1"></i>
                                <?= format_post_date($post['created_at']) ?>
                            </span>
                            <span class="flex items-center view-count">
                                <i class="fas fa-eye mr-1"></i>
                                <?= $post['views'] ?? 0 ?> views
                            </span>
                        </div>
                        <a href="view_post.php?slug=<?= htmlspecialchars($slug) ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                            Read More <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <!-- No Posts Message -->
        <?php if (empty($metadata)): ?>
        <div class="text-center py-12">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-file-alt text-6xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Posts Yet</h3>
            <p class="text-gray-600 mb-6">Start creating your first blog post to get started.</p>
            <?php if(isset($is_admin) && $is_admin): ?>
            <a href="create_sample_php_post.php" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>Create Your First Post
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Categories Section -->
<?php 
$categories = get_all_categories($metadata);
if (!empty($categories)):
?>
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Browse by Category</h2>
            <p class="text-lg text-gray-600">Explore posts by topic and find what interests you most.</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach (array_slice($categories, 0, 12) as $category => $count): ?>
            <a href="#category-<?= htmlspecialchars($category) ?>" class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition text-center group">
                <div class="text-2xl text-blue-600 mb-2 group-hover:text-blue-800">
                    <i class="fas fa-folder"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1"><?= htmlspecialchars(ucfirst($category)) ?></h3>
                <p class="text-sm text-gray-500"><?= $count ?> posts</p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Newsletter Section -->
<section class="py-12 bg-blue-600 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">Stay Updated</h2>
        <p class="text-xl mb-8 opacity-90">Get the latest posts and updates delivered to your inbox.</p>
        <form class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
            <input type="email" placeholder="Enter your email" class="flex-1 px-4 py-3 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-white">
            <button type="submit" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                Subscribe
            </button>
        </form>
    </div>
</section>

<?php
// Include footer
includeThemeTemplate('footer', [
    'metadata' => $metadata
]);
?>
