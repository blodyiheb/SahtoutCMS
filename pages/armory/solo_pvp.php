<?php
define('ALLOWED_ACCESS', true);
// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/header.php';

$search = '';
$search_error = '';

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);

    // Remove SQL wildcards
    $search = str_replace(['%', '_'], '', $search);

    // Limit length
    $search = substr($search, 0, 12);

    // Minimum length (use 3 for big servers)
    if (strlen($search) > 0 && strlen($search) < 2) {
        $search_error = translate('solo_pvp_search_min', 'Please enter at least 2 characters.');
        $search = '';
    }
}

if ($search !== '') {
    // Search by character name
    $sql = "
    SELECT c.guid, c.name, c.race, c.class, c.level, c.gender, c.totalKills, g.name AS guild_name
    FROM characters c
    LEFT JOIN guild_member gm ON c.guid = gm.guid
    LEFT JOIN guild g ON gm.guildid = g.guildid
    WHERE LOWER(c.name) LIKE LOWER(?)
    ORDER BY c.level DESC, c.totalKills DESC
    LIMIT 50
    ";

    $stmt = $char_db->prepare($sql);
    $like = '%' . $search . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Default Top 50
    $sql = "
    SELECT c.guid, c.name, c.race, c.class, c.level, c.gender, c.totalKills, g.name AS guild_name
    FROM characters c
    LEFT JOIN guild_member gm ON c.guid = gm.guid
    LEFT JOIN guild g ON gm.guildid = g.guildid
    ORDER BY c.level DESC, c.totalKills DESC
    LIMIT 50
    ";

    $result = $char_db->query($sql);
}

// Prepare players array
$players = [];
while ($row = $result->fetch_assoc()) {
    $players[] = [
        'guid' => $row['guid'],
        'name' => $row['name'],
        'race' => $row['race'],
        'class' => $row['class'],
        'gender' => $row['gender'],
        'level' => $row['level'],
        'kills' => $row['totalKills'],
        'guild_name' => $row['guild_name'] ?? translate('solo_pvp_no_guild', 'No Guild')
    ];
}

// Faction from race
function getFaction($race) {
    $alliance = [1, 3, 4, 7, 11, 22, 25, 29];
    return in_array($race, $alliance) ? 'Alliance' : 'Horde';
}

