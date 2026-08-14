<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php'; // Include language file for translations
$page_class = 'news';
include $project_root . 'includes/header.php';

$default_image_url = 'img/newsimg/news.png';
$items_per_page = 6;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$is_single = !empty($slug);

// Category color mapping - Distinct colors for each category
$category_colors = [
    'patch' => ['bg' => 'rgba(46, 204, 113, 0.15)', 'border' => 'rgba(46, 204, 113, 0.4)', 'text' => '#2ecc71', 'hover' => 'rgba(46, 204, 113, 0.25)', 'badge' => '#2ecc71'],
    'event' => ['bg' => 'rgba(52, 152, 219, 0.15)', 'border' => 'rgba(52, 152, 219, 0.4)', 'text' => '#3498db', 'hover' => 'rgba(52, 152, 219, 0.25)', 'badge' => '#3498db'],
    'announcement' => ['bg' => 'rgba(155, 89, 182, 0.15)', 'border' => 'rgba(155, 89, 182, 0.4)', 'text' => '#9b59b6', 'hover' => 'rgba(155, 89, 182, 0.25)', 'badge' => '#9b59b6'],
    'general' => ['bg' => 'rgba(241, 196, 15, 0.15)', 'border' => 'rgba(241, 196, 15, 0.4)', 'text' => '#f1c40f', 'hover' => 'rgba(241, 196, 15, 0.25)', 'badge' => '#f1c40f'],
    'maintenance' => ['bg' => 'rgba(231, 76, 60, 0.15)', 'border' => 'rgba(231, 76, 60, 0.4)', 'text' => '#e74c3c', 'hover' => 'rgba(231, 76, 60, 0.25)', 'badge' => '#e74c3c'],
    'update' => ['bg' => 'rgba(46, 204, 113, 0.15)', 'border' => 'rgba(46, 204, 113, 0.4)', 'text' => '#2ecc71', 'hover' => 'rgba(46, 204, 113, 0.25)', 'badge' => '#2ecc71'],
];

