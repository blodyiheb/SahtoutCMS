<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';

// Functions to get faction and icon paths
function getFaction($race) {
    $alliance = [1, 3, 4, 7, 11, 22, 25, 29];
    return in_array((int)$race, $alliance, true) ? 'Alliance' : 'Horde';
}

function factionIconByName($faction) {
    global $base_path;
    return $base_path . "img/accountimg/faction/" . strtolower($faction) . ".png";
}

function raceIcon($race, $gender) {
    global $base_path;
    $genderFolder = ((int)$gender === 0) ? 'male' : 'female';
    $raceMap = [
        1 => 'human', 2 => 'orc', 3 => 'dwarf', 4 => 'nightelf',
        5 => 'undead', 6 => 'tauren', 7 => 'gnome', 8 => 'troll',
        9 => 'goblin', 10 => 'bloodelf', 11 => 'draenei',
        22 => 'worgen', 25 => 'pandaren_alliance', 26 => 'pandaren_horde',
        29 => 'voidelf'
    ];
    $raceName = $raceMap[$race] ?? 'unknown';
    return $base_path . "img/accountimg/race/{$genderFolder}/{$raceName}.png";
}

function classIcon($class) {
    global $base_path;
    $classMap = [
        1 => 'warrior', 2 => 'paladin', 3 => 'hunter', 4 => 'rogue',
        5 => 'priest', 6 => 'deathknight', 7 => 'shaman', 8 => 'mage',
        9 => 'warlock', 10 => 'monk', 11 => 'druid', 12 => 'demonhunter'
    ];
    $className = $classMap[$class] ?? 'unknown';
    return $base_path . "img/accountimg/class/{$className}.webp";
}

function getTeamTypeName($type) {
    switch ((int)$type) {
        case 2:
            return translate('arenateam_type_2v2', '2v2');
        case 3:
            return translate('arenateam_type_3v3', '3v3');
        case 5:
            return translate('arenateam_type_5v5', '5v5');
        default:
            return translate('arenateam_type_unknown', 'Unknown');
    }
}

// Get arenaTeamId from URL and sanitize
$arenaTeamId = isset($_GET['arenaTeamId']) ? (int)$_GET['arenaTeamId'] : 0;

// Query team details
$teamSql = "
SELECT 
    at.arenaTeamId,
    at.name AS team_name,
    at.rating,
    at.seasonWins,
    at.seasonGames,
    (at.seasonGames - at.seasonWins) AS seasonLosses,
    CASE WHEN at.seasonGames > 0 
        THEN ROUND((at.seasonWins / at.seasonGames) * 100, 1) 
        ELSE 0 END AS winrate,
    at.weekWins,
    at.weekGames,
    (at.weekGames - at.weekWins) AS weekLosses,
    at.type,
    at.captainGuid
FROM arena_team at
WHERE at.arenaTeamId = ?
";

$team = null;
$orderedMembers = [];

if ($stmt = $char_db->prepare($teamSql)) {
    $stmt->bind_param("i", $arenaTeamId);
    $stmt->execute();
    $teamResult = $stmt->get_result();
    $team = $teamResult->fetch_assoc();
    $teamResult->free();
    $stmt->close();
}

// Query team members only if team exists
if ($team) {
    $membersSql = "
    SELECT 
        c.guid,
        c.name,
        c.race,
        c.class,
        c.gender,
        atm.personalRating AS personal_rating
    FROM arena_team_member atm
    JOIN characters c ON atm.guid = c.guid
    WHERE atm.arenaTeamId = ?
    ORDER BY c.name ASC
    ";

    if ($stmt = $char_db->prepare($membersSql)) {
        $stmt->bind_param("i", $arenaTeamId);
        $stmt->execute();
        $membersResult = $stmt->get_result();

        $members = [];
        $captain = null;

        while ($row = $membersResult->fetch_assoc()) {
            if ($row['guid'] == $team['captainGuid']) {
                $captain = $row;
            } else {
                $members[] = $row;
            }
        }
        $membersResult->free();
        $stmt->close();

        if ($captain) {
            $orderedMembers[] = $captain;
        }
        $orderedMembers = array_merge($orderedMembers, $members);
    }
}

