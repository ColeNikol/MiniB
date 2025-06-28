<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : get_blog_title() ?></title>
    
    <!-- SEO Meta Tags -->
    <?php if (isset($page_description)): ?>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <?php endif; ?>
    
    <?php if (isset($page_keywords)): ?>
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <?php endif; ?>
    
    <!-- Open Graph Meta Tags -->
    <?php if (isset($og_title)): ?>
    <meta property="og:title" content="<?= htmlspecialchars($og_title) ?>">
    <?php endif; ?>
    
    <?php if (isset($og_description)): ?>
    <meta property="og:description" content="<?= htmlspecialchars($og_description) ?>">
    <?php endif; ?>
    
    <?php if (isset($og_image)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
    <?php endif; ?>
    
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $_SERVER['REQUEST_URI'] ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <?php if (isset($twitter_title)): ?>
    <meta name="twitter:title" content="<?= htmlspecialchars($twitter_title) ?>">
    <?php endif; ?>
    
    <?php if (isset($twitter_description)): ?>
    <meta name="twitter:description" content="<?= htmlspecialchars($twitter_description) ?>">
    <?php endif; ?>
    
    <?php if (isset($twitter_image)): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($twitter_image) ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= getThemeUrl() ?>/assets/images/favicon.ico">
    
    <!-- External CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Theme Styles -->
    <?php enqueueThemeStyles(); ?>
    
    <!-- Custom CSS -->
    <?php $custom_css = get_theme_custom_css(); ?>
    <?php if (!empty($custom_css)): ?>
    <style>
        <?= $custom_css ?>
    </style>
    <?php endif; ?>
    
    <!-- Analytics -->
    <?php $analytics = get_theme_analytics(); ?>
    <?php if (!empty($analytics['google_analytics'])): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($analytics['google_analytics']) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= htmlspecialchars($analytics['google_analytics']) ?>');
    </script>
    <?php endif; ?>
    
    <?php if (!empty($analytics['google_tag_manager'])): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?= htmlspecialchars($analytics['google_tag_manager']) ?>');</script>
    <?php endif; ?>
    
    <?php if (!empty($analytics['facebook_pixel'])): ?>
    <!-- Facebook Pixel -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?= htmlspecialchars($analytics['facebook_pixel']) ?>');
        fbq('track', 'PageView');
    </script>
    <?php endif; ?>
</head>
<body class="bg-gray-50 font-sans">
    <?php if (!empty($analytics['google_tag_manager'])): ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= htmlspecialchars($analytics['google_tag_manager']) ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php endif; ?>
    
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="./" class="logo text-xl font-bold text-blue-600 flex items-center">
                        <?php $logo_url = get_custom_logo_url(); ?>
                        <?php if (!empty($logo_url)): ?>
                            <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= get_custom_logo_text() ?>" class="h-8 w-auto mr-2">
                        <?php else: ?>
                            <i class="fas fa-blog mr-2"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars(get_custom_logo_text()) ?>
                    </a>
                </div>
                
                <div class="flex items-center">
                    <!-- Search Bar -->
                    <div class="relative w-64 mr-4 search-container">
                        <input type="text" id="searchInput" placeholder="Search posts..." class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                        <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                    </div>
                    
                    <!-- Theme Toggle Button -->
                    <button id="themeToggle" class="theme-toggle mr-4" title="Toggle dark mode">
                        <i class="fas fa-sun sun-icon"></i>
                        <i class="fas fa-moon moon-icon" style="display: none;"></i>
                    </button>
                    
                    <!-- Admin Controls -->
                    <?php if(isset($is_admin) && $is_admin): ?>
                        <button id="newPostBtn" class="btn btn-success mr-2">
                            <i class="fas fa-plus mr-2"></i>New Post
                        </button>
                        <form method="post" class="inline">
                            <button name="logout" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </button>
                        </form>
                    <?php else: ?>
                        <button id="loginBtn" class="btn btn-primary">
                            <i class="fas fa-user"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <main class="min-h-screen"> 