// Image paths
function factionIcon($race) {
    global $base_path;
    $faction = getFaction($race);
    return $base_path . "img/accountimg/faction/" . strtolower($faction) . ".png";
}
function raceIcon($race, $gender) {
    global $base_path;
    $genderFolder = ($gender == 0) ? 'male' : 'female';
    $raceMap = [
        1 => 'human', 2 => 'orc', 3 => 'dwarf', 4 => 'nightelf',
        5 => 'undead', 6 => 'tauren', 7 => 'gnome', 8 => 'troll',
        9 => 'goblin', 10 => 'bloodelf', 11 => 'draenei',
        22 => 'worgen', 25 => 'pandaren_alliance', 26 => 'pandaren_horde',
        29 => 'voidelf'
    ];
    $raceName = isset($raceMap[$race]) ? $raceMap[$race] : 'unknown';
    return $base_path . "img/accountimg/race/{$genderFolder}/{$raceName}.png";
}
function classIcon($class) {
    global $base_path;
    $classMap = [
        1 => 'warrior', 2 => 'paladin', 3 => 'hunter', 4 => 'rogue',
        5 => 'priest', 6 => 'deathknight', 7 => 'shaman', 8 => 'mage',
        9 => 'warlock', 10 => 'monk', 11 => 'druid', 12 => 'demonhunter'
    ];
    $className = isset($classMap[$class]) ? $classMap[$class] : 'unknown';
    return $base_path . "img/accountimg/class/{$className}.webp";
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title_name ." ". translate('solo_pvp_page_title', 'Top 50 Players'); ?></title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/armory/arenanavbar.css">
    <style>
        /* Page background */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-armory.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            min-height: 100vh;
            padding-top: 112px;
        }
        
        body::before {
            display: none;
        }
        
        /* Main content wrapper */
        .arena-content {
            position: relative;
            z-index: 1;
        }
        
        /* Table container */
        .table-container {
            scrollbar-width: thin;
            scrollbar-color: #ffcc00 #1f2937;
            font-family: 'Arial', sans-serif;
        }
        
        .table-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #1f2937;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #ffcc00;
            border-radius: 4px;
        }

        /* 1st Place - Gold */
        .rank-1 {
            background: linear-gradient(to right, rgba(120, 85, 0, 0.9), rgba(212, 175, 55, 0.85), rgba(80, 55, 0, 0.9)) !important;
        }
        .rank-1:hover {
            background: linear-gradient(to right, rgba(160, 115, 0, 0.95), rgba(255, 215, 0, 0.95), rgba(120, 85, 0, 0.95)) !important;
            filter: brightness(1.25);
            transition: all 0.2s ease-in-out;
            cursor: var(--hover-wow-gif) 16 16, auto;
        }

        /* 2nd Place - Silver */
        .rank-2 {
            background: linear-gradient(to right, rgba(60, 65, 75, 0.9), rgba(160, 170, 185, 0.8), rgba(40, 45, 55, 0.9)) !important;
        }
        .rank-2:hover {
            background: linear-gradient(to right, rgba(90, 100, 115, 0.95), rgba(200, 210, 225, 0.9), rgba(70, 75, 85, 0.95)) !important;
            filter: brightness(1.2);
            transition: all 0.2s ease-in-out;
            cursor: var(--hover-wow-gif) 16 16, auto;
        }

        /* 3rd Place - Bronze */
        .rank-3 {
            background: linear-gradient(to right, rgba(90, 45, 15, 0.9), rgba(180, 100, 50, 0.8), rgba(60, 30, 10, 0.9)) !important;
        }
        .rank-3:hover {
            background: linear-gradient(to right, rgba(120, 60, 20, 0.95), rgba(210, 120, 60, 0.9), rgba(90, 40, 15, 0.95)) !important;
            filter: brightness(1.2);
            transition: all 0.2s ease-in-out;
            cursor: var(--hover-wow-gif) 16 16, auto;
        }
        
        /* Top 4 and Top 5 rows */
        .top5 {
            background: linear-gradient(to right, rgba(22, 22, 22, 0.9), rgba(4, 58, 158, 0.9)) !important;
        }
        
        .top5:hover {
            background: linear-gradient(to right, rgba(88, 7, 219, 0.9), rgba(6, 9, 199, 0.8)) !important;
            filter: brightness(1.2);
            transition: filter 0.2s ease-in-out;
            cursor: var(--hover-wow-gif) 16 16, auto;
        }
        
        /* Regular row hover */
        .player-row:hover {
            background-color: rgba(16, 54, 158, 0.7) !important;
            transition: background-color 0.2s ease-in-out;
            cursor: var(--hover-wow-gif) 16 16, auto;
        }
        
        /* Search button hover */
        #search-btn:hover {
            cursor: var(--hover-wow-gif) 16 16, auto;
        }
        
        /* Links */
        .player-link {
            color: #ffffff;
            text-decoration: none;
        }
        
        .player-link:hover {
            text-decoration: underline;
            color: #ffd700;
        }
        
        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }
        }
    </style>
