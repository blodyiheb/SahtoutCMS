<?php
define('ALLOWED_ACCESS', true);
require_once '../includes/session.php';
require_once '../languages/language.php'; // Include language detection
$page_class = 'woltk';

// Handle download request if submitted
if (isset($_GET['file'])) {
    // Immediately clear any existing output buffers
    while (ob_get_level()) ob_end_clean();
    
    $file = basename($_GET['file']);
    $path = __DIR__ . '/files/' . $file;
    
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
        header('Location: woltk.php');
        exit;
    }
}

include_once '../includes/header.php';
?>

<style>
    /* Your original WoW styling */
    :root {
        --wow-gold: #e4c062;
        --wow-brown: #3a2a1a;
        --wow-dark-brown: #1a1208;
        --wow-light-brown: #7a5c3c;
        --wow-red: #a52a2a;
        --wow-blue: #3a6ea5;
    }
    
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    
    body {
        display: flex;
        flex-direction: column;
        background: url('https://i.imgur.com/YQZQZ9q.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Arial', sans-serif;
        color: var(--wow-gold);
        text-shadow: 1px 1px 2px #000;
        min-height: 100vh;
    }
    
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px 0;
    }
    
    .container {
        background-color: rgba(0, 0, 0, 0.7);
        border: 2px solid var(--wow-gold);
        border-radius: 5px;
        padding: 2rem;
        margin: auto;
        width: 80%;
        max-width: 600px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.8);
    }
    
    h1 {
        font-family: 'Times New Roman', serif;
        font-size: 2.5rem;
        color: var(--wow-gold);
        text-align: center;
        margin-bottom: 1.5rem;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    
    .error {
        color: var(--wow-red);
        background-color: rgba(0, 0, 0, 0.5);
        padding: 0.5rem;
        margin-bottom: 1rem;
        border-left: 3px solid var(--wow-red);
        text-align: center;
    }
    
    /* Enhanced Download Button Styles */
    .download-button {
        background: linear-gradient(to bottom, #8B4513, #A0522D);
        color: var(--wow-gold);
        border: 2px solid var(--wow-gold);
        padding: 1rem 2rem;
        font-size: 1.5rem;
        font-weight: bold;
        border-radius: 5px;
        cursor: pointer;
        text-transform: uppercase;
        transition: all 0.3s ease;
        margin-top: 1.5rem;
        display: block;
        width: 100%;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .download-button:hover {
        background: linear-gradient(to bottom, #A0522D, #8B4513);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(228, 192, 98, 0.5);
        text-shadow: 0 0 5px var(--wow-gold);
    }
    
    .download-button i {
        margin-right: 10px;
    }
    
    .download-button::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255, 255, 255, 0.1);
        transform: rotate(45deg);
        pointer-events: none;
    }
    
    .download-button:hover::after {
        animation: shine 1.5s infinite;
    }
    
    @keyframes shine {
        0% { left: -100%; top: -100%; }
        100% { left: 100%; top: 100%; }
    }
    
    /* File info styling */
    .file-info {
        background: rgba(58, 42, 26, 0.5);
        padding: 1rem;
        border-left: 3px solid var(--wow-gold);
        margin: 1.5rem 0;
    }
    
    .file-info p {
        margin: 0.5rem 0;
        display: flex;
        align-items: center;
    }
    
    .file-info i {
        width: 25px;
        text-align: center;
        margin-right: 10px;
        color: var(--wow-gold);
    }
    
    /* Footer styling remains the same */
    footer {
        background-color: rgba(0, 0, 0, 0.8);
        color: var(--wow-gold);
        padding: 1rem;
        text-align: center;
        border-top: 1px solid var(--wow-gold);
        margin-top: auto;
    }
</style>

<div class="main-content">
    <div class="container wow-decoration">
        <h1><?php echo translate('download_title', 'Choose a file to download'); ?></h1>
        
        <?php if (isset($_SESSION['download_error'])): ?>
            <div class="error"><?php echo htmlspecialchars($_SESSION['download_error'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php unset($_SESSION['download_error']); ?>
        <?php endif; ?>
        
        <div class="file-info">
            <p><i class="fas fa-file-archive"></i> <?php echo translate('download_file_name', 'Wrath of the Lich King Client'); ?></p>
            <p><i class="fas fa-download"></i> <?php echo translate('download_file_size', 'Size'); ?>: <?php 
                echo file_exists(__DIR__ . '/files/wow_woltk.zip') ? 
                round(filesize(__DIR__ . '/files/wow_woltk.zip') / (1024 * 1024), 2) . ' MB' : 
                translate('download_size_unknown', 'Unknown'); 
            ?></p>
            <p><i class="fas fa-exclamation-triangle"></i> <?php echo translate('download_space_required', 'Requires 35GB free space'); ?></p>
        </div>
        
        <form method="get">
            <input type="hidden" name="file" value="wow_woltk.zip">
            <button type="submit" class="download-button">
                <i class="fas fa-dragon"></i> <?php echo translate('download_button', 'DOWNLOAD NOW'); ?>
            </button>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>