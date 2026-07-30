<?php
define('ALLOWED_ACCESS', true);

// Include session, language, and paths
require_once __DIR__ . '/../../includes/paths.php';
require_once $project_root . 'includes/session.php'; // Includes config.php
require_once $project_root . 'languages/language.php'; // Include translation system
require_once $project_root . 'includes/config.settings.php';
$page_class = 'characters';

define('DB_AUTH', $db_auth_name);
define('DB_CHAR', $db_char_name);
define('DB_WORLD', $db_world_name);
define('DB_SITE', $db_site_name);
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: {$base_path}login");
    exit;
}

// Use databases from config.php
global $site_db, $auth_db, $char_db;

// Check user role from user_currencies
$user_id = $_SESSION['user_id'];
$role_query = "SELECT role FROM " . DB_SITE . ".user_currencies WHERE account_id = ?";
$stmt = $site_db->prepare($role_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION['role'] = $row['role'];
} else {
    $_SESSION['role'] = 'player';
}
$stmt->close();

// Restrict access to admin or moderator only
if (!in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

// Handle search, online filter, level filter, and sort
$search_char_name = '';
$search_username = '';
$online_filter = '';
$min_level = '';
$max_level = '';
$sort_id = 'asc';
if (isset($_GET['search_char_name']) && !empty(trim($_GET['search_char_name']))) {
    $search_char_name = trim($_GET['search_char_name']);
}
if (isset($_GET['search_username']) && !empty(trim($_GET['search_username']))) {
    $search_username = trim($_GET['search_username']);
}
if (isset($_GET['online_filter']) && in_array($_GET['online_filter'], ['online', 'offline', ''])) {
    $online_filter = $_GET['online_filter'];
}
if (isset($_GET['min_level']) && trim($_GET['min_level']) !== '' && is_numeric($_GET['min_level'])) {
    $min_level = max(1, min(255, (int)$_GET['min_level']));
}
if (isset($_GET['max_level']) && trim($_GET['max_level']) !== '' && is_numeric($_GET['max_level'])) {
    $max_level = max(1, min(255, (int)$_GET['max_level']));
}
if (isset($_GET['sort_id']) && in_array($_GET['sort_id'], ['asc', 'desc'])) {
    $sort_id = $_GET['sort_id'];
}

$chars_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $chars_per_page;

// Predefined teleport locations
$predefined_locations = [
    'stormwind' => [
        'name' => 'Stormwind City',
        'map' => 0, // Eastern Kingdoms
        'x' => -8913.23,
        'y' => 554.633,
        'z' => 94.7944,
        'o' => 0.0
    ],
    'orgrimmar' => [
        'name' => 'Orgrimmar',
        'map' => 1, // Kalimdor
        'x' => 1552.5,
        'y' => -4420.66,
        'z' => 8.94802,
        'o' => 0.0
    ],
    'shattrath' => [
        'name' => 'Shattrath City',
        'map' => 530, // Outland
        'x' => -1850.21,
        'y' => 5435.82,
        'z' => -10.9614,
        'o' => 3.40339
    ],
    'dalaran' => [
        'name' => 'Dalaran (Northrend)',
        'map' => 571, // Northrend
        'x' => 5804.15,
        'y' => 624.771,
        'z' => 647.767,
        'o' => 1.64
    ],
    'gm_island' => [
        'name' => 'GM Island',
        'map' => 1, // Kalimdor
        'x' => 16222.1,
        'y' => 16252.1,
        'z' => 12.5872,
        'o' => 0.0
    ]
];

// Handle form submissions
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manage_character') {
    if (!isset($_POST['csrf_token'])) {
        $update_message = '<div class="alert alert-danger">' . translate('admin_chars_csrf_missing', 'CSRF token is missing.') . '</div>';
    } elseif ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $update_message = '<div class="alert alert-danger">' . translate('admin_chars_csrf_error', 'CSRF token validation failed.') . '</div>';
    } else {
        $guid = (int)$_POST['guid'];
        $char_action = $_POST['char_action'] ?? '';
        $success = false;

        // Fetch character name and online status for feedback
        $stmt = $char_db->prepare("SELECT name, online FROM " . DB_CHAR . ".characters WHERE guid = ?");
        $stmt->bind_param("i", $guid);
        $stmt->execute();
        $char = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$char) {
            $update_message = '<div class="alert alert-danger">' . translate('admin_chars_not_found', 'Character not found.') . '</div>';
        } else {
            $char_name = $char['name'];

            if ($char_action === 'add_gold') {
                if ($char['online'] == 0) {
                    $gold = isset($_POST['gold']) ? (int)$_POST['gold'] : 0;
                    if ($gold >= 0) {
                        $gold_in_copper = $gold * 10000; // Convert gold to copper
                        $stmt = $char_db->prepare("UPDATE " . DB_CHAR . ".characters SET money = money + ? WHERE guid = ?");
                        $stmt->bind_param("ii", $gold_in_copper, $guid);
                        if ($stmt->execute()) {
                            $success = true;
                            $update_message = '<div class="alert alert-success">' . sprintf(translate('admin_chars_add_gold_success', 'Added %d gold to %s successfully.'), $gold, htmlspecialchars($char_name)) . '</div>';
                        } else {
                            $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_chars_add_gold_failed', 'Failed to add gold to %s.'), htmlspecialchars($char_name)) . '</div>';
                        }
                        $stmt->close();
                    } else {
                        $update_message = '<div class="alert alert-danger">' . translate('admin_chars_gold_negative', 'Gold amount must be a non-negative number.') . '</div>';
                    }
                } else {
                    $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_chars_gold_online', 'Cannot add gold to %s: Character is online.'), htmlspecialchars($char_name)) . '</div>';
                }
            } elseif ($char_action === 'change_level') {
                if ($char['online'] == 0) {
                    $level = isset($_POST['level']) ? (int)$_POST['level'] : 0;
                    if ($level >= 1 && $level <= 255) {
                        $stmt = $char_db->prepare("UPDATE " . DB_CHAR . ".characters SET level = ? WHERE guid = ?");
                        $stmt->bind_param("ii", $level, $guid);
                        if ($stmt->execute()) {
                            $success = true;
                            $update_message = '<div class="alert alert-success">' . sprintf(translate('admin_chars_level_success', 'Level changed to %d for %s successfully.'), $level, htmlspecialchars($char_name)) . '</div>';
                        } else {
                            $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_chars_level_failed', 'Failed to change level for %s.'), htmlspecialchars($char_name)) . '</div>';
                        }
                        $stmt->close();
                    } else {
                        $update_message = '<div class="alert alert-danger">' . translate('admin_chars_level_invalid', 'Level must be between 1 and 255.') . '</div>';
                    }
                } else {
                    $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_chars_level_online', 'Cannot change level for %s: Character is online.'), htmlspecialchars($char_name)) . '</div>';
                }
            } elseif ($char_action === 'teleport') {
                if ($char['online'] == 0) {
                    $map = isset($_POST['map']) ? (int)$_POST['map'] : 0;
                    $x = isset($_POST['x']) ? (float)$_POST['x'] : 0;
                    $y = isset($_POST['y']) ? (float)$_POST['y'] : 0;
                    $z = isset($_POST['z']) ? (float)$_POST['z'] : 0;
                    if ($map >= 0 && $x != 0 && $y != 0) {
                        $stmt = $char_db->prepare("UPDATE " . DB_CHAR . ".characters SET map = ?, position_x = ?, position_y = ?, position_z = ? WHERE guid = ?");
                        $stmt->bind_param("idddi", $map, $x, $y, $z, $guid);
                        if ($stmt->execute()) {
                            $success = true;
                            $map_name = isset($map_names[$map]) ? $map_names[$map] : $map;
                            $update_message = '<div class="alert alert-success">' . sprintf(translate('admin_chars_teleport_success', 'Teleported %s to %s (%.2f, %.2f, %.2f).'), htmlspecialchars($char_name), htmlspecialchars($map_name), $x, $y, $z) . '</div>';
                        } else {
                            $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_chars_teleport_failed', 'Failed to teleport %s.'), htmlspecialchars($char_name)) . '</div>';
                        }
                        $stmt->close();
                    } else {
                        $update_message = '<div class="alert alert-danger">' . translate('admin_chars_teleport_invalid', 'Invalid coordinates. Map must be ≥ 0 and X/Y cannot be 0.') . '</div>';
                    }
                } else {
                    $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_chars_teleport_online', 'Cannot teleport %s: Character is online.'), htmlspecialchars($char_name)) . '</div>';
                }
            } elseif ($char_action === 'teleport_directly') {
                if ($char['online'] == 0) {
                    $location = $_POST['predefined_location'] ?? '';
                    if (isset($predefined_locations[$location])) {
                        $loc = $predefined_locations[$location];
                        $stmt = $char_db->prepare("UPDATE " . DB_CHAR . ".characters SET map = ?, position_x = ?, position_y = ?, position_z = ?, orientation = ? WHERE guid = ?");
                        $stmt->bind_param("iddddi", $loc['map'], $loc['x'], $loc['y'], $loc['z'], $loc['o'], $guid);
                        if ($stmt->execute()) {
                            $success = true;
                            $update_message = '<div class="alert alert-success">' . sprintf(translate('admin_chars_teleport_direct_success', 'Teleported %s to %s.'), htmlspecialchars($char_name), htmlspecialchars($loc['name'])) . '</div>';
                        } else {
                            $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_chars_teleport_failed', 'Failed to teleport %s.'), htmlspecialchars($char_name)) . '</div>';
                        }
                        $stmt->close();
                    } else {
                        $update_message = '<div class="alert alert-danger">' . translate('admin_chars_location_invalid', 'Invalid location selected.') . '</div>';
                    }
                } else {
                    $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_chars_teleport_online', 'Cannot teleport %s: Character is online.'), htmlspecialchars($char_name)) . '</div>';
                }
            }
        }
    }
}

