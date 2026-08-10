<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/header.php';

// Faction from race
function getFaction($race) {
    $alliance = [1, 3, 4, 7, 11, 22, 25, 29];
    return in_array($race, $alliance) ? 'Alliance' : 'Horde';
}

function factionIconByName($faction) {
    global $base_path;
    return $base_path . "img/accountimg/faction/" . strtolower($faction) . ".png";
}

// Search handling
$search = '';
$search_error = '';

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);

    // Remove SQL wildcards
    $search = str_replace(['%', '_'], '', $search);

    // Limit length
    $search = substr($search, 0, 16);

    // Minimum length check
    if (strlen($search) > 0 && strlen($search) < 2) {
        $search_error = translate('arena_3v3_search_min', 'Please enter at least 2 characters.');
        $search = '';
    }
}

if ($search !== '') {
    // Search 3v3 arena teams by team name
    $sql = "
    SELECT 
        at.arenaTeamId,
        at.name AS team_name,
        at.rating,
        at.seasonWins,
        (at.seasonGames - at.seasonWins) AS seasonLosses,
        CASE WHEN at.seasonGames > 0 
            THEN ROUND((at.seasonWins / at.seasonGames) * 100, 1) 
            ELSE 0 END AS winrate,
        c.race
    FROM arena_team at
    JOIN arena_team_member atm ON at.arenaTeamId = atm.arenaTeamId
    JOIN characters c ON atm.guid = c.guid
    WHERE at.type = 3
    AND atm.guid = at.captainGuid
    AND LOWER(at.name) LIKE LOWER(?)
    ORDER BY at.rating DESC
    LIMIT 50
    ";

    $stmt = $char_db->prepare($sql);
    $like = '%' . $search . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Default Top 50 3v3 teams
    $sql = "
    SELECT 
        at.arenaTeamId,
        at.name AS team_name,
        at.rating,
        at.seasonWins,
        (at.seasonGames - at.seasonWins) AS seasonLosses,
        CASE WHEN at.seasonGames > 0 
            THEN ROUND((at.seasonWins / at.seasonGames) * 100, 1) 
            ELSE 0 END AS winrate,
        c.race
    FROM arena_team at
    JOIN arena_team_member atm ON at.arenaTeamId = atm.arenaTeamId
    JOIN characters c ON atm.guid = c.guid
    WHERE at.type = 3
    AND atm.guid = at.captainGuid
    ORDER BY at.rating DESC
    LIMIT 50
    ";

    $result = $char_db->query($sql);
}

$teams = [];
while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title_name . " " . translate('arena_3v3_page_title', 'Top 50 3v3 Arena Teams'); ?></title>
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
        
        /* Table container scrollbars */
        .table-container {
            scrollbar-width: thin;
            scrollbar-color: #ffcc00 #1f2937;
            font-family: 'Arial', sans-serif;
        }
        
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #1f2937;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #ffcc00;
            border-radius: 4px;
        }
        
        /* Top 5 teams styling */
        .top5 {
            background: linear-gradient(to right, rgba(22, 22, 22, 0.9), rgba(153, 27, 27, 0.85)) !important;
        }
        
        .top5:hover {
            background: linear-gradient(to right, rgba(185, 28, 28, 0.9), rgba(127, 29, 29, 0.9)) !important;
            filter: brightness(1.2);
            transition: filter 0.2s ease-in-out;
            cursor: var(--hover-wow-gif) 16 16, auto;
        }
        
        /* Regular row hover */
        .team-row:hover {
            background-color: rgba(153, 27, 27, 0.6) !important;
            transition: background-color 0.2s ease-in-out;
            cursor: var(--hover-wow-gif) 16 16, auto;
        }
        
        /* Search button cursor */
        #search-btn:hover {
            cursor: var(--hover-wow-gif) 16 16, auto;
        }
        
        /* Links */
        .team-link {
            color: #ffffff;
            text-decoration: none;
        }
        
        .team-link:hover {
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
    <!-- Updated container width to max-w-7xl -->
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        
        <!-- Main Container - Transparent Glass Effect -->
        <div class="bg-black/30 backdrop-blur-md rounded-2xl border border-amber-500/30 p-6 md:p-10 shadow-2xl">
            
            <!-- Title -->
            <h1 class="text-3xl md:text-5xl font-bold text-center text-amber-400 mb-6 font-['UnifrakturCook',sans-serif] [text-shadow:0_0_20px_rgba(255,215,0,0.3)]">
                <?php echo translate('arena_3v3_title', 'Top 50 3v3 Arena Teams'); ?>
            </h1>

            <!-- Navigation Bar Include -->
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
                    placeholder="<?php echo translate('arena_3v3_search_placeholder', 'Search team name...'); ?>"
                    maxlength="16"
                    class="w-full sm:w-80 px-4 py-2.5 rounded-lg bg-black/60 text-white border-2 border-amber-500/40 focus:outline-none focus:border-amber-400 focus:shadow-[0_0_15px_rgba(255,215,0,0.2)] transition-all duration-300 placeholder:text-gray-400 text-sm"
                >
                <button 
                    type="submit"
                    id="search-btn"
                    class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-black font-bold hover:from-amber-400 hover:to-amber-500 transition-all duration-300 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-105"
                >
                    <?php echo translate('arena_3v3_search_btn', 'Search'); ?>
                </button>

                <?php if ($search !== ''): ?>
                    <a href="<?php echo $base_path; ?>armory/arena_3v3" class="px-5 py-2.5 rounded-lg bg-gray-600/80 text-white hover:bg-gray-500/80 transition-all duration-300 hover:scale-105">
                        <?php echo translate('arena_3v3_reset_btn', 'Reset'); ?>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Table -->
            <div class="table-container overflow-x-auto rounded-xl shadow-2xl border border-amber-500/20">
                <table class="w-full text-sm md:text-base text-center">
                    <thead class="bg-gradient-to-r from-amber-600/80 to-amber-700/80 text-amber-100 uppercase text-xs md:text-sm">
                        <tr>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('arena_3v3_rank', 'Rank'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold text-left"><?php echo translate('arena_3v3_name', 'Name'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('arena_3v3_faction', 'Faction'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('arena_3v3_wins', 'Wins'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('arena_3v3_losses', 'Losses'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('arena_3v3_winrate', 'Winrate'); ?></th>
                            <th class="py-4 px-4 md:px-6 font-bold"><?php echo translate('arena_3v3_rating', 'Rating'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($teams) == 0): ?>
                            <tr>
                                <td colspan="7" class="py-8 px-4 text-lg text-amber-400 font-bold">
                                    <?php echo translate('arena_3v3_no_teams', 'No 3v3 arena teams found.'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank = 1;
                            $teamCount = count($teams);
                            foreach ($teams as $team) {
                                $rowClass = ($rank <= 5 && $teamCount >= 5) ? 'top5' : 'team-row';
                                $faction = getFaction($team['race']);
                                $teamUrl = $base_path . "armory/arenateam?arenaTeamId=" . $team['arenaTeamId'];

                                echo "<tr class='{$rowClass} transition-all duration-200 border-b border-gray-700/50 last:border-0' onclick=\"window.location='{$teamUrl}';\">
                                    <td class='py-3.5 px-4 md:px-6 font-bold text-amber-400'>{$rank}</td>
                                    <td class='py-3.5 px-4 md:px-6 text-left'>
                                        <a href='{$teamUrl}' class='team-link font-semibold hover:text-amber-400 transition-colors duration-200'>
                                            " . htmlspecialchars($team['team_name']) . "
                                        </a>
                                    </td>
                                    <td class='py-3.5 px-4 md:px-6'>
                                        <img src='" . factionIconByName($faction) . "' alt='{$faction}' title='{$faction}' class='inline-block w-6 h-6 rounded-full shadow-md'>
                                    </td>
                                    <td class='py-3.5 px-4 md:px-6 text-emerald-400 font-semibold'>{$team['seasonWins']}</td>
                                    <td class='py-3.5 px-4 md:px-6 text-rose-400 font-semibold'>{$team['seasonLosses']}</td>
                                    <td class='py-3.5 px-4 md:px-6 font-bold text-amber-300'>{$team['winrate']}%</td>
                                    <td class='py-3.5 px-4 md:px-6 font-extrabold text-amber-400 text-base md:text-lg'>{$team['rating']}</td>
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
                <?php echo translate('arena_3v3_footer', 'Click on any row to view team details.'); ?>
            </div>
        </div>
    </div>
</div>

<?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>