</head>
<body>
<div class="arena-content min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        <!-- Main Container - Transparent Glass Effect -->
        <div class="bg-black/30 backdrop-blur-md rounded-2xl border border-amber-500/30 p-6 md:p-10 shadow-2xl">
            
            <!-- Title -->
            <h1 class="text-3xl md:text-5xl font-bold text-center text-amber-400 mb-6 font-['UnifrakturCook',sans-serif] [text-shadow:0_0_20px_rgba(255,215,0,0.3)]">
                <?php echo translate('solo_pvp_title', 'Top 50 Players'); ?>
            </h1>

            <!-- Navigation - Clean & Modern -->
            <?php include_once $project_root . 'includes/arenanavbar.php'; ?>

            <!-- Search Error -->
            <?php if (!empty($search_error)): ?>
                <div class="mb-4 text-center text-red-400 font-semibold text-sm">
                    <?php echo htmlspecialchars($search_error); ?>
                </div>
            <?php endif; ?>

            <!-- Search Form -->
            <form method="get" class="mb-8 flex flex-col sm:flex-row justify-center items-center gap-3">
                <input 
                    type="text" 
                    name="search" 
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="<?php echo translate('solo_pvp_search_placeholder', 'Search character name...'); ?>"
                    maxlength="12"
                    class="w-full sm:w-80 px-4 py-2.5 rounded-lg bg-black/60 text-white border-2 border-amber-500/40 focus:outline-none focus:border-amber-400 focus:shadow-[0_0_15px_rgba(255,215,0,0.2)] transition-all duration-300 placeholder:text-gray-400 text-sm"
                >
                <button 
                    type="submit"
                    id="search-btn"
                    class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-black font-bold hover:from-amber-400 hover:to-amber-500 transition-all duration-300 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-105"
                >
                    <?php echo translate('solo_pvp_search_btn', 'Search'); ?>
                </button>

                <?php if ($search !== ''): ?>
                    <a href="<?php echo $base_path; ?>armory/solo_pvp" class="px-5 py-2.5 rounded-lg bg-gray-600/80 text-white hover:bg-gray-500/80 transition-all duration-300 hover:scale-105">
                        <?php echo translate('solo_pvp_reset_btn', 'Reset'); ?>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Table -->
            <div class="table-container overflow-x-auto rounded-xl shadow-2xl border border-amber-500/20">
                <table class="w-full text-sm md:text-base text-center">
                    <thead class="bg-gradient-to-r from-amber-600/80 to-amber-700/80 text-amber-100 uppercase text-xs md:text-sm">
                        <tr>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('solo_pvp_rank', 'Rank'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold text-left"><?php echo translate('solo_pvp_name', 'Name'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold text-left hidden sm:table-cell"><?php echo translate('solo_pvp_guild', 'Guild'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('solo_pvp_faction', 'Faction'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold hidden md:table-cell"><?php echo translate('solo_pvp_race', 'Race'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold hidden md:table-cell"><?php echo translate('solo_pvp_class', 'Class'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('solo_pvp_level', 'Level'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('solo_pvp_kills', 'PvP Kills'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($players) == 0): ?>
                            <tr>
                                <td colspan="8" class="py-8 px-4 text-lg text-amber-400 font-bold">
                                    <?php echo translate('solo_pvp_no_players', 'No players found.'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank = 1;
                            $playerCount = count($players);
                            foreach ($players as $p) {
                                // Dynamic row styling based on rank
                                if ($rank === 1) {
                                    $rowClass = 'rank-1';
                                } elseif ($rank === 2) {
                                    $rowClass = 'rank-2';
                                } elseif ($rank === 3) {
                                    $rowClass = 'rank-3';
                                } elseif ($rank <= 5 && $playerCount >= 5) {
                                    $rowClass = 'top5';
                                } else {
                                    $rowClass = 'player-row';
                                }

                                echo "<tr class='{$rowClass} transition-all duration-200 border-b border-gray-700/50 last:border-0' onclick=\"window.location='{$base_path}character?guid={$p['guid']}';\">
                                    <td class='py-3.5 px-4 md:px-6 font-bold text-amber-400'>{$rank}</td>
                                    <td class='py-3.5 px-4 md:px-6 text-left'>
                                        <a href='{$base_path}character?guid={$p['guid']}' class='player-link font-semibold hover:text-amber-400 transition-colors duration-200'>
                                            " . htmlspecialchars($p['name']) . "
                                        </a>
                                    </td>
                                    <td class='py-3.5 px-4 md:px-6 text-left hidden sm:table-cell text-gray-300'>" . htmlspecialchars($p['guild_name']) . "</td>
                                    <td class='py-3.5 px-4 md:px-6'>
                                        <img src='" . factionIcon($p['race']) . "' alt='" . translate('solo_pvp_faction_alt', 'Faction') . "' class='inline-block w-6 h-6 rounded-full shadow-md'>
                                    </td>
                                    <td class='py-3.5 px-4 md:px-6 hidden md:table-cell'>
                                        <img src='" . raceIcon($p['race'], $p['gender']) . "' alt='" . translate('solo_pvp_race_alt', 'Race') . "' class='inline-block w-6 h-6 rounded-full shadow-md'>
                                    </td>
                                    <td class='py-3.5 px-4 md:px-6 hidden md:table-cell'>
                                        <img src='" . classIcon($p['class']) . "' alt='" . translate('solo_pvp_class_alt', 'Class') . "' class='inline-block w-6 h-6 rounded-full shadow-md'>
                                    </td>
                                    <td class='py-3.5 px-4 md:px-6 font-bold text-amber-400'>{$p['level']}</td>
                                    <td class='py-3.5 px-4 md:px-6 font-extrabold text-green-400 text-base md:text-lg'>{$p['kills']}</td>
                                </tr>";
                                $rank++;
                            }
                            ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Footer note -->
            <div class="mt-6 text-center text-gray-400 text-xs md:text-sm">
                <?php echo translate('solo_pvp_footer', 'Click on any row to view character details.'); ?>
            </div>
        </div>
    </div>
</div>

<?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>