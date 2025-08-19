<?php
define('ALLOWED_ACCESS', true);
require_once '../../includes/session.php';
$page_class = "armory";
require_once '../../includes/header.php';

// Functions to get faction and icon paths
function getFaction($race) {
    $alliance = [1,3,4,7,11,22,25,29];
    return in_array($race, $alliance) ? 'Alliance' : 'Horde';
}

function factionIconByName($faction) {
    return "/Sahtout/img/accountimg/faction/" . strtolower($faction) . ".png";
}

function raceIcon($race, $gender) {
    $genderFolder = ($gender == 0) ? 'male' : 'female';
    $raceMap = [
        1 => 'human', 2 => 'orc', 3 => 'dwarf', 4 => 'nightelf',
        5 => 'undead', 6 => 'tauren', 7 => 'gnome', 8 => 'troll',
        9 => 'goblin', 10 => 'bloodelf', 11 => 'draenei',
        22 => 'worgen', 25 => 'pandaren_alliance', 26 => 'pandaren_horde',
        29 => 'voidelf'
    ];
    $raceName = isset($raceMap[$race]) ? $raceMap[$race] : 'unknown';
    return "/Sahtout/img/accountimg/race/{$genderFolder}/{$raceName}.png";
}

function classIcon($class) {
    $classMap = [
        1 => 'warrior', 2 => 'paladin', 3 => 'hunter', 4 => 'rogue',
        5 => 'priest', 6 => 'deathknight', 7 => 'shaman', 8 => 'mage',
        9 => 'warlock', 10 => 'monk', 11 => 'druid', 12 => 'demonhunter'
    ];
    $className = isset($classMap[$class]) ? $classMap[$class] : 'unknown';
    return "/Sahtout/img/accountimg/class/{$className}.webp";
}

function getTeamTypeName($type) {
    switch ($type) {
        case 2:
            return '2v2';
        case 3:
            return '3v3';
        case 5:
            return '5v5';
        default:
            return 'Unknown';
    }
}

// Get arenaTeamId from URL and sanitize
$arenaTeamId = isset($_GET['arenaTeamId']) ? intval($_GET['arenaTeamId']) : 0;

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
$stmt = $char_db->prepare($teamSql);
$stmt->bind_param("i", $arenaTeamId);
$stmt->execute();
$teamResult = $stmt->get_result();
$team = $teamResult->fetch_assoc();
$stmt->close();

// Query team members
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
$stmt = $char_db->prepare($membersSql);
$stmt->bind_param("i", $arenaTeamId);
$stmt->execute();
$membersResult = $stmt->get_result();

$members = [];
$captain = null;
while ($row = $membersResult->fetch_assoc()) {
    if ($row['guid'] == ($team['captainGuid'] ?? 0)) {
        $captain = $row;
    } else {
        $members[] = $row;
    }
}
$stmt->close();

// Place captain at the top
$orderedMembers = [];
if ($captain) {
    $orderedMembers[] = $captain;
}
$orderedMembers = array_merge($orderedMembers, $members);
?>

