<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'page-manager';

require_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($langCode); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('title_page_manager', 'Page Manager'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/settings_navbar.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #212529;
            color: #212529;
        }
        .container-fluid {
            padding: 0;
        }
        .main-content {
            padding-top: 80px;
            min-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }
        .content {
            padding: 1.5rem;
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin: 1rem;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .coming-soon {
            max-width: 600px;
            margin: 0 auto;
        }
        .coming-soon h2 {
            font-size: 2.2rem;
            margin-bottom: 1rem;
            color: #343a40;
        }
        .coming-soon p {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        .construction-icon {
            font-size: 4rem;
            color: #ffc107;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .main-content {
                padding-top: 60px;
                padding-left: 0;
                padding-right: 0;
            }
            .content {
                margin: 0.5rem;
                padding: 1rem;
            }
            .coming-soon h2 {
                font-size: 1.8rem;
            }
            .construction-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include $project_root . 'includes/admin_sidebar.php'; ?>
            <main class="col-md-10 main-content">
                <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>
                <div class="content">
                    <div class="coming-soon">
                        <div class="construction-icon"><?php echo translate('under_construction_icon', 'Under Construction'); ?></div>
                        <h2><?php echo translate('page_under_construction', 'Page Under Construction'); ?></h2>
                        <p><?php echo translate('coming_soon_message', 'This feature is coming soon. Stay tuned!'); ?></p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <?php include $project_root . 'includes/footer.php'; ?>
</body>
</html>