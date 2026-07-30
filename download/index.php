<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php'; // Include language detection

// Handle download request if submitted
if (isset($_GET['file'])) {
    // Immediately clear any existing output buffers
    while (ob_get_level()) ob_end_clean();
    
    $file = basename($_GET['file']);
    $path = $project_root . 'download/files/' . $file;
    
    if (file_exists($path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        readfile($path);
        exit;
    } else {
        $_SESSION['download_error'] = translate('download_error_file_not_found', 'File not found');
        header("Location: {$base_path}download/woltk.php");
        exit;
    }
}

include_once $project_root . 'includes/header.php';
?>
<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/download/index.css">
<title><?php echo $site_title_name ." ". translate('download_title', 'Download'); ?></title>

<style>
    :root{
            --bg-download:url('<?php echo $base_path; ?>img/backgrounds/bg-download.jpg');
        }
</style>
<div class="main-content">
    <div class="container wow-decoration">
        <h1><?php echo translate('download_title_h1', 'Choose a file to download'); ?></h1>
        
        <?php if (isset($_SESSION['download_error'])): ?>
            <div class="error"><?php echo htmlspecialchars($_SESSION['download_error'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php unset($_SESSION['download_error']); ?>
        <?php endif; ?>
        
        <div class="file-info">
            <p><i class="fas fa-file-archive"></i> <?php echo translate('download_file_name', 'Wrath of the Lich King Client'); ?></p>
            <p><i class="fas fa-download"></i> <?php echo translate('download_file_size', 'Size'); ?>: <?php 
                echo file_exists($project_root . 'download/files/wow_woltk.zip') ? 
                round(filesize($project_root . 'download/files/wow_woltk.zip') / (1024 * 1024), 2) . ' MB' : 
                translate('download_size_unknown', 'Unknown'); 
            ?></p>
            <p><i class="fas fa-exclamation-triangle"></i> <?php echo translate('download_space_required', 'Requires 35GB free space'); ?></p>
        </div>
        
        <form method="get" action="<?php echo $base_path; ?>download">
            <input type="hidden" name="file" value="wow_woltk.zip">
            <button type="submit" class="download-button">
                <i class="fas fa-dragon"></i> <?php echo translate('download_button', 'DOWNLOAD NOW'); ?>
            </button>
        </form>
    </div>
</div>

<?php include_once $project_root . 'includes/footer.php'; ?>