<!DOCTYPE html>
<html>
<head>
    <title>WoW Armory - Arena Team Details</title>
    <!-- Load Tailwind CSS with a custom configuration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            prefix: 'tw-', // Prefix all Tailwind classes
            corePlugins: {
                preflight: false // Disable Tailwind's reset
            }
        }
    </script>
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .arena-content {
            flex: 1 0 auto;
            min-height: calc(100vh - 200px); /* Adjust based on header/footer height */
            margin-top: 120px; /* Increased for dropdown clearance */
            overflow: visible !important;
        }
        .arena-content .tw-container {
            overflow: visible !important;
        }
        .arena-content .table-container {
            scrollbar-width: thin;
            scrollbar-color: #ffcc00 #1f2937;
            font-family: 'Arial', sans-serif;
            border: 2px double #fcd34d;
            border-top: 1px solid #ffffff;
            border-bottom: 1px solid #ffffff;
            box-shadow: 0 0 10px rgba(252, 211, 77, 0.5);
        }
        .arena-content .table-container::-webkit-scrollbar {
            width: 8px;
        }
        .arena-content .table-container::-webkit-scrollbar-track {
            background: #1f2937;
        }
        .arena-content .table-container::-webkit-scrollbar-thumb {
            background: #ffcc00;
            border-radius: 4px;
        }
        .arena-content tr {
            cursor: pointer;
        }
        .arena-content tr:not(.captain-row):hover {
            background-color: #0078c9; /* Matches navbar hover */
            transition: background-color 0.2s ease-in-out;
        }
        .arena-content tr.captain-row {
            background: linear-gradient(to right, #fcd34d, #d97706);
        }
        .arena-content tr.captain-row:hover {
            filter: brightness(1.2);
            transition: filter 0.2s ease-in-out;
        }
        .arena-content .arena-icon, .arena-content .leader-icon {
            width: 24px;
            height: 24px;
            vertical-align: middle;
            margin-right: 4px;
        }
        .arena-content .team-header {
            background: linear-gradient(to right, #4338ca, #1e1b4b);
            border: 2px double #fcd34d;
            border-top: 1px solid #ffffff;
            border-bottom: 1px solid #ffffff;
            text-shadow: 0 0 8px rgba(252, 211, 77, 0.8);
        }
        .arena-content .summary-container {
            background: linear-gradient(to right, #4338ca, #1e1b4b);
            border: 2px double #fcd34d;
            border-top: 1px solid #ffffff;
            border-bottom: 1px solid #ffffff;
            box-shadow: 0 0 10px rgba(252, 211, 77, 0.5);
            transition: all 0.3s ease-in-out;
        }
        .arena-content .summary-container:hover {
            border-color: #4dd0e1;
            transform: scale(1.02);
        }
        .arena-content .summary-item-2v2 {
            background: linear-gradient(to right, #dc2626, #7f1d1d);
        }
        .arena-content .summary-item-3v3 {
            background: linear-gradient(to right, #15803d, #064e3b);
        }
        .arena-content .summary-item-5v5 {
            background: linear-gradient(to right, #1e40af, #1e1b4b);
        }
        .arena-content .summary-item-default {
            background: linear-gradient(to right, #4338ca, #1e1b4b);
        }
        .arena-content .summary-item {
            box-shadow: 0 0 5px rgba(252, 211, 77, 0.3);
            transition: filter 0.2s ease-in-out;
        }
        .arena-content .summary-item:hover {
            filter: brightness(1.2);
        }
        .arena-content .summary-value {
            text-shadow: 0 0 5px rgba(252, 211, 77, 0.6);
        }

        /* Scope nav-container override to arena-nav-wrapper */
        .arena-nav-wrapper .nav-container {
            border: 2px double #4338ca;
            margin-top: 20px;
        }
        .arena-content a {
            color: #ffffff;
            text-decoration: none;
        }
    </style>
</head>
<body class="tw-bg-gray-900 tw-text-white">
    <div class="arena-content">
        <div class="tw-container tw-max-w-6xl tw-mx-auto tw-ml-0 tw-mr-auto sm:tw-mx-auto tw-px-2 tw-py-4 sm:tw-px-4 sm:tw-py-8">
            <?php if (!$team): ?>
                <div class="tw-text-center tw-text-base sm:tw-text-lg tw-text-amber-400 tw-bg-gray-800 tw-p-4 sm:tw-p-6 tw-rounded-lg tw-shadow-lg tw-max-w-6xl tw-mx-auto">
                    No arena team found.
                </div>
            <?php else: ?>
                <h1 class="team-header tw-text-[2rem] sm:tw-text-[2.5rem] tw-font-bold tw-text-center tw-text-gold-300 tw-mb-6 tw-p-2 sm:tw-p-4 tw-rounded-xl tw-max-w-6xl tw-mx-auto">
                    <img src="/Sahtout/img/armory/arena.webp" alt="Arena Team" title="Arena Team" class="arena-icon inline-block">
                    <?php echo htmlspecialchars($team['team_name']); ?> - <?php echo getTeamTypeName($team['type']); ?> Arena Team
                </h1>

                <div class="arena-nav-wrapper">
                    <?php include_once '../../includes/arenanavbar.php'; ?>
                </div>

                <!-- Team Summary -->
                <div class="summary-container tw-p-4 sm:tw-p-6 tw-rounded-lg tw-shadow-lg tw-mb-8 tw-max-w-6xl tw-mx-auto">
                    <h2 class="tw-text-xl sm:tw-text-2xl tw-font-bold tw-text-amber-400 tw-mb-4">Team Summary</h2>
                    <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-4 tw-gap-4 tw-text-center">
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300">Rating</p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['rating']; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300">Winrate</p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['winrate']; ?>%</p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300">Season Games</p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['seasonGames']; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300">Season Wins</p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['seasonWins']; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300">Season Losses</p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['seasonLosses']; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300">Week Games</p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['weekGames'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300">Week Wins</p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['weekWins'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300">Week Losses</p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['weekLosses'] ?? 'N/A'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Team Members -->
                <h2 class="tw-text-xl sm:tw-text-2xl tw-font-bold tw-text-amber-400 tw-mb-4 tw-max-w-6xl tw-mx-auto">Team Members</h2>
                <div class="table-container tw-overflow-x-auto tw-rounded-lg tw-shadow-lg tw-max-w-6xl tw-mx-auto">
                    <table class="tw-w-full tw-text-xs sm:tw-text-sm tw-text-center tw-bg-gray-900/90">
                        <thead class="tw-text-gold-300 tw-uppercase" style="background: linear-gradient(to right, #4338ca, #1e1b4b);">
                            <tr>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">Name</th>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">Faction</th>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">Race</th>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">Class</th>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">Personal Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($orderedMembers) == 0): ?>
                                <tr>
                                    <td colspan="5" class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6 tw-text-base sm:tw-text-lg tw-text-amber-400">No members found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orderedMembers as $member): ?>
                                    <?php $faction = getFaction($member['race']); ?>
                                    <tr class="<?php echo $member['guid'] == $team['captainGuid'] ? 'captain-row' : ''; ?> tw-transition tw-duration-200" onclick="window.location='/sahtout/pages/character.php?guid=<?php echo $member['guid']; ?>';">
                                        <td class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">
                                            <?php if ($member['guid'] == $team['captainGuid']): ?>
                                                <img src="/Sahtout/img/armory/leader.png" alt="Team Captain" title="Team Captain" class="leader-icon inline-block">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($member['name']); ?>
                                        </td>
                                        <td class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">
                                            <img src="<?php echo factionIconByName($faction); ?>" alt="<?php echo $faction; ?>" title="<?php echo $faction; ?>" class="tw-inline-block tw-w-5 tw-h-5 sm:tw-w-6 sm:tw-h-6 tw-rounded">
                                        </td>
                                        <td class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">
                                            <img src="<?php echo raceIcon($member['race'], $member['gender']); ?>" alt="Race" class="tw-inline-block tw-w-5 tw-h-5 sm:tw-w-6 sm:tw-h-6 tw-rounded">
                                        </td>
                                        <td class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">
                                            <img src="<?php echo classIcon($member['class']); ?>" alt="Class" class="tw-inline-block tw-w-5 tw-h-5 sm:tw-w-6 sm:tw-h-6 tw-rounded">
                                        </td>
                                        <td class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6"><?php echo $member['personal_rating']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once '../../includes/footer.php'; ?>
</body>
</html>