// Render global website header
require_once $project_root . 'includes/header.php';
?>

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
    
    /* Main content wrapper */
    .arena-content {
        position: relative;
        z-index: 1;
    }
    
    /* Glass effect container */
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
    
    /* Captain row */
    .captain-row {
        background: rgba(242, 207, 82, 0.1) !important;
        border-left: 3px solid #f2cf5b;
    }
    
    .captain-row:hover {
        background: rgba(242, 207, 82, 0.2) !important;
    }
    
    /* Member row hover */
    .member-row:hover {
        background: rgba(242, 207, 82, 0.08) !important;
        cursor: pointer;
    }
    
    .member-row {
        transition: all 0.2s ease-in-out;
    }
    
    /* Table container - FIXED: Ensure horizontal scroll on mobile */
    .table-container {
        scrollbar-width: thin;
        scrollbar-color: #f2cf5b #1f2937;
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
        min-width: 600px;
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
    
    /* Responsive */
    @media (max-width: 767px) {
        body {
            padding-top: 96px;
        }
        
        .glass-container {
            padding: 1.5rem 0.75rem;
        }
    }
</style>

<div class="arena-content min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        <!-- Main Container -->
        <div class="glass-container">
            
            <?php if (!$team): ?>
                <!-- Not Found Alert -->
                <div class="text-center py-12">
                    <div class="text-6xl mb-4 text-[rgba(201,162,39,0.3)]">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p class="text-2xl font-bold text-[#f2cf5b]">
                        <?php echo translate('arenateam_no_team', 'No arena team found.'); ?>
                    </p>
                    <a href="<?php echo $base_path; ?>armory/solo_pvp" class="inline-block mt-6 px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> <?php echo translate('back_to_armory', 'Back to Armory'); ?>
                    </a>
                </div>
            <?php else: ?>

                <!-- Team Header -->
                <div class="mb-8">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-none bg-[rgba(242,207,82,0.1)] border border-[rgba(201,162,39,0.3)] flex items-center justify-center flex-shrink-0">
                                <img src="<?php echo $base_path; ?>img/armory/arena.webp" alt="Arena Team" class="w-10 h-10 object-contain">
                            </div>
                            <div>
                                <h1 class="wow-title text-3xl md:text-5xl font-bold">
                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                </h1>
                                <p class="text-gray-400 text-sm flex items-center gap-2">
                                    <i class="fas fa-tag text-[rgba(201,162,39,0.5)]"></i>
                                    <?php echo translate('arenateam_suffix', 'Arena Team'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-2 px-5 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] font-bold text-lg">
                                <i class="fas fa-trophy"></i>
                                <?php echo getTeamTypeName($team['type']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Bar -->
                <?php include_once $project_root . 'includes/arenanavbar.php'; ?>

                <!-- Team Statistics -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-[#f2cf5b] mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-bar"></i>
                        <?php echo translate('arenateam_team_summary', 'Team Summary'); ?>
                    </h2>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.1)] p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_rating', 'Rating'); ?></p>
                            <p class="text-2xl font-extrabold text-[#f2cf5b]"><?php echo (int)$team['rating']; ?></p>
                        </div>

                        <?php 
                        $winrate = (float)$team['winrate'];
                        $winrateColor = $winrate >= 60 ? 'text-[#2ecc71]' : ($winrate >= 50 ? 'text-[#f2cf5b]' : 'text-[#ef4444]');
                        ?>
                        <div class="bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.1)] p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_winrate', 'Winrate'); ?></p>
                            <p class="text-2xl font-extrabold <?php echo $winrateColor; ?>"><?php echo $winrate; ?>%</p>
                        </div>

                        <div class="bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.1)] p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_season_games', 'Season Games'); ?></p>
                            <p class="text-2xl font-extrabold text-gray-200"><?php echo (int)$team['seasonGames']; ?></p>
                        </div>

                        <div class="bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.1)] p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_season_wins', 'Season Wins'); ?></p>
                            <p class="text-2xl font-extrabold text-[#2ecc71]"><?php echo (int)$team['seasonWins']; ?></p>
                        </div>

                        <div class="bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.1)] p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_season_losses', 'Season Losses'); ?></p>
                            <p class="text-2xl font-extrabold text-[#ef4444]"><?php echo (int)$team['seasonLosses']; ?></p>
                        </div>

                        <div class="bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.1)] p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_week_games', 'Week Games'); ?></p>
                            <p class="text-2xl font-extrabold text-gray-200"><?php echo isset($team['weekGames']) ? (int)$team['weekGames'] : '0'; ?></p>
                        </div>

                        <div class="bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.1)] p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_week_wins', 'Week Wins'); ?></p>
                            <p class="text-2xl font-extrabold text-[#2ecc71]"><?php echo isset($team['weekWins']) ? (int)$team['weekWins'] : '0'; ?></p>
                        </div>

                        <div class="bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.1)] p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_week_losses', 'Week Losses'); ?></p>
                            <p class="text-2xl font-extrabold text-[#ef4444]"><?php echo isset($team['weekLosses']) ? (int)$team['weekLosses'] : '0'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Team Roster -->
                <div>
                    <h2 class="text-xl font-bold text-[#f2cf5b] mb-4 flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        <?php echo translate('arenateam_team_members', 'Team Members'); ?>
                    </h2>

                    <!-- Table - FIXED: Added scroll wrapper -->
                    <div class="table-container overflow-x-auto border border-[rgba(201,162,39,0.15)]">
                        <table class="w-full text-sm text-left min-w-[600px]">
                            <thead>
                                <tr class="bg-[rgba(201,162,39,0.15)] border-b border-[rgba(201,162,39,0.2)] text-[#f2cf5b] uppercase tracking-wider font-semibold">
                                    <th class="py-3 px-3 md:px-4 whitespace-nowrap"><?php echo translate('arenateam_name', 'Name'); ?></th>
                                    <th class="py-3 px-3 md:px-4 text-center whitespace-nowrap"><?php echo translate('arenateam_faction', 'Faction'); ?></th>
                                    <th class="py-3 px-3 md:px-4 text-center whitespace-nowrap"><?php echo translate('arenateam_race', 'Race'); ?></th>
                                    <th class="py-3 px-3 md:px-4 text-center whitespace-nowrap"><?php echo translate('arenateam_class', 'Class'); ?></th>
                                    <th class="py-3 px-3 md:px-4 text-center whitespace-nowrap"><?php echo translate('arenateam_personal_rating', 'Personal Rating'); ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[rgba(201,162,39,0.05)]">
                                <?php if (empty($orderedMembers)): ?>
                                    <tr>
                                        <td colspan="5" class="py-8 px-4 text-center text-[#f2cf5b]">
                                            <i class="fas fa-users-slash text-2xl block mb-2 text-[rgba(201,162,39,0.3)]"></i>
                                            <?php echo translate('arenateam_no_members', 'No members found.'); ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orderedMembers as $member): ?>
                                        <?php 
                                        $faction = getFaction($member['race']); 
                                        $isCaptain = ($member['guid'] == $team['captainGuid']);
                                        ?>
                                        <tr class="<?php echo $isCaptain ? 'captain-row' : 'member-row'; ?> transition-colors duration-150" 
                                            onclick="window.location='<?php echo $base_path; ?>character?guid=<?php echo (int)$member['guid']; ?>';" style="cursor:pointer;">
                                            
                                            <td class="py-3 px-3 md:px-4 font-medium text-gray-200 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <?php if ($isCaptain): ?>
                                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-[rgba(242,207,82,0.2)] border border-[rgba(201,162,39,0.4)]" title="Team Captain">
                                                            <i class="fas fa-crown text-[10px] text-[#f2cf5b]"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="hover:text-[#f2cf5b] transition-colors">
                                                        <?php echo htmlspecialchars($member['name']); ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="py-3 px-3 md:px-4 text-center whitespace-nowrap">
                                                <img src="<?php echo factionIconByName($faction); ?>" alt="<?php echo $faction; ?>" title="<?php echo $faction; ?>" class="inline-block w-6 h-6 rounded-full shadow-md">
                                            </td>

                                            <td class="py-3 px-3 md:px-4 text-center whitespace-nowrap">
                                                <img src="<?php echo raceIcon($member['race'], $member['gender']); ?>" alt="Race" class="inline-block w-6 h-6 rounded-full shadow-md border border-[rgba(201,162,39,0.1)]">
                                            </td>

                                            <td class="py-3 px-3 md:px-4 text-center whitespace-nowrap">
                                                <img src="<?php echo classIcon($member['class']); ?>" alt="Class" class="inline-block w-6 h-6 rounded-full shadow-md border border-[rgba(201,162,39,0.1)]">
                                            </td>

                                            <td class="py-3 px-3 md:px-4 text-center whitespace-nowrap">
                                                <span class="inline-block px-3 py-1 bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.15)] font-semibold text-[#f2cf5b] text-sm">
                                                    <?php echo (int)$member['personal_rating']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once $project_root . 'includes/footer.php'; ?>