// Count total characters for pagination
$count_query = "SELECT COUNT(*) as total FROM " . DB_CHAR . ".characters c JOIN " . DB_AUTH . ".account a ON c.account = a.id WHERE 1=1";
$params = [];
$types = '';
if ($search_char_name) {
    $count_query .= " AND LOWER(c.name) LIKE LOWER(?)";
    $params[] = "%$search_char_name%";
    $types .= 's';
}
if ($search_username) {
    $count_query .= " AND a.username LIKE ?";
    $params[] = "%$search_username%";
    $types .= 's';
}
if ($online_filter !== '') {
    $count_query .= " AND c.online = ?";
    $params[] = $online_filter === 'online' ? 1 : 0;
    $types .= 'i';
}
if ($min_level !== '') {
    $count_query .= " AND c.level >= ?";
    $params[] = $min_level;
    $types .= 'i';
}
if ($max_level !== '') {
    $count_query .= " AND c.level <= ?";
    $params[] = $max_level;
    $types .= 'i';
}
$stmt = $char_db->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_chars = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$total_pages = ceil($total_chars / $chars_per_page);

// Fetch characters
$chars_query = "SELECT c.guid, c.account, c.name, c.race, c.class, c.gender, c.level, c.map, c.online, a.username 
                FROM " . DB_CHAR . ".characters c JOIN " . DB_AUTH . ".account a ON c.account = a.id WHERE 1=1";
