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

// Render global website header (opens html/head/body and primary layout containers)
require_once $project_root . 'includes/header.php';
?>

<!-- Page Specific Assets -->
<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/armory/arenanavbar.css">
<style>
    :root {
        --bg-armory: url('<?php echo $base_path; ?>img/backgrounds/bg-armory.jpg');
    }
    
    body {
        background: var(--bg-armory) no-repeat center center fixed;
        background-size: cover;
        position: relative;
        min-height: 100vh;
        padding-top: 112px;
    }

    body::before {
        display: none;
    }

    .arena-page-wrapper {
        position: relative;
        min-height: 100vh;
        color: #f1f5f9;
    }
    
    .arena-content {
        position: relative;
        z-index: 1;
    }
    
    .wow-gold-gradient {
        background: linear-gradient(180deg, #FFE89C 0%, #D4A341 50%, #8A641B 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .wow-glass-panel {
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(212, 163, 65, 0.2);
    }
    
    .wow-glass-card {
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.2s ease-in-out;
    }
    
    .wow-glass-card:hover {
        border-color: rgba(212, 163, 65, 0.4);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.5);
    }
    
    .table-container {
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    
    .captain-row {
        background: rgba(252, 211, 77, 0.1);
        border-left: 3px solid #fcd34d;
    }
    
    tbody tr:not(.captain-row):hover {
        background: rgba(0, 120, 201, 0.3) !important;
        transition: background-color 0.2s ease-in-out;
    }
    
    .arena-nav-wrapper .nav-container {
        border: 2px double #4338ca;
        margin-top: 20px;
    }

    @media (max-width: 767px) {
        body {
            padding-top: 96px;
        }
    }
</style>

<div class="arena-page-wrapper">
    <div class="arena-content py-4 sm:py-8 px-3 sm:px-6">
        <div class="container max-w-6xl mx-auto">
            
            <?php if (!$team): ?>
                <!-- Not Found Alert -->
                <div class="wow-glass-panel rounded-2xl p-8 text-center max-w-2xl mx-auto shadow-2xl">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-500/10 flex items-center justify-center border border-amber-500/30">
                        <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <p class="text-lg sm:text-xl font-semibold text-amber-400">
                        <?php echo translate('arenateam_no_team', 'No arena team found.'); ?>
                    </p>
                </div>
            <?php else: ?>

                <!-- Main Team Hero Header -->
                <div class="wow-glass-panel rounded-2xl p-6 sm:p-8 mb-8 shadow-2xl relative overflow-hidden">
                    <div class="absolute -top-24 -right-24 w-72 h-72 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-6 relative z-10">
                        <div class="flex items-center gap-4 sm:gap-6 text-center sm:text-left">
                            <div class="relative flex-shrink-0">
                                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-amber-500/20 to-slate-900 border border-amber-500/40 flex items-center justify-center shadow-lg">
                                    <img src="<?php echo $base_path; ?>img/armory/arena.webp" alt="Arena Team" class="w-10 h-10 sm:w-12 sm:h-12 object-contain">
                                </div>
                            </div>
                            <div>
                                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight wow-gold-gradient mb-1">
                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                </h1>
                                <p class="text-slate-400 text-sm sm:text-base flex items-center justify-center sm:justify-start gap-2">
                                    <span><?php echo translate('arenateam_suffix', 'Arena Team'); ?></span>
                                </p>
                            </div>
                        </div>

                        <!-- Bracket Badge -->
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-gradient-to-r from-amber-500/20 to-yellow-600/20 border border-amber-500/40 text-amber-300 font-bold text-lg sm:text-xl shadow-inner">
                                <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php echo getTeamTypeName($team['type']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Bar -->
                <div class="arena-nav-wrapper mb-8">
                    <?php include_once $project_root . 'includes/arenanavbar.php'; ?>
                </div>

                <!-- Team Statistics Section -->
                <div class="wow-glass-panel rounded-2xl p-6 sm:p-8 mb-8 shadow-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl sm:text-2xl font-bold text-amber-400 flex items-center gap-3">
                            <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <?php echo translate('arenateam_team_summary', 'Team Summary'); ?>
                        </h2>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                        <div class="wow-glass-card p-4 rounded-xl text-center">
                            <p class="text-xs sm:text-sm text-slate-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_rating', 'Rating'); ?></p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-amber-300"><?php echo (int)$team['rating']; ?></p>
                        </div>

                        <?php 
                        $winrate = (float)$team['winrate'];
                        $winrateColor = $winrate >= 60 ? 'text-emerald-400' : ($winrate >= 50 ? 'text-yellow-400' : 'text-red-400');
                        ?>
                        <div class="wow-glass-card p-4 rounded-xl text-center">
                            <p class="text-xs sm:text-sm text-slate-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_winrate', 'Winrate'); ?></p>
                            <p class="text-2xl sm:text-3xl font-extrabold <?php echo $winrateColor; ?>"><?php echo $winrate; ?>%</p>
                        </div>

                        <div class="wow-glass-card p-4 rounded-xl text-center">
                            <p class="text-xs sm:text-sm text-slate-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_season_games', 'Season Games'); ?></p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-slate-100"><?php echo (int)$team['seasonGames']; ?></p>
                        </div>

                        <div class="wow-glass-card p-4 rounded-xl text-center">
                            <p class="text-xs sm:text-sm text-slate-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_season_wins', 'Season Wins'); ?></p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-400"><?php echo (int)$team['seasonWins']; ?></p>
                        </div>

                        <div class="wow-glass-card p-4 rounded-xl text-center">
                            <p class="text-xs sm:text-sm text-slate-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_season_losses', 'Season Losses'); ?></p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-rose-400"><?php echo (int)$team['seasonLosses']; ?></p>
                        </div>

                        <div class="wow-glass-card p-4 rounded-xl text-center">
                            <p class="text-xs sm:text-sm text-slate-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_week_games', 'Week Games'); ?></p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-slate-200"><?php echo isset($team['weekGames']) ? (int)$team['weekGames'] : 'N/A'; ?></p>
                        </div>

                        <div class="wow-glass-card p-4 rounded-xl text-center">
                            <p class="text-xs sm:text-sm text-slate-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_week_wins', 'Week Wins'); ?></p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-400"><?php echo isset($team['weekWins']) ? (int)$team['weekWins'] : 'N/A'; ?></p>
                        </div>

                        <div class="wow-glass-card p-4 rounded-xl text-center">
                            <p class="text-xs sm:text-sm text-slate-400 uppercase tracking-wider font-semibold mb-1"><?php echo translate('arenateam_week_losses', 'Week Losses'); ?></p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-rose-400"><?php echo isset($team['weekLosses']) ? (int)$team['weekLosses'] : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Team Roster Section -->
                <div class="wow-glass-panel rounded-2xl p-6 sm:p-8 shadow-2xl">
                    <h2 class="text-xl sm:text-2xl font-bold text-amber-400 mb-6 flex items-center gap-3">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <?php echo translate('arenateam_team_members', 'Team Members'); ?>
                    </h2>

                    <div class="table-container overflow-x-auto rounded-xl border border-slate-800">
                        <table class="w-full text-xs sm:text-sm text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-900/90 border-b border-amber-500/20 text-amber-400 uppercase tracking-wider font-semibold">
                                    <th class="py-4 px-6"><?php echo translate('arenateam_name', 'Name'); ?></th>
                                    <th class="py-4 px-6 text-center"><?php echo translate('arenateam_faction', 'Faction'); ?></th>
                                    <th class="py-4 px-6 text-center"><?php echo translate('arenateam_race', 'Race'); ?></th>
                                    <th class="py-4 px-6 text-center"><?php echo translate('arenateam_class', 'Class'); ?></th>
                                    <th class="py-4 px-6 text-center"><?php echo translate('arenateam_personal_rating', 'Personal Rating'); ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <?php if (empty($orderedMembers)): ?>
                                    <tr>
                                        <td colspan="5" class="py-8 px-6 text-center text-base text-amber-400/80">
                                            <?php echo translate('arenateam_no_members', 'No members found.'); ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orderedMembers as $member): ?>
                                        <?php 
                                        $faction = getFaction($member['race']); 
                                        $isCaptain = ($member['guid'] == $team['captainGuid']);
                                        ?>
                                        <tr class="<?php echo $isCaptain ? 'captain-row' : 'hover:bg-slate-800/40'; ?> transition-colors duration-150 cursor-pointer" 
                                            onclick="window.location='<?php echo $base_path; ?>pages/character.php?guid=<?php echo (int)$member['guid']; ?>';">
                                            
                                            <td class="py-4 px-6 font-medium text-slate-100">
                                                <div class="flex items-center gap-3">
                                                    <?php if ($isCaptain): ?>
                                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-500/20 border border-amber-400/40" title="Team Captain">
                                                            <img src="<?php echo $base_path; ?>img/armory/leader.png" alt="Team Captain" class="w-3.5 h-3.5">
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="w-6"></span>
                                                    <?php endif; ?>
                                                    <span class="text-base hover:text-amber-300 transition-colors">
                                                        <?php echo htmlspecialchars($member['name']); ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="py-4 px-6 text-center">
                                                <img src="<?php echo factionIconByName($faction); ?>" alt="<?php echo $faction; ?>" title="<?php echo $faction; ?>" class="inline-block w-7 h-7 rounded-lg shadow-sm">
                                            </td>

                                            <td class="py-4 px-6 text-center">
                                                <img src="<?php echo raceIcon($member['race'], $member['gender']); ?>" alt="Race" class="inline-block w-7 h-7 rounded-lg border border-slate-700 shadow-sm">
                                            </td>

                                            <td class="py-4 px-6 text-center">
                                                <img src="<?php echo classIcon($member['class']); ?>" alt="Class" class="inline-block w-7 h-7 rounded-lg border border-slate-700 shadow-sm">
                                            </td>

                                            <td class="py-4 px-6 text-center">
                                                <span class="inline-block px-3 py-1 rounded-md bg-slate-800 border border-slate-700 font-semibold text-amber-300">
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