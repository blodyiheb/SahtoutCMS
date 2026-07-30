<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php'; // Include language file for translations
$page_class = 'news';
include $project_root . 'includes/header.php';

$default_image_url = 'img/newsimg/news.png';
$items_per_page = 5;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$is_single = !empty($slug);

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

    $total_query = "SELECT COUNT(*) as total FROM server_news";
    $total_result = $site_db->query($total_query);
    $total_rows = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $items_per_page);
    $current_page = min($current_page, $total_pages);

    $query = "SELECT id, title, slug, LEFT(content, 200) as excerpt, posted_by, 
              post_date, image_url, is_important, category 
              FROM server_news 
              ORDER BY is_important DESC, post_date DESC
              LIMIT ?, ?";
    $stmt = $site_db->prepare($query);
    $stmt->bind_param('ii', $offset, $items_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
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
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/news.css">
</head>
<style>
     :root{
            --bg-news:url('<?php echo $base_path; ?>img/backgrounds/bg-news.jpg');
        }
</style>
<body class="news <?php echo $is_single ? 'single-view' : 'list-view'; ?>">
    <div class="main-content">
        <div class="wow-news-container">
            <?php if ($is_single): ?>
                <!-- Single News Article View -->
                <article class="news-single <?php echo $news['is_important'] ? 'important' : ''; ?>">
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
                    <h1 class="news-single-title"><?php echo htmlspecialchars($news['title']); ?></h1>
                    <div class="news-single-meta">
                        <span class="category <?php echo htmlspecialchars($news['category']); ?>">
                            <?php echo translate('category_' . $news['category'], ucfirst(htmlspecialchars($news['category']))); ?>
                        </span>
                        <span class="date"><?php echo date('M j, Y', strtotime($news['post_date'])); ?></span>
                        <span class="author"><?php echo sprintf(translate('posted_by', 'Posted by %s'), htmlspecialchars($news['posted_by'])); ?></span>
                    </div>
                    <div class="news-single-content">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </div>
                    <a href="<?php echo $base_path; ?>news" class="news-single-back"><?php echo translate('back_to_news', '← Back to News'); ?></a>
                </article>
            <?php else: ?>
                <!-- News List -->
                <h1 class="wow-news-title"><?php echo $site_title_name ." ".translate('page_title_list', '- News'); ?></h1>
                <?php if ($result->num_rows === 0): ?>
                    <div class="no-news"><?php echo translate('no_news', 'No news available at this time.'); ?></div>
                <?php else: ?>
                    <div class="news-list">
                        <?php while ($news = $result->fetch_assoc()): ?>
                            <a href="<?php echo $base_path; ?>news?slug=<?php echo htmlspecialchars($news['slug']); ?>" class="news-link">
                                <article class="news-item <?php echo $news['is_important'] ? 'important' : ''; ?>">
                                    <?php if (!empty($news['image_url'])): ?>
                                        <img src="<?php echo $base_path . htmlspecialchars($news['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                             class="news-image"
                                             onerror="this.src='<?php echo $base_path . htmlspecialchars($default_image_url); ?>'">
                                    <?php else: ?>
                                        <img src="<?php echo $base_path . htmlspecialchars($default_image_url); ?>" 
                                             alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                             class="news-image">
                                    <?php endif; ?>
                                    <div class="news-content">
                                        <h2><?php echo htmlspecialchars($news['title']); ?></h2>
                                        <div class="news-meta">
                                            <span class="category <?php echo htmlspecialchars($news['category']); ?>">
                                                <?php echo translate('category_' . $news['category'], ucfirst(htmlspecialchars($news['category']))); ?>
                                            </span>
                                            <span class="date"><?php echo date('M j, Y', strtotime($news['post_date'])); ?></span>
                                            <span class="author"><?php echo sprintf(translate('posted_by', 'Posted by %s'), htmlspecialchars($news['posted_by'])); ?></span>
                                        </div>
                                        <p class="news-excerpt"><?php echo htmlspecialchars($news['excerpt']); ?>...</p>
                                    </div>
                                </article>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="news-pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="<?php echo $base_path; ?>news?page=<?php echo $current_page - 1; ?>" 
                                   class="pagination-link" 
                                   aria-label="<?php echo translate('pagination_previous', 'Previous page'); ?>">« <?php echo translate('pagination_prev', 'Prev'); ?></a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="<?php echo $base_path; ?>news?page=<?php echo $i; ?>" 
                                   class="pagination-link <?php echo $i == $current_page ? 'active' : ''; ?>" 
                                   aria-label="<?php echo sprintf(translate('pagination_go_to_page', 'Go to page %s'), $i); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($current_page < $total_pages): ?>
                                <a href="<?php echo $base_path; ?>news?page=<?php echo $current_page + 1; ?>" 
                                   class="pagination-link" 
                                   aria-label="<?php echo translate('pagination_next_label', 'Next page'); ?>"><?php echo translate('pagination_next', 'Next'); ?> »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php 
    if (!$is_single) {
        $stmt->close();
    }
    if (isset($site_db)) {
        $site_db->close();
    }
    ?>
    <?php include $project_root . 'includes/footer.php'; ?>
</body>
</html>