$params = [];
$types = '';
if ($search_char_name) {
    $chars_query .= " AND LOWER(c.name) LIKE LOWER(?)";
    $params[] = "%$search_char_name%";
    $types .= 's';
}
if ($search_username) {
    $chars_query .= " AND a.username LIKE ?";
    $params[] = "%$search_username%";
    $types .= 's';
}
if ($online_filter !== '') {
    $chars_query .= " AND c.online = ?";
    $params[] = $online_filter === 'online' ? 1 : 0;
    $types .= 'i';
}
if ($min_level !== '') {
    $chars_query .= " AND c.level >= ?";
    $params[] = $min_level;
    $types .= 'i';
}
if ($max_level !== '') {
    $chars_query .= " AND c.level <= ?";
    $params[] = $max_level;
    $types .= 'i';
}
$chars_query .= " ORDER BY c.guid " . ($sort_id === 'desc' ? 'DESC' : 'ASC') . " LIMIT ? OFFSET ?";
$params[] = $chars_per_page;
$params[] = $offset;
$types .= 'ii';
$stmt = $char_db->prepare($chars_query);
if (!$stmt) {
    $_SESSION['debug_errors'][] = translate('admin_chars_db_error', 'Failed to prepare query: ') . $char_db->error;
    header("Location: {$base_path}login?error=database_error");
    exit();
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$chars_result = $stmt->get_result();
$characters = [];
while ($char = $chars_result->fetch_assoc()) {
    $characters[] = $char;
}
$stmt->close();

// Map ID to name mapping, including instances
$map_names = [
    // World Maps
    0 => 'Eastern Kingdoms',
    1 => 'Kalimdor',
    530 => 'Outland',
    571 => 'Northrend',
    // Instances
    33 => 'Shadowfang Keep',
    34 => 'The Stockade',
    36 => 'Deadmines',
    43 => 'Wailing Caverns',
    47 => 'Razorfen Kraul',
    48 => 'Blackfathom Deeps',
    70 => 'Uldaman',
    90 => 'Gnomeregan',
    109 => 'Sunken Temple',
    129 => 'Razorfen Downs',
    189 => 'Scarlet Monastery',
    209 => 'Zulfarrak',
    229 => 'Blackrock Spire',
    230 => 'Blackrock Depths',
    249 => 'Onyxias Lair',
    269 => 'The Black Morass',
    289 => 'Scholomance',
    309 => 'Zulgurub',
    329 => 'Stratholme',
    349 => 'Maraudon',
    389 => 'Ragefire Chasm',
    409 => 'Molten Core',
    429 => 'Dire Maul',
    469 => 'Blackwing Lair',
    509 => 'Ruins of Ahnqiraj',
    531 => 'Temple of Ahnqiraj',
    532 => 'Karazhan',
    533 => 'Naxxramas',
    534 => 'Hyjal',
    540 => 'Shattered Halls',
    542 => 'Blood Furnace',
    543 => 'Hellfire Ramparts',
    544 => 'Magtheridons Lair',
    545 => 'Steam Vault',
    546 => 'The Underbog',
    547 => 'The Slave Pens',
    548 => 'Serpent Shrine',
    550 => 'The Eye',
    552 => 'Arcatraz',
    553 => 'The Botanica',
    554 => 'Mechanar',
    555 => 'Shadow Labyrinth',
    556 => 'Sethekk Halls',
    557 => 'Mana Tombs',
    558 => 'Auchenai Crypts',
    560 => 'Old Hillsbrad',
    564 => 'Black Temple',
    565 => 'Gruuls Lair',
    568 => 'Zulaman',
    574 => 'Utgarde Keep',
    575 => 'Utgarde Pinnacle',
    576 => 'Nexus',
    578 => 'Oculus',
    580 => 'Sunwell Plateau',
    585 => 'Magisters Terrace',
    595 => 'Culling of Stratholme',
    599 => 'Halls of Stone',
    600 => 'Drak Tharon Keep',
    601 => 'Azjol Nerub',
    602 => 'Halls of Lightning',
    603 => 'Ulduar',
    604 => 'Gundrak',
    608 => 'Violet Hold',
    615 => 'Obsidian Sanctum',
    616 => 'Eye of Eternity',
    619 => 'Ahnkahet',
    624 => 'Vault of Archavon',
    631 => 'Icecrown Citadel',
    632 => 'Forge of Souls',
    649 => 'Trial of the Crusader',
    650 => 'Trial of the Champion',
    658 => 'Pit of Saron',
    668 => 'Halls of Reflection',
    724 => 'Ruby Sanctum'
];

// Helper functions for icons and status
function getRaceIcon($race, $gender) {
    global $base_path;
    $races = [
        1 => 'human', 2 => 'orc', 3 => 'dwarf', 4 => 'nightelf',
        5 => 'undead', 6 => 'tauren', 7 => 'gnome', 8 => 'troll',
        10 => 'bloodelf', 11 => 'draenei'
    ];
    $gender_folder = ($gender == 1) ? 'female' : 'male';
    $race_name = $races[$race] ?? 'default';
    $image = $race_name . '.png';
    return '<img src="' . $base_path . 'img/accountimg/race/' . $gender_folder . '/' . $image . '" alt="' . translate('admin_chars_race_icon_alt', 'Race Icon') . '" class="account-sahtout-icon">';
}

function getClassIcon($class) {
    global $base_path;
    $icons = [
        1 => 'warrior.webp', 2 => 'paladin.webp', 3 => 'hunter.webp', 4 => 'rogue.webp',
        5 => 'priest.webp', 6 => 'deathknight.webp', 7 => 'shaman.webp', 8 => 'mage.webp',
        9 => 'warlock.webp', 11 => 'druid.webp'
    ];
    return '<img src="' . $base_path . 'img/accountimg/class/' . ($icons[$class] ?? 'default.jpg') . '" alt="' . translate('admin_chars_class_icon_alt', 'Class Icon') . '" class="account-sahtout-icon">';
}

function getFactionIcon($race) {
    global $base_path;
    $allianceRaces = [1, 3, 4, 7, 11];
    $faction = in_array($race, $allianceRaces) ? 'alliance.png' : 'horde.png';
    return '<img src="' . $base_path . 'img/accountimg/faction/' . $faction . '" alt="' . translate('admin_chars_faction_icon_alt', 'Faction Icon') . '" class="account-sahtout-icon">';
}

function getOnlineStatus($online) {
    return $online ? "<span style='color: #55ff55'>" . translate('admin_chars_status_online', 'Online') . "</span>" : "<span style='color: #ff5555'>" . translate('admin_chars_status_offline', 'Offline') . "</span>";
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('admin_chars_meta_description', 'Character Management for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('admin_chars_page_title', 'Character Management'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/characters.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/footer.css">
    
</head>
<body class="characters">
    <div class="wrapper">
        <?php include $project_root . 'includes/header.php'; ?>
            <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">

        <div class="dashboard-container">
            <div class="row">
                <!-- Sidebar -->
                <?php include $project_root . 'includes/admin_sidebar.php'; ?>
                <!-- Main Content -->
                <div class="col-md-9">
                    <h1 class="dashboard-title"><?php echo translate('admin_chars_title', 'Character Management'); ?></h1>
                    <?php echo $update_message; ?>
                    <!-- Debug Output: Display number of characters fetched -->
                    <div class="alert alert-info">
                        <?php echo sprintf(translate('admin_chars_found_chars', 'Found %d characters on this page (Total: %d).'), count($characters), $total_chars); ?>
                    </div>
                    <!-- Search and Sort Form -->
                    <form class="search-form" method="GET" action="<?php echo $base_path; ?>admin/characters">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="search_char_name" class="form-label"><?php echo translate('admin_chars_label_char_name', 'Character Name'); ?></label>
                                <input type="text" name="search_char_name" id="search_char_name" class="form-control" value="<?php echo htmlspecialchars($search_char_name); ?>" placeholder="<?php echo translate('admin_chars_placeholder_char_name', 'Enter character name'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_username" class="form-label"><?php echo translate('admin_chars_label_username', 'Username'); ?></label>
                                <input type="text" name="search_username" id="search_username" class="form-control" value="<?php echo htmlspecialchars($search_username); ?>" placeholder="<?php echo translate('admin_chars_placeholder_username', 'Enter username'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="min_level" class="form-label"><?php echo translate('admin_chars_label_min_level', 'Min Level'); ?></label>
                                <input type="number" name="min_level" id="min_level" class="form-control" value="<?php echo htmlspecialchars($min_level); ?>" placeholder="<?php echo translate('admin_chars_placeholder_min_level', 'e.g., 1'); ?>" min="1" max="255">
                            </div>
                            <div class="col-md-3">
                                <label for="max_level" class="form-label"><?php echo translate('admin_chars_label_max_level', 'Max Level'); ?></label>
                                <input type="number" name="max_level" id="max_level" class="form-control" value="<?php echo htmlspecialchars($max_level); ?>" placeholder="<?php echo translate('admin_chars_placeholder_max_level', 'e.g., 255'); ?>" min="1" max="255">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="online_filter" class="form-label"><?php echo translate('admin_chars_label_online_status', 'Online Status'); ?></label>
                                <select name="online_filter" id="online_filter" class="form-select">
                                    <option value="" <?php echo $online_filter === '' ? 'selected' : ''; ?>><?php echo translate('admin_chars_option_all', 'All'); ?></option>
                                    <option value="online" <?php echo $online_filter === 'online' ? 'selected' : ''; ?>><?php echo translate('admin_chars_option_online', 'Online'); ?></option>
                                    <option value="offline" <?php echo $online_filter === 'offline' ? 'selected' : ''; ?>><?php echo translate('admin_chars_option_offline', 'Offline'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sort_id" class="form-label"><?php echo translate('admin_chars_label_sort_id', 'Sort by ID'); ?></label>
                                <select name="sort_id" id="sort_id" class="form-select">
                                    <option value="asc" <?php echo $sort_id === 'asc' ? 'selected' : ''; ?>><?php echo translate('admin_chars_option_sort_asc', 'Ascending'); ?></option>
                                    <option value="desc" <?php echo $sort_id === 'desc' ? 'selected' : ''; ?>><?php echo translate('admin_chars_option_sort_desc', 'Descending'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary"><?php echo translate('admin_chars_search_button', 'Search'); ?></button>
                                <?php if ($search_char_name || $search_username || $online_filter !== '' || $min_level !== '' || $max_level !== ''): ?>
                                    <a href="<?php echo $base_path; ?>admin/characters" class="btn btn-secondary ms-2"><?php echo translate('admin_chars_clear_filters', 'Clear Filters'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                    <!-- Characters Table -->
                    <div class="card">
                        <div class="card-header"><?php echo translate('admin_chars_table_header', 'Characters'); ?></div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('admin_chars_table_char_id', 'Character ID'); ?></th>
                                            <th><?php echo translate('admin_chars_table_name', 'Name'); ?></th>
                                            <th><?php echo translate('admin_chars_table_username', 'Username'); ?></th>
                                            <th><?php echo translate('admin_chars_table_race', 'Race'); ?></th>
                                            <th><?php echo translate('admin_chars_table_class', 'Class'); ?></th>
                                            <th><?php echo translate('admin_chars_table_map', 'Map'); ?></th>
                                            <th><?php echo translate('admin_chars_table_level', 'Level'); ?></th>
                                            <th><?php echo translate('admin_chars_table_online', 'Online'); ?></th>
                                            <th><?php echo translate('admin_chars_table_action', 'Action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($characters)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center"><?php echo translate('admin_chars_no_chars_found', 'No characters found.'); ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($characters as $char): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($char['guid']); ?></td>
                                                    <td><?php echo htmlspecialchars($char['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($char['username']); ?></td>
                                                    <td><?php echo getRaceIcon($char['race'], $char['gender']); ?></td>
                                                    <td><?php echo getClassIcon($char['class']); ?></td>
                                                    <td><?php echo htmlspecialchars(isset($map_names[$char['map']]) ? $map_names[$char['map']] : $char['map']); ?></td>
                                                    <td><?php echo htmlspecialchars($char['level']); ?></td>
                                                    <td><?php echo getOnlineStatus($char['online']); ?></td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#manageModal-<?php echo $char['guid']; ?>"><?php echo translate('admin_chars_manage_button', 'Manage'); ?></button>
                                                    </td>
                                                </tr>
                                                <!-- Manage Character Modal -->
                                                <div class="modal fade" id="manageModal-<?php echo $char['guid']; ?>" tabindex="-1" aria-labelledby="manageModalLabel-<?php echo $char['guid']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="manageModalLabel-<?php echo $char['guid']; ?>"><?php echo translate('admin_chars_manage_modal_title', 'Manage Character: ') . htmlspecialchars($char['name']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo translate('admin_chars_close_button', 'Close'); ?>"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST" action="<?php echo $base_path; ?>admin/characters">
                                                                    <input type="hidden" name="action" value="manage_character">
                                                                    <input type="hidden" name="guid" value="<?php echo $char['guid']; ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><?php echo translate('admin_chars_label_action', 'Action'); ?></label>
                                                                        <select name="char_action" class="form-select" id="charAction-<?php echo $char['guid']; ?>" onchange="toggleActionFields(this)">
                                                                            <option value="add_gold"><?php echo translate('admin_chars_action_add_gold', 'Add Gold'); ?></option>
                                                                            <option value="change_level"><?php echo translate('admin_chars_action_change_level', 'Change Level'); ?></option>
                                                                            <option value="teleport"><?php echo translate('admin_chars_action_teleport', 'Teleport (Custom)'); ?></option>
                                                                            <option value="teleport_directly"><?php echo translate('admin_chars_action_teleport_direct', 'Teleport Directly'); ?></option>
                                                                        </select>
                                                                    </div>
                                                                    <div id="goldFields-<?php echo $char['guid']; ?>">
                                                                        <div class="mb-3">
                                                                            <label class="form-label"><?php echo translate('admin_chars_label_gold', 'Gold Amount (in gold)'); ?></label>
                                                                            <input type="number" name="gold" class="form-control" placeholder="<?php echo translate('admin_chars_placeholder_gold', 'Enter gold amount (e.g., 100)'); ?>" min="0" required>
                                                                        </div>
                                                                    </div>
                                                                    <div id="levelFields-<?php echo $char['guid']; ?>" style="display: none;">
                                                                        <div class="mb-3">
                                                                            <label class="form-label"><?php echo translate('admin_chars_label_level', 'Level (1-255)'); ?></label>
                                                                            <input type="number" name="level" class="form-control" placeholder="<?php echo translate('admin_chars_placeholder_level', 'Enter level (e.g., 80)'); ?>" min="1" max="255" required>
                                                                        </div>
                                                                    </div>
                                                                    <div id="teleportFields-<?php echo $char['guid']; ?>" style="display: none;">
                                                                        <div class="mb-3">
                                                                            <label class="form-label"><?php echo translate('admin_chars_label_map', 'Map ID'); ?></label>
                                                                            <select name="map" class="form-select">
                                                                                <?php foreach ($map_names as $id => $name): ?>
                                                                                    <option value="<?php echo $id; ?>"><?php echo $id . ' - ' . htmlspecialchars($name); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-4">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label"><?php echo translate('admin_chars_label_x_coord', 'X Coordinate'); ?></label>
                                                                                    <input type="number" step="0.000001" name="x" class="form-control" placeholder="<?php echo translate('admin_chars_placeholder_x_coord', 'X coordinate'); ?>" required>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label"><?php echo translate('admin_chars_label_y_coord', 'Y Coordinate'); ?></label>
                                                                                    <input type="number" step="0.000001" name="y" class="form-control" placeholder="<?php echo translate('admin_chars_placeholder_y_coord', 'Y coordinate'); ?>" required>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label"><?php echo translate('admin_chars_label_z_coord', 'Z Coordinate'); ?></label>
                                                                                    <input type="number" step="0.000001" name="z" class="form-control" placeholder="<?php echo translate('admin_chars_placeholder_z_coord', 'Z coordinate'); ?>" required>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="alert alert-info">
                                                                            <?php echo translate('admin_chars_teleport_tip', 'Tip: Look up coordinates in the game using .gps command.'); ?>
                                                                        </div>
                                                                    </div>
                                                                    <div id="teleportDirectlyFields-<?php echo $char['guid']; ?>" style="display: none;">
                                                                        <div class="mb-3">
                                                                            <label class="form-label"><?php echo translate('admin_chars_label_destination', 'Destination'); ?></label>
                                                                            <select name="predefined_location" class="form-select">
                                                                                <option value="stormwind">Stormwind City</option>
                                                                                <option value="orgrimmar">Orgrimmar</option>
                                                                                <option value="shattrath">Shattrath City</option>
                                                                                <option value="dalaran">Dalaran (Northrend)</option>
                                                                                <option value="gm_island">GM Island</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo translate('admin_chars_cancel_button', 'Cancel'); ?></button>
                                                                        <button type="submit" class="btn btn-primary"><?php echo translate('admin_chars_apply_button', 'Apply'); ?></button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="<?php echo translate('admin_chars_pagination_aria', 'Character pagination'); ?>">
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo $base_path; ?>admin/characters?page=<?php echo $page - 1; ?>&search_char_name=<?php echo urlencode($search_char_name); ?>&search_username=<?php echo urlencode($search_username); ?>&online_filter=<?php echo urlencode($online_filter); ?>&min_level=<?php echo urlencode($min_level); ?>&max_level=<?php echo urlencode($max_level); ?>&sort_id=<?php echo $sort_id; ?>" aria-label="<?php echo translate('admin_chars_previous', 'Previous'); ?>">
                                                <span aria-hidden="true">&laquo; <?php echo translate('admin_chars_previous', 'Previous'); ?></span>
                                            </a>
                                        </li>
                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        if ($start_page > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="' . $base_path . 'admin/characters?page=1&search_char_name=' . urlencode($search_char_name) . '&search_username=' . urlencode($search_username) . '&online_filter=' . urlencode($online_filter) . '&min_level=' . urlencode($min_level) . '&max_level=' . urlencode($max_level) . '&sort_id=' . $sort_id . '">1</a></li>';
                                            if ($start_page > 2) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                        }
                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                            echo '<a class="page-link" href="' . $base_path . 'admin/characters?page=' . $i . '&search_char_name=' . urlencode($search_char_name) . '&search_username=' . urlencode($search_username) . '&online_filter=' . urlencode($online_filter) . '&min_level=' . urlencode($min_level) . '&max_level=' . urlencode($max_level) . '&sort_id=' . $sort_id . '">' . $i . '</a>';
                                            echo '</li>';
                                        }
                                        if ($end_page < $total_pages) {
                                            if ($end_page < $total_pages - 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            echo '<li class="page-item"><a class="page-link" href="' . $base_path . 'admin/characters?page=' . $total_pages . '&search_char_name=' . urlencode($search_char_name) . '&search_username=' . urlencode($search_username) . '&online_filter=' . urlencode($online_filter) . '&min_level=' . urlencode($min_level) . '&max_level=' . urlencode($max_level) . '&sort_id=' . $sort_id . '">' . $total_pages . '</a></li>';
                                        }
                                        ?>
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo $base_path; ?>admin/characters?page=<?php echo $page + 1; ?>&search_char_name=<?php echo urlencode($search_char_name); ?>&search_username=<?php echo urlencode($search_username); ?>&online_filter=<?php echo urlencode($online_filter); ?>&min_level=<?php echo urlencode($min_level); ?>&max_level=<?php echo urlencode($max_level); ?>&sort_id=<?php echo $sort_id; ?>" aria-label="<?php echo translate('admin_chars_next', 'Next'); ?>">
                                                <span aria-hidden="true"><?php echo translate('admin_chars_next', 'Next'); ?> &raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include $project_root . 'includes/footer.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_path; ?>assets/js/pages/admin/characters.js"></script>
</body>
</html>
<?php 
$site_db->close();
$auth_db->close();
$char_db->close();
?>