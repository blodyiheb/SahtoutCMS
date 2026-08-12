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

// Category color mapping
$category_colors = [
    'patch' => ['bg' => 'rgba(59, 130, 246, 0.15)', 'border' => 'rgba(59, 130, 246, 0.3)', 'text' => '#60a5fa', 'hover' => 'rgba(59, 130, 246, 0.25)'],
    'event' => ['bg' => 'rgba(16, 185, 129, 0.15)', 'border' => 'rgba(16, 185, 129, 0.3)', 'text' => '#34d399', 'hover' => 'rgba(16, 185, 129, 0.25)'],
    'announcement' => ['bg' => 'rgba(239, 68, 68, 0.15)', 'border' => 'rgba(239, 68, 68, 0.3)', 'text' => '#f87171', 'hover' => 'rgba(239, 68, 68, 0.25)'],
    'general' => ['bg' => 'rgba(139, 92, 246, 0.15)', 'border' => 'rgba(139, 92, 246, 0.3)', 'text' => '#a78bfa', 'hover' => 'rgba(139, 92, 246, 0.25)'],
    'maintenance' => ['bg' => 'rgba(251, 191, 36, 0.15)', 'border' => 'rgba(251, 191, 36, 0.3)', 'text' => '#fbbf24', 'hover' => 'rgba(251, 191, 36, 0.25)'],
    'update' => ['bg' => 'rgba(236, 72, 153, 0.15)', 'border' => 'rgba(236, 72, 153, 0.3)', 'text' => '#f472b6', 'hover' => 'rgba(236, 72, 153, 0.25)'],
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
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Page background - Only for news page */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-news.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        body::before {
            display: none;
        }
        
        .news-content {
            position: relative;
            z-index: 1;
        }
        
        .glass-container {
            background: rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8);
        }
        
        /* Tabs Container */
        .tabs-container {
            background: rgba(0, 0, 0, 0.46);
            border-radius: 12px;
            padding: 0.65rem;
            border: 1px solid rgba(255, 255, 255, 0.10);
            margin-bottom: 1.5rem;
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            justify-content: center;
            width: 100%;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05), 0 10px 28px rgba(0, 0, 0, 0.35);
        }
        
        /* Category Tab Styles */
        .category-tab {
            --tab-bg: rgba(252, 211, 77, 0.14);
            --tab-border: rgba(252, 211, 77, 0.32);
            --tab-text: #fcd34d;
            --tab-hover: rgba(252, 211, 77, 0.25);
            padding: 0.65rem 1.35rem;
            font-weight: 700;
            font-size: 0.9rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--tab-border);
            color: var(--tab-text);
            background: var(--tab-bg);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
        }
        
        .category-tab:hover {
            color: #ffffff;
            background: var(--tab-hover);
            border-color: var(--tab-text);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.35), 0 0 16px color-mix(in srgb, var(--tab-text) 28%, transparent);
        }
        
        .category-tab.active {
            color: #ffffff;
            background: linear-gradient(135deg, var(--tab-hover), var(--tab-bg));
            border-color: var(--tab-text);
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--tab-text) 45%, transparent), 0 0 20px color-mix(in srgb, var(--tab-text) 24%, transparent);
        }
        
        .category-tab i {
            font-size: 1rem;
        }
        
        /* News Cards - Only category color, no yellow left side */
        .news-card {
            background: rgba(18, 24, 34, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .news-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            background: rgba(30, 40, 55, 0.95);
        }
        
        /* Card category colors - Top border only */
        .news-card.card-patch { border-top: 4px solid #60a5fa; }
        .news-card.card-patch:hover { border-color: #60a5fa; }
        
        .news-card.card-event { border-top: 4px solid #34d399; }
        .news-card.card-event:hover { border-color: #34d399; }
        
        .news-card.card-announcement { border-top: 4px solid #f87171; }
        .news-card.card-announcement:hover { border-color: #f87171; }
        
        .news-card.card-general { border-top: 4px solid #a78bfa; }
        .news-card.card-general:hover { border-color: #a78bfa; }
        
        .news-card.card-maintenance { border-top: 4px solid #fbbf24; }
        .news-card.card-maintenance:hover { border-color: #fbbf24; }
        
        .news-card.card-update { border-top: 4px solid #f472b6; }
        .news-card.card-update:hover { border-color: #f472b6; }
        
        /* Important badge - no yellow left side */
        .news-card.important {
            position: relative;
        }
        
        .news-card.important .important-badge {
            display: inline-block;
            background: rgba(252, 211, 77, 0.15);
            color: #fcd34d;
            font-weight: 700;
            font-size: 0.6rem;
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(252, 211, 77, 0.15);
        }
        
        .news-card-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        
        .news-card-content {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .news-card-title {
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 700;
            transition: color 0.3s ease;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .news-card:hover .news-card-title {
            color: #fcd34d;
        }
        
        .news-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
        }
        
        /* Category Badges - Each category has its own color */
        .category-badge {
            padding: 0.15rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .category-badge.badge-patch { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.2); }
        .category-badge.badge-event { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
        .category-badge.badge-announcement { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .category-badge.badge-general { background: rgba(139, 92, 246, 0.2); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.2); }
        .category-badge.badge-maintenance { background: rgba(251, 191, 36, 0.2); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.2); }
        .category-badge.badge-update { background: rgba(236, 72, 153, 0.2); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.2); }
        
        .news-card-excerpt {
            color: #9ca3af;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0;
            flex: 1;
        }
        
        /* Single Article */
        .news-single {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .news-single-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        
        .news-single-title {
            color: #ffffff;
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1rem;
        }
        
        .news-single-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            color: #6b7280;
            font-size: 0.9rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .news-single-content {
            color: #d1d5db;
            font-size: 1.05rem;
            line-height: 1.8;
        }
        
        .news-single-content p {
            margin-bottom: 1.2rem;
        }
        
        .news-single-back {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.6rem 1.8rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #9ca3af;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .news-single-back:hover {
            background: rgba(252, 211, 77, 0.1);
            border-color: rgba(252, 211, 77, 0.2);
            transform: translateX(-5px);
            color: #fcd34d;
        }
        
        /* Pagination */
        .pagination-link {
            padding: 0.5rem 1.2rem;
            background: rgba(18, 24, 34, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: #9ca3af;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .pagination-link:hover {
            background: rgba(252, 211, 77, 0.08);
            border-color: rgba(252, 211, 77, 0.15);
            color: #fcd34d;
            transform: translateY(-2px);
        }
        
        .pagination-link.active {
            background: rgba(252, 211, 77, 0.12);
            border-color: rgba(252, 211, 77, 0.2);
            color: #fcd34d;
        }
        
        .no-news {
            text-align: center;
            color: #6b7280;
            font-size: 1.2rem;
            padding: 3rem 0;
        }
        
        .no-news i {
            font-size: 3rem;
            color: #374151;
            display: block;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .news-single-title {
                font-size: 1.6rem;
            }
            
            .news-single-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .category-tab {
                padding: 0.4rem 1rem;
                font-size: 0.8rem;
            }
            
            .tabs-container {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>

<div class="news-content min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        
        <!-- Main Container -->
        <div class="glass-container p-6 md:p-10">
            
            <?php if ($is_single): ?>
                <!-- Single News Article View -->
                <article class="news-single">
                    <?php if (!empty($news['image_url'])): ?>
                        <img src="<?php echo $base_path . htmlspecialchars($news['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($news['title']); ?>" 
                             class="news-single-image"
                             onerror="this.src='<?php echo $base_path . htmlspecialchars($default_image_url); ?>'">
                    <?php else: ?>
                        <img src="<?php echo $base_path . htmlspecialchars($default_image_url); ?>" 
                             alt="<?php echo htmlspecialchars($news['title']); ?>" 
                             class="news-single-image">
                    <?php endif; ?>
                    
                    <h1 class="news-single-title">
                        <?php echo htmlspecialchars($news['title']); ?>
                        <?php if ($news['is_important']): ?>
                            <span class="important-badge ml-2"><i class="fas fa-star mr-1"></i> Important</span>
                        <?php endif; ?>
                    </h1>
                    
                    <div class="news-single-meta">
                        <span class="category-badge badge-<?php echo htmlspecialchars($news['category']); ?>">
                            <i class="fas fa-tag mr-1"></i> <?php echo translate('category_' . $news['category'], ucfirst(htmlspecialchars($news['category']))); ?>
                        </span>
                        <span><i class="far fa-calendar-alt mr-1"></i> <?php echo date('F j, Y', strtotime($news['post_date'])); ?></span>
                        <span><i class="far fa-user mr-1"></i> <?php echo sprintf(translate('posted_by', 'Posted by %s'), htmlspecialchars($news['posted_by'])); ?></span>
                    </div>
                    
                    <div class="news-single-content">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </div>
                    
                    <a href="<?php echo $base_path; ?>news" class="news-single-back">
                        <i class="fas fa-arrow-left mr-2"></i> <?php echo translate('back_to_news', 'Back to News'); ?>
                    </a>
                </article>
                
            <?php else: ?>
                <!-- News List -->
                
                <!-- Centered Title -->
                <div class="text-center mb-8">
                    <h1 class="text-4xl md:text-5xl font-extrabold text-amber-400 tracking-tight">
                        <?php echo $site_title_name ." ". translate('page_title_list', 'News'); ?>
                    </h1>
                    <p class="text-gray-400 mt-2 text-sm"><?php echo translate('news_subtitle', 'Stay updated with the latest news and announcements'); ?></p>
                </div>

                <!-- Category Filter Tabs - With Container -->
                <div class="tabs-container">
                    <a href="<?php echo $base_path; ?>news" 
                       class="category-tab tab-all <?php echo empty($category_filter) ? 'active' : ''; ?>"
                       style="--tab-bg: rgba(252, 211, 77, 0.14); --tab-border: rgba(252, 211, 77, 0.34); --tab-text: #fcd34d; --tab-hover: rgba(252, 211, 77, 0.28);">
                        <i class="fas fa-th-list"></i> <?php echo translate('tab_all', 'All'); ?>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <?php $tab_color = $category_colors[$cat] ?? ['bg' => 'rgba(148, 163, 184, 0.14)', 'border' => 'rgba(148, 163, 184, 0.34)', 'text' => '#cbd5e1', 'hover' => 'rgba(148, 163, 184, 0.26)']; ?>
                        <a href="<?php echo $base_path; ?>news?category=<?php echo htmlspecialchars($cat); ?>" 
                           class="category-tab tab-<?php echo htmlspecialchars($cat); ?> <?php echo $category_filter === $cat ? 'active' : ''; ?>"
                           style="--tab-bg: <?php echo htmlspecialchars($tab_color['bg']); ?>; --tab-border: <?php echo htmlspecialchars($tab_color['border']); ?>; --tab-text: <?php echo htmlspecialchars($tab_color['text']); ?>; --tab-hover: <?php echo htmlspecialchars($tab_color['hover']); ?>;">
                            <i class="fas fa-tag"></i> <?php echo translate('category_' . $cat, ucfirst(htmlspecialchars($cat))); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($result->num_rows === 0): ?>
                    <div class="no-news">
                        <i class="fas fa-scroll"></i>
                        <?php echo translate('no_news', 'No news available at this time.'); ?>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php while ($news = $result->fetch_assoc()): ?>
                            <a href="<?php echo $base_path; ?>news?slug=<?php echo htmlspecialchars($news['slug']); ?>" 
                               class="news-card card-<?php echo htmlspecialchars($news['category']); ?> <?php echo $news['is_important'] ? 'important' : ''; ?>">
                                
                                <?php if (!empty($news['image_url'])): ?>
                                    <img src="<?php echo $base_path . htmlspecialchars($news['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                         class="news-card-image"
                                         onerror="this.src='<?php echo $base_path . htmlspecialchars($default_image_url); ?>'">
                                <?php else: ?>
                                    <img src="<?php echo $base_path . htmlspecialchars($default_image_url); ?>" 
                                         alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                         class="news-card-image">
                                <?php endif; ?>
                                
                                <div class="news-card-content">
                                    <div class="news-card-meta">
                                        <span class="category-badge badge-<?php echo htmlspecialchars($news['category']); ?>">
                                            <?php echo translate('category_' . $news['category'], ucfirst(htmlspecialchars($news['category']))); ?>
                                        </span>
                                        <span><i class="far fa-calendar-alt mr-1"></i> <?php echo date('M j, Y', strtotime($news['post_date'])); ?></span>
                                    </div>
                                    
                                    <h2 class="news-card-title">
                                        <?php echo htmlspecialchars($news['title']); ?>
                                        <?php if ($news['is_important']): ?>
                                            <span class="important-badge ml-1"><i class="fas fa-star mr-1"></i> Important</span>
                                        <?php endif; ?>
                                    </h2>
                                    
                                    <p class="news-card-excerpt"><?php echo htmlspecialchars($news['excerpt']); ?>...</p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center gap-2 mt-8 flex-wrap">
                            <?php if ($current_page > 1): ?>
                                <a href="<?php echo $base_path; ?>news?page=<?php echo $current_page - 1; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" 
                                   class="pagination-link">
                                    <i class="fas fa-chevron-left mr-1"></i> <?php echo translate('pagination_prev', 'Prev'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="<?php echo $base_path; ?>news?page=<?php echo $i; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" 
                                   class="pagination-link <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="<?php echo $base_path; ?>news?page=<?php echo $current_page + 1; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" 
                                   class="pagination-link">
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