if ($is_single) {
    $query = "SELECT id, title, slug, content, posted_by, post_date, image_url, is_important, category 
              FROM server_news 
              WHERE slug = ?";
    $stmt = $site_db->prepare($query);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $news = $result->fetch_assoc();
    $stmt->close();

    if (!$news) {
        header('HTTP/1.0 404 Not Found');
        echo '<h1>' . translate('error_404_title', '404 - News Not Found') . '</h1>';
        echo '<p>' . translate('error_404_message', 'The news article you are looking for does not exist.') . '</p>';
        include $project_root . 'includes/footer.php';
        exit;
    }
} else {
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($current_page - 1) * $items_per_page;

    // Build query with category filter
    $where_clause = '';
    $params = [];
    $types = '';

    if (!empty($category_filter)) {
        $where_clause = "WHERE category = ?";
        $params[] = $category_filter;
        $types .= 's';
    }

    $total_query = "SELECT COUNT(*) as total FROM server_news " . $where_clause;
    $total_stmt = $site_db->prepare($total_query);
    if (!empty($params)) {
        $total_stmt->bind_param($types, ...$params);
    }
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_rows = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $items_per_page);
    $current_page = min($current_page, $total_pages);
    $offset = ($current_page - 1) * $items_per_page;

    $query = "SELECT id, title, slug, LEFT(content, 200) as excerpt, posted_by, 
              post_date, image_url, is_important, category 
              FROM server_news 
              " . $where_clause . "
              ORDER BY is_important DESC, post_date DESC
              LIMIT ?, ?";
    $stmt = $site_db->prepare($query);
    
    // Build parameter array for the main query
    $params[] = $offset;
    $params[] = $items_per_page;
    $types .= 'ii';
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Get all categories for the filter tabs
$category_query = "SELECT DISTINCT category FROM server_news ORDER BY category";
$category_result = $site_db->query($category_query);
$categories = [];
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row['category'];
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($is_single): ?>
        <meta name="description" content="<?php echo htmlspecialchars(substr($news['content'], 0, 150)); ?>...">
        <link rel="canonical" href="<?php echo $base_path; ?>news?slug=<?php echo htmlspecialchars($news['slug']); ?>">
        <title><?php echo htmlspecialchars($news['title']); ?></title>
    <?php else: ?>
        <meta name="description" content="<?php echo translate('meta_description_list', 'Latest news and updates for our World of Warcraft server.'); ?>">
        <link rel="canonical" href="<?php echo $base_path; ?>news?page=<?php echo $current_page; ?>">
        <title><?php echo $site_title_name ." ". translate('page_title_list', 'News'); ?></title>
    <?php endif; ?>
    <meta name="robots" content="index">
    
    <!-- Tailwind CSS -->
    
    <!-- Custom styles for things Tailwind can't handle easily -->
    <style>
        /* Page background */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-news.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            position: relative;
        }
        
        /* Glass morphism effects */
        .glass-container {
            background: rgba(10, 14, 22, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8), inset 0 0 60px rgba(0,0,0,.25);
        }
        
        /* Corner decoration */
        .glass-decoration {
            background:
                linear-gradient(#e8c552,#e8c552) left top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) left bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left bottom / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right bottom / 2px 18px;
            background-repeat: no-repeat;
        }
        
        /* Category tab colors */
        .tab-patch { --tab-color: #2ecc71; }
        .tab-event { --tab-color: #3498db; }
        .tab-announcement { --tab-color: #9b59b6; }
        .tab-general { --tab-color: #f1c40f; }
        .tab-maintenance { --tab-color: #e74c3c; }
        .tab-update { --tab-color: #2ecc71; }
        .tab-all { --tab-color: #ffffff; }
        
        .category-tab {
            transition: all 0.3s ease;
            clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
            font-family: 'Cinzel', serif;
            letter-spacing: 0.04em;
        }
        
        .category-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.35), 0 0 20px var(--tab-color);
        }
        
        .category-tab.active {
            box-shadow: 0 0 30px var(--tab-color);
        }
        
        /* News cards */
        .news-card {
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        
        .news-card:hover {
            transform: translateY(-6px);
        }
        
        .news-card-image {
            height: 180px;
            object-fit: cover;
        }
        
        /* Card category top borders - applied via inline styles */
        .card-patch { border-top-color: #2ecc71; }
        .card-event { border-top-color: #3498db; }
        .card-announcement { border-top-color: #9b59b6; }
        .card-general { border-top-color: #f1c40f; }
        .card-maintenance { border-top-color: #e74c3c; }
        .card-update { border-top-color: #2ecc71; }
        
        /* Important badge */
        .important-badge {
            clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px);
        }
        
        /* Category badges */
        .category-badge {
            clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px);
        }
        
        /* Clip-path utilities */
        .clip-path-tab {
            clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
        }
        .clip-path-badge {
            clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px);
        }
        
        /* Wow title gradient */
        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.9));
            letter-spacing: .02em;
        }
        
        /* Single article image */
        .news-single-image {
            max-height: 400px;
            object-fit: cover;
        }
        
        /* Text shadow utilities */
        .text-shadow-lg {
            text-shadow: 0 0 30px rgba(0,0,0,0.8);
        }
        .text-shadow-md {
            text-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .text-shadow-sm {
            text-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        
        @media (max-width: 768px) {
            .news-single-title {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>

<div class="relative z-10 min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        
        <!-- Main Container -->
        <div class="glass-container relative border border-[rgba(201,162,39,0.22)] p-6 md:p-10">
            <!-- Inner border -->
            <div class="absolute inset-[5px] border border-[rgba(201,162,39,0.14)] pointer-events-none"></div>
            <!-- Corner decorations -->
            <div class="absolute inset-0 glass-decoration pointer-events-none"></div>
            
            <?php if ($is_single): ?>
                <!-- Single News Article View -->
                <article class="max-w-[900px] mx-auto">
                    <?php if (!empty($news['image_url'])): ?>
                        <img src="<?php echo $base_path . htmlspecialchars($news['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($news['title']); ?>" 
                             class="news-single-image w-full rounded-none mb-6 border border-[rgba(201,162,39,0.2)]"
                             onerror="this.src='<?php echo $base_path . htmlspecialchars($default_image_url); ?>'">
                    <?php else: ?>
                        <img src="<?php echo $base_path . htmlspecialchars($default_image_url); ?>" 
                             alt="<?php echo htmlspecialchars($news['title']); ?>" 
                             class="news-single-image w-full rounded-none mb-6 border border-[rgba(201,162,39,0.2)]">
                    <?php endif; ?>
                    
                    <h1 class="text-white text-3xl md:text-4xl font-extrabold leading-tight mb-4 font-['Cinzel'] text-shadow-lg">
                        <?php echo htmlspecialchars($news['title']); ?>
                        <?php if ($news['is_important']): ?>
                            <span class="important-badge inline-block ml-2 bg-[rgba(242,207,82,0.15)] text-[#f2cf5b] font-bold text-[0.6rem] px-2 py-[0.15rem] uppercase tracking-wider border border-[rgba(201,162,39,0.2)]">
                                <i class="fas fa-star mr-1"></i> Important
                            </span>
                        <?php endif; ?>
                    </h1>
                    
                    <div class="flex flex-wrap gap-4 text-gray-400 text-sm pb-6 border-b border-[rgba(201,162,39,0.1)] mb-6">
                        <?php 
                        $cat = $news['category'];
                        $colors = $category_colors[$cat] ?? ['badge' => '#ffffff'];
                        $badgeColor = $colors['badge'];
                        ?>
                        <span class="category-badge inline-block px-3 py-[0.15rem] font-semibold text-[0.65rem] uppercase tracking-wider border" style="background: <?php echo $badgeColor; ?>20; color: <?php echo $badgeColor; ?>; border-color: <?php echo $badgeColor; ?>30;">
                            <i class="fas fa-tag mr-1"></i> <?php echo translate('category_' . $news['category'], ucfirst(htmlspecialchars($news['category']))); ?>
                        </span>
                        <span><i class="far fa-calendar-alt mr-1"></i> <?php echo date('F j, Y', strtotime($news['post_date'])); ?></span>
                        <span><i class="far fa-user mr-1"></i> <?php echo sprintf(translate('posted_by', 'Posted by %s'), htmlspecialchars($news['posted_by'])); ?></span>
                    </div>
                    
                    <div class="text-gray-300 text-base leading-relaxed text-shadow-md">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </div>
                    
                    <a href="<?php echo $base_path; ?>news" class="inline-block mt-8 px-6 py-2 bg-black/35 border border-[rgba(201,162,39,0.2)] text-gray-400 no-underline font-semibold transition-all duration-300 clip-path-tab font-['Cinzel'] tracking-wide hover:bg-[rgba(242,207,82,0.15)] hover:border-[#f2cf5b] hover:-translate-x-1 hover:text-[#f2cf5b] hover:shadow-[0_0_20px_rgba(242,207,82,0.15)]">
                        <i class="fas fa-arrow-left mr-2"></i> <?php echo translate('back_to_news', 'Back to News'); ?>
                    </a>
                </article>
                
            <?php else: ?>
                <!-- News List -->
                
                <!-- Centered Title -->
                <div class="text-center mb-8">
                    <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight wow-title">
                        <?php echo $site_title_name ." ". translate('page_title_list', 'News'); ?>
                    </h1>
                    <p class="text-gray-300 mt-2 text-sm text-shadow-sm"><?php echo translate('news_subtitle', 'Stay updated with the latest news and announcements'); ?></p>
                </div>

                <!-- Category Filter Tabs -->
                <div class="bg-black/25 backdrop-blur-sm rounded-none p-[0.65rem] border border-[rgba(201,162,39,0.15)] mb-6 inline-flex flex-wrap gap-[0.45rem] justify-center w-full shadow-[inset_0_1px_0_rgba(255,255,255,0.05),0_10px_28px_rgba(0,0,0,0.35)]">
                    <a href="<?php echo $base_path; ?>news" 
                       class="category-tab tab-all px-5 py-[0.65rem] font-bold text-sm rounded-none cursor-pointer no-underline inline-flex items-center gap-2 border border-white/30 text-white bg-white/10 hover:bg-white/20 <?php echo empty($category_filter) ? 'active bg-white text-black border-white shadow-[0_0_30px_#ffffff]' : ''; ?>">
                        <i class="fas fa-th-list"></i> <?php echo translate('tab_all', 'All'); ?>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <?php 
                        $tab_color = $category_colors[$cat] ?? ['bg' => 'rgba(255,255,255,0.1)', 'border' => 'rgba(255,255,255,0.3)', 'text' => '#ffffff', 'hover' => 'rgba(255,255,255,0.2)'];
                        $isActive = ($category_filter === $cat);
                        ?>
                        <a href="<?php echo $base_path; ?>news?category=<?php echo htmlspecialchars($cat); ?>" 
                           class="category-tab tab-<?php echo htmlspecialchars($cat); ?> px-5 py-[0.65rem] font-bold text-sm rounded-none cursor-pointer no-underline inline-flex items-center gap-2 border <?php echo $isActive ? 'active text-black' : 'text-[' . htmlspecialchars($tab_color['text']) . '] bg-[' . htmlspecialchars($tab_color['bg']) . '] border-[' . htmlspecialchars($tab_color['border']) . '] hover:bg-[' . htmlspecialchars($tab_color['hover']) . '] hover:border-[' . htmlspecialchars($tab_color['text']) . '] hover:text-white'; ?>"
                           style="<?php 
                           if ($isActive) {
                               echo 'background: ' . htmlspecialchars($tab_color['text']) . '; color: #000000; border-color: ' . htmlspecialchars($tab_color['text']) . '; box-shadow: 0 0 30px ' . htmlspecialchars($tab_color['text']) . ';';
                           } else {
                               echo '--tab-bg: ' . htmlspecialchars($tab_color['bg']) . '; --tab-border: ' . htmlspecialchars($tab_color['border']) . '; --tab-text: ' . htmlspecialchars($tab_color['text']) . '; --tab-hover: ' . htmlspecialchars($tab_color['hover']) . ';';
                           }
                           ?>">
                            <i class="fas fa-tag"></i> <?php echo translate('category_' . $cat, ucfirst(htmlspecialchars($cat))); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($result->num_rows === 0): ?>
                    <div class="text-center text-gray-400 text-xl py-12 text-shadow-lg">
                        <i class="fas fa-scroll text-5xl block mb-4 text-[rgba(201,162,39,0.3)]"></i>
                        <?php echo translate('no_news', 'No news available at this time.'); ?>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php while ($news = $result->fetch_assoc()): ?>
                            <?php 
                            $card_color = $category_colors[$news['category']]['badge'] ?? '#f2cf5b';
                            ?>
                            <a href="<?php echo $base_path; ?>news?slug=<?php echo htmlspecialchars($news['slug']); ?>" 
                               class="news-card card-<?php echo htmlspecialchars($news['category']); ?> bg-[rgba(10,14,22,0.75)] border border-[rgba(201,162,39,0.18)] rounded-none no-underline text-inherit overflow-hidden flex flex-col h-full border-t-4"
                               style="border-top-color: <?php echo $card_color; ?>; --card-border: <?php echo $card_color; ?>;"
                               onmouseover="this.style.borderColor='<?php echo $card_color; ?>'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.6)'"
                               onmouseout="this.style.borderColor=''; this.style.boxShadow=''">
                                
                                <?php if (!empty($news['image_url'])): ?>
                                    <img src="<?php echo $base_path . htmlspecialchars($news['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                         class="news-card-image w-full border-b border-[rgba(201,162,39,0.1)]"
                                         onerror="this.src='<?php echo $base_path . htmlspecialchars($default_image_url); ?>'">
                                <?php else: ?>
                                    <img src="<?php echo $base_path . htmlspecialchars($default_image_url); ?>" 
                                         alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                         class="news-card-image w-full border-b border-[rgba(201,162,39,0.1)]">
                                <?php endif; ?>
                                
                                <div class="p-5 flex-1 flex flex-col">
                                    <div class="flex flex-wrap gap-3 text-[0.75rem] text-gray-500 mb-3">
                                        <span class="category-badge inline-block px-3 py-[0.15rem] font-semibold text-[0.65rem] uppercase tracking-wider border" style="background: <?php echo $card_color; ?>20; color: <?php echo $card_color; ?>; border-color: <?php echo $card_color; ?>30;">
                                            <?php echo translate('category_' . $news['category'], ucfirst(htmlspecialchars($news['category']))); ?>
                                        </span>
                                        <span><i class="far fa-calendar-alt mr-1"></i> <?php echo date('M j, Y', strtotime($news['post_date'])); ?></span>
                                    </div>
                                    
                                    <h2 class="text-white text-base font-bold transition-colors duration-300 mb-2 leading-snug font-['Cinzel']" style="--hover-color: <?php echo $card_color; ?>;">
                                        <?php echo htmlspecialchars($news['title']); ?>
                                        <?php if ($news['is_important']): ?>
                                            <span class="important-badge inline-block ml-1 bg-[rgba(242,207,82,0.15)] text-[#f2cf5b] font-bold text-[0.6rem] px-2 py-[0.15rem] uppercase tracking-wider border border-[rgba(201,162,39,0.2)]">
                                                <i class="fas fa-star mr-1"></i> Important
                                            </span>
                                        <?php endif; ?>
                                    </h2>
                                    
                                    <p class="text-gray-300 text-sm leading-relaxed m-0 flex-1"><?php echo htmlspecialchars($news['excerpt']); ?>...</p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center gap-2 mt-8 flex-wrap">
                            <?php if ($current_page > 1): ?>
                                <a href="<?php echo $base_path; ?>news?page=<?php echo $current_page - 1; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" 
                                   class="px-5 py-2 bg-black/35 border border-[rgba(201,162,39,0.15)] text-gray-400 rounded-none no-underline font-semibold transition-all duration-300 text-sm clip-path-badge font-['Cinzel'] hover:bg-[rgba(242,207,82,0.1)] hover:border-[#f2cf5b] hover:text-[#f2cf5b] hover:-translate-y-0.5 hover:shadow-[0_0_20px_rgba(242,207,82,0.1)]">
                                    <i class="fas fa-chevron-left mr-1"></i> <?php echo translate('pagination_prev', 'Prev'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="<?php echo $base_path; ?>news?page=<?php echo $i; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" 
                                   class="px-5 py-2 bg-black/35 border border-[rgba(201,162,39,0.15)] text-gray-400 rounded-none no-underline font-semibold transition-all duration-300 text-sm clip-path-badge font-['Cinzel'] hover:bg-[rgba(242,207,82,0.1)] hover:border-[#f2cf5b] hover:text-[#f2cf5b] hover:-translate-y-0.5 hover:shadow-[0_0_20px_rgba(242,207,82,0.1)] <?php echo $i == $current_page ? 'bg-gradient-to-b from-[#f6d478] via-[#c9a227] to-[#8a6a14] border-[#f2cf5b] text-[#1a1200] shadow-[0_0_20px_rgba(242,207,82,0.25)]' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="<?php echo $base_path; ?>news?page=<?php echo $current_page + 1; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" 
                                   class="px-5 py-2 bg-black/35 border border-[rgba(201,162,39,0.15)] text-gray-400 rounded-none no-underline font-semibold transition-all duration-300 text-sm clip-path-badge font-['Cinzel'] hover:bg-[rgba(242,207,82,0.1)] hover:border-[#f2cf5b] hover:text-[#f2cf5b] hover:-translate-y-0.5 hover:shadow-[0_0_20px_rgba(242,207,82,0.1)]">
                                    <?php echo translate('pagination_next', 'Next'); ?> <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
if (!$is_single) {
    $stmt->close();
}
if (isset($site_db)) {
    $site_db->close();
}
include $project_root . 'includes/footer.php'; 
?>
</body>
</html>