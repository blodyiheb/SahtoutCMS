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

    // Limit length safely using multi-byte substr
    $search = mb_substr($search, 0, 16);

    // Minimum length check
    if (mb_strlen($search) > 0 && mb_strlen($search) < 2) {
        $search_error = translate('arena_5v5_search_min', 'Please enter at least 2 characters.');
        $search = '';
    }
}

if ($search !== '') {
    // Properly escape SQL LIKE special characters (%, _)
    // Use simple str_replace instead of addcslashes to avoid escaping backslash issues
    $escaped_search = str_replace(['%', '_'], ['\%', '\_'], $search);

    // Search 5v5 arena teams by team name
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
    WHERE at.type = 5
    AND atm.guid = at.captainGuid
    AND at.name LIKE ?
    ORDER BY at.rating DESC
    LIMIT 50
    ";

    $stmt = $char_db->prepare($sql);
    $like = '%' . $escaped_search . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Default Top 50 5v5 teams
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
    WHERE at.type = 5
    AND atm.guid = at.captainGuid
    ORDER BY at.rating DESC
    LIMIT 50
    ";

    $result = $char_db->query($sql);
}

$teams = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title_name . " " . translate('arena_5v5_page_title', 'Top 50 5v5 Arena Teams'); ?></title>
    
    <!-- Tailwind CSS -->
    <!-- Font Awesome for icons -->
    
    <style>
        /* Page background - Show full background image without overlay */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-armory.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        /* REMOVED: Dark overlay that was hiding the background */
        /* body::before is completely removed */
        
        /* Main content wrapper */
        .arena-content {
            position: relative;
            z-index: 1;
        }
        
        /* Glass effect container - Darker to improve readability */
        .glass-container {
            background: rgba(5, 7, 11, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,.22);
            border-radius: 0;
            padding: 2.5rem 2.5rem;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8), inset 0 0 60px rgba(0,0,0,.25);
            position: relative;
        }
        
        .glass-container::before {
            content: ''; position: absolute; inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        
        .glass-container::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
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
        
        /* Table container - FIXED: Ensure horizontal scroll on mobile */
        .table-container {
            scrollbar-width: thin;
            scrollbar-color: #f2cf5b #1f2937;
            font-family: 'Arial', sans-serif;
            border-radius: 0;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            display: block;
            width: 100%;
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
            background: #f2cf5b;
            border-radius: 4px;
        }
        
        /* Table - FIXED: Ensure proper sizing */
        .table-container table {
            min-width: 700px;
            width: 100%;
            border-collapse: collapse;
        }
        
        /* FIXED: Table cells with proper padding and min-width */
        .table-container th,
        .table-container td {
            padding: 12px 8px;
            white-space: nowrap;
        }
        
        /* Responsive table cells */
        @media (max-width: 640px) {
            .table-container th,
            .table-container td {
                padding: 10px 6px;
                font-size: 0.75rem;
            }
        }

        /* 1st Place - Legendary Gold with glow */
        .rank-1 {
            background: linear-gradient(135deg, rgba(242, 207, 82, 0.35), rgba(201, 162, 39, 0.25), rgba(242, 207, 82, 0.35)) !important;
            border-left: 4px solid #f2cf5b;
            box-shadow: inset 0 0 30px rgba(242, 207, 82, 0.15);
        }
        .rank-1 td {
            color: #fff5d6 !important;
            text-shadow: 0 0 20px rgba(242, 207, 82, 0.3);
        }
        .rank-1 td:first-child {
            color: #f2cf5b !important;
            font-size: 1.2em;
            text-shadow: 0 0 30px rgba(242, 207, 82, 0.5);
        }
        .rank-1:hover {
            background: linear-gradient(135deg, rgba(242, 207, 82, 0.5), rgba(201, 162, 39, 0.4), rgba(242, 207, 82, 0.5)) !important;
            filter: brightness(1.1);
            transition: all 0.3s ease-in-out;
            cursor: pointer;
            box-shadow: 0 0 40px rgba(242, 207, 82, 0.2);
        }

        /* 2nd Place - Platinum/Silver with shimmer */
        .rank-2 {
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.3), rgba(160, 160, 160, 0.2), rgba(192, 192, 192, 0.3)) !important;
            border-left: 4px solid #c0c0c0;
            box-shadow: inset 0 0 30px rgba(192, 192, 192, 0.1);
        }
        .rank-2 td {
            color: #f0f0f0 !important;
            text-shadow: 0 0 20px rgba(192, 192, 192, 0.2);
        }
        .rank-2 td:first-child {
            color: #c0c0c0 !important;
            font-size: 1.1em;
            text-shadow: 0 0 30px rgba(192, 192, 192, 0.4);
        }
        .rank-2:hover {
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.45), rgba(160, 160, 160, 0.35), rgba(192, 192, 192, 0.45)) !important;
            filter: brightness(1.1);
            transition: all 0.3s ease-in-out;
            cursor: pointer;
            box-shadow: 0 0 40px rgba(192, 192, 192, 0.15);
        }

        /* 3rd Place - Bronze with warm glow */
        .rank-3 {
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.35), rgba(180, 100, 30, 0.25), rgba(205, 127, 50, 0.35)) !important;
            border-left: 4px solid #cd7f32;
            box-shadow: inset 0 0 30px rgba(205, 127, 50, 0.1);
        }
        .rank-3 td {
            color: #f5e6d3 !important;
            text-shadow: 0 0 20px rgba(205, 127, 50, 0.2);
        }
        .rank-3 td:first-child {
            color: #cd7f32 !important;
            font-size: 1.05em;
            text-shadow: 0 0 30px rgba(205, 127, 50, 0.4);
        }
        .rank-3:hover {
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.5), rgba(180, 100, 30, 0.4), rgba(205, 127, 50, 0.5)) !important;
            filter: brightness(1.1);
            transition: all 0.3s ease-in-out;
            cursor: pointer;
            box-shadow: 0 0 40px rgba(205, 127, 50, 0.15);
        }
        
        /* Top 4 and Top 5 rows - Dark blue with subtle gradient */
        .top5 {
            background: linear-gradient(to right, rgba(20, 30, 60, 0.7), rgba(10, 20, 50, 0.6)) !important;
        }
        
        .top5:hover {
            background: linear-gradient(to right, rgba(30, 50, 90, 0.8), rgba(20, 40, 80, 0.7)) !important;
            filter: brightness(1.15);
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }
        
        /* Regular row hover */
        .team-row:hover {
            background: rgba(242, 207, 82, 0.15) !important;
            transition: background-color 0.2s ease-in-out;
            cursor: pointer;
        }
        
        /* Default row style */
        .team-row {
            background: rgba(0, 0, 0, 0.4);
            transition: all 0.2s ease-in-out;
        }
        
        /* Search button - Purple theme matching 5v5 nav button */
        #search-btn {
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            border: 2px solid #8b5cf6;
            color: #ffffff;
            font-weight: 700;
            text-shadow: 0 1px 0 rgba(0,0,0,0.2);
        }
        
        #search-btn:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #a78bfa, #7c3aed);
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.5);
            border-color: #a78bfa;
        }
        
        #search-btn i {
            color: #ffffff;
        }
        
        /* Reset button - matches nav idle state */
        .reset-btn {
            background: rgba(10, 14, 22, 0.6);
            border: 2px solid rgba(201, 162, 39, 0.3);
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .reset-btn:hover {
            transform: scale(1.05);
            border-color: #f2cf5b;
            background: rgba(242, 207, 82, 0.1);
            color: #f2cf5b;
            box-shadow: 0 0 20px rgba(242, 207, 82, 0.15);
        }
        
        /* Links */
        .team-link {
            color: #ffffff;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .team-link:hover {
            text-decoration: underline;
            color: #f2cf5b;
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
        
        /* Medal emojis for top 3 */
        .medal-gold {
            color: #f2cf5b;
            font-size: 1.4em;
            filter: drop-shadow(0 0 10px rgba(242, 207, 82, 0.5));
        }
        .medal-silver {
            color: #c0c0c0;
            font-size: 1.3em;
            filter: drop-shadow(0 0 10px rgba(192, 192, 192, 0.4));
        }
        .medal-bronze {
            color: #cd7f32;
            font-size: 1.2em;
            filter: drop-shadow(0 0 10px rgba(205, 127, 50, 0.4));
        }
        
        /* Responsive adjustments */
        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }
            
            .glass-container {
                padding: 1.5rem 0.75rem;
            }
        }
    </style>
</head>
<body>
<div class="arena-content min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        <!-- Main Container - Darker Glass Effect -->
        <div class="glass-container">
            
            <!-- Title -->
            <h1 class="wow-title text-3xl md:text-5xl font-bold text-center mb-6">
                <?php echo translate('arena_5v5_title', 'Top 50 5v5 Arena Teams'); ?>
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
                    placeholder="<?php echo translate('arena_5v5_search_placeholder', 'Search team name...'); ?>"
                    maxlength="16"
                    class="w-full sm:w-80 px-4 py-2.5 rounded-lg bg-black/60 text-white border-2 border-[rgba(201,162,39,0.4)] focus:outline-none focus:border-[#f2cf5b] focus:shadow-[0_0_15px_rgba(242,207,82,0.2)] transition-all duration-300 placeholder:text-gray-400 text-sm"
                >
                <button 
                    type="submit"
                    id="search-btn"
                    class="px-6 py-2.5 rounded-lg transition-all duration-300 shadow-lg shadow-[rgba(139,92,246,0.3)] flex items-center gap-2"
                >
                    <i class="fas fa-search"></i> <?php echo translate('arena_5v5_search_btn', 'Search'); ?>
                </button>

                <?php if ($search !== ''): ?>
                    <a href="<?php echo $base_path; ?>armory/arena_5v5" class="reset-btn px-5 py-2.5 rounded-lg transition-all duration-300 flex items-center gap-2">
                        <i class="fas fa-times"></i> <?php echo translate('arena_5v5_reset_btn', 'Reset'); ?>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Table - FIXED: Added scroll wrapper -->
            <div class="table-container overflow-x-auto border border-[rgba(201,162,39,0.15)] shadow-2xl">
                <table class="w-full text-sm md:text-base text-center min-w-[700px]">
                    <thead class="bg-gradient-to-r from-[rgba(201,162,39,0.9)] to-[rgba(160,130,30,0.9)] text-amber-100 uppercase text-xs md:text-sm">
                        <tr>
                            <th class="py-4 px-3 md:px-6 font-bold whitespace-nowrap"><?php echo translate('arena_5v5_rank', 'Rank'); ?></th>
                            <th class="py-4 px-3 md:px-6 font-bold text-left whitespace-nowrap"><?php echo translate('arena_5v5_name', 'Name'); ?></th>
                            <th class="py-4 px-3 md:px-6 font-bold whitespace-nowrap"><?php echo translate('arena_5v5_faction', 'Faction'); ?></th>
                            <th class="py-4 px-3 md:px-6 font-bold whitespace-nowrap"><?php echo translate('arena_5v5_wins', 'Wins'); ?></th>
                            <th class="py-4 px-3 md:px-6 font-bold whitespace-nowrap"><?php echo translate('arena_5v5_losses', 'Losses'); ?></th>
                            <th class="py-4 px-3 md:px-6 font-bold whitespace-nowrap"><?php echo translate('arena_5v5_winrate', 'Winrate'); ?></th>
                            <th class="py-4 px-3 md:px-6 font-bold whitespace-nowrap"><?php echo translate('arena_5v5_rating', 'Rating'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($teams) == 0): ?>
                            <tr>
                                <td colspan="7" class="py-8 px-4 text-lg text-[#f2cf5b] font-bold text-center">
                                    <i class="fas fa-trophy text-3xl block mb-3 text-[rgba(201,162,39,0.3)]"></i>
                                    <?php echo translate('arena_5v5_no_teams', 'No 5v5 arena teams found.'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank = 1;
                            $teamCount = count($teams);
                            foreach ($teams as $team) {
                                // Dynamic row styling based on rank
                                if ($rank === 1) {
                                    $rowClass = 'rank-1';
                                    $rankDisplay = '<span class="medal-gold">🥇</span>';
                                } elseif ($rank === 2) {
                                    $rowClass = 'rank-2';
                                    $rankDisplay = '<span class="medal-silver">🥈</span>';
                                } elseif ($rank === 3) {
                                    $rowClass = 'rank-3';
                                    $rankDisplay = '<span class="medal-bronze">🥉</span>';
                                } elseif ($rank <= 5 && $teamCount >= 5) {
                                    $rowClass = 'top5';
                                    $rankDisplay = '#' . $rank;
                                } else {
                                    $rowClass = 'team-row';
                                    $rankDisplay = '#' . $rank;
                                }

                                $faction = getFaction($team['race']);
                                $teamUrl = $base_path . "armory/arenateam?arenaTeamId=" . (int)$team['arenaTeamId'];

                                echo "<tr class='{$rowClass} transition-all duration-200 border-b border-gray-700/30 last:border-0' onclick=\"window.location='{$teamUrl}';\" style=\"cursor:pointer;\">
                                    <td class='py-3.5 px-3 md:px-6 font-bold text-[#f2cf5b] whitespace-nowrap'>{$rankDisplay}</td>
                                    <td class='py-3.5 px-3 md:px-6 text-left whitespace-nowrap'>
                                        <a href='{$teamUrl}' class='team-link font-semibold hover:text-[#f2cf5b] transition-colors duration-200'>
                                            " . htmlspecialchars($team['team_name']) . "
                                        </a>
                                    </td>
                                    <td class='py-3.5 px-3 md:px-6 whitespace-nowrap'>
                                        <img src='" . factionIconByName($faction) . "' alt='{$faction}' title='{$faction}' class='inline-block w-6 h-6 rounded-full shadow-md'>
                                    </td>
                                    <td class='py-3.5 px-3 md:px-6 text-[#2ecc71] font-semibold whitespace-nowrap'>" . (int)$team['seasonWins'] . "</td>
                                    <td class='py-3.5 px-3 md:px-6 text-[#ef4444] font-semibold whitespace-nowrap'>" . (int)$team['seasonLosses'] . "</td>
                                    <td class='py-3.5 px-3 md:px-6 font-bold text-[#f2cf5b] whitespace-nowrap'>" . htmlspecialchars($team['winrate']) . "%</td>
                                    <td class='py-3.5 px-3 md:px-6 font-extrabold text-[#f2cf5b] text-base md:text-lg whitespace-nowrap'>" . (int)$team['rating'] . "</td>
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
                <i class="fas fa-mouse-pointer mr-2 text-[rgba(201,162,39,0.4)]"></i>
                <?php echo translate('arena_5v5_footer', 'Click on any row to view team details.'); ?>
            </div>
        </div>
    </div>
</div>

<?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>