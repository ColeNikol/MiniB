    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Blog Info -->
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-xl font-bold mb-4"><?= htmlspecialchars(get_blog_title()) ?></h3>
                    <p class="text-gray-300 mb-4"><?= htmlspecialchars(get_blog_description()) ?></p>
                    <div class="flex space-x-4">
                        <?php $social_links = get_social_links(); ?>
                        <?php if (!empty($social_links['twitter'])): ?>
                            <a href="<?= htmlspecialchars($social_links['twitter']) ?>" class="text-gray-300 hover:text-white transition" target="_blank" rel="noopener">
                                <i class="fab fa-twitter text-xl"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($social_links['facebook'])): ?>
                            <a href="<?= htmlspecialchars($social_links['facebook']) ?>" class="text-gray-300 hover:text-white transition" target="_blank" rel="noopener">
                                <i class="fab fa-facebook text-xl"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($social_links['instagram'])): ?>
                            <a href="<?= htmlspecialchars($social_links['instagram']) ?>" class="text-gray-300 hover:text-white transition" target="_blank" rel="noopener">
                                <i class="fab fa-instagram text-xl"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($social_links['github'])): ?>
                            <a href="<?= htmlspecialchars($social_links['github']) ?>" class="text-gray-300 hover:text-white transition" target="_blank" rel="noopener">
                                <i class="fab fa-github text-xl"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($social_links['linkedin'])): ?>
                            <a href="<?= htmlspecialchars($social_links['linkedin']) ?>" class="text-gray-300 hover:text-white transition" target="_blank" rel="noopener">
                                <i class="fab fa-linkedin text-xl"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <?php $nav_menu = get_navigation_menu(); ?>
                        <?php foreach ($nav_menu as $item): ?>
                            <li>
                                <a href="<?= htmlspecialchars($item['url']) ?>" class="text-gray-300 hover:text-white transition">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Categories -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Categories</h4>
                    <ul class="space-y-2">
                        <?php 
                        if (isset($metadata)) {
                            $categories = get_all_categories($metadata);
                            $category_count = 0;
                            foreach ($categories as $category => $count) {
                                if ($category_count >= 5) break; // Show only top 5 categories
                                echo '<li><a href="#category-' . htmlspecialchars($category) . '" class="text-gray-300 hover:text-white transition">' . htmlspecialchars(ucfirst($category)) . ' (' . $count . ')</a></li>';
                                $category_count++;
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-300"><?= htmlspecialchars(get_footer_text()) ?></p>
                <p class="text-gray-400 text-sm mt-2">Powered by <a href="#" class="text-blue-400 hover:text-blue-300">MiniB</a></p>
            </div>
        </div>
    </footer>
    
    <!-- Theme Scripts -->
    <?php enqueueThemeScripts(); ?>
    
    <!-- Custom JavaScript -->
    <?php $custom_js = get_theme_custom_js(); ?>
    <?php if (!empty($custom_js)): ?>
    <script>
        <?= $custom_js ?>
    </script>
    <?php endif; ?>
    
    <!-- Theme Toggle Script -->
    <script>
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const sunIcon = document.querySelector('.sun-icon');
            const moonIcon = document.querySelector('.moon-icon');
            
            // Check for saved theme preference or default to light mode
            const currentTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', currentTheme === 'dark');
            updateThemeIcon(currentTheme === 'dark');
            
            themeToggle.addEventListener('click', function() {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                updateThemeIcon(isDark);
            });
            
            function updateThemeIcon(isDark) {
                if (isDark) {
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'inline';
                } else {
                    sunIcon.style.display = 'inline';
                    moonIcon.style.display = 'none';
                }
            }
        });
    </script>
    
    <!-- Search Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const postCards = document.querySelectorAll('.post-card');
            
            if (searchInput && postCards.length > 0) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    
                    postCards.forEach(card => {
                        const title = card.querySelector('.post-title')?.textContent.toLowerCase() || '';
                        const excerpt = card.querySelector('.post-excerpt')?.textContent.toLowerCase() || '';
                        const tags = card.querySelector('.post-tags')?.textContent.toLowerCase() || '';
                        
                        const matches = title.includes(searchTerm) || 
                                      excerpt.includes(searchTerm) || 
                                      tags.includes(searchTerm);
                        
                        card.style.display = matches ? 'block' : 'none';
                    });
                });
            }
        });
    </script>
    
    <!-- Admin Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPostBtn = document.getElementById('newPostBtn');
            const loginBtn = document.getElementById('loginBtn');
            
            if (newPostBtn) {
                newPostBtn.addEventListener('click', function() {
                    // Show new post form or redirect to post creation page
                    window.location.href = './create_sample_php_post.php';
                });
            }
            
            if (loginBtn) {
                loginBtn.addEventListener('click', function() {
                    // Show login modal or redirect to login page
                    const username = prompt('Username:');
                    const password = prompt('Password:');
                    
                    if (username && password) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="username" value="${username}">
                            <input type="hidden" name="password" value="${password}">
                            <input type="hidden" name="login" value="1">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });
    </script>
    
    <!-- View Counter Script -->
    <script>
        function incrementView(slug) {
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'increment_view=1&slug=' + encodeURIComponent(slug)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const viewElement = document.querySelector(`[data-post-slug="${slug}"] .view-count`);
                    if (viewElement) {
                        viewElement.textContent = data.views;
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Auto-increment view when post is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const postSlug = document.querySelector('[data-post-slug]')?.getAttribute('data-post-slug');
            if (postSlug) {
                incrementView(postSlug);
            }
        });
    </script>
</body>
</html> 