<?php
require_once '../../../includes/session.php';
$page_class = "armory";
require_once '../../../includes/header.php';

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
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1 0 auto;
            min-height: calc(100vh - 200px); /* Adjust based on header/footer height */
        }
        .table-container {
            scrollbar-width: thin;
            scrollbar-color: #ffcc00 #1f2937;
            font-family: 'Arial', sans-serif;
            border: 2px double #fcd34d;
            border-top: 1px solid #ffffff;
            border-bottom: 1px solid #ffffff;
            box-shadow: 0 0 10px rgba(252, 211, 77, 0.5);
        }
        .table-container::-webkit-scrollbar {
            width: 8px;
        }
        .table-container::-webkit-scrollbar-track {
            background: #1f2937;
        }
        .table-container::-webkit-scrollbar-thumb {
            background: #ffcc00;
            border-radius: 4px;
        }
        tr {
            cursor: pointer;
        }
        tr:not(.captain-row):hover {
            background-color: #0078c9; /* Matches navbar hover */
            transition: background-color 0.2s ease-in-out;
        }
        tr.captain-row {
            background: linear-gradient(to right, #fcd34d, #d97706);
        }
        tr.captain-row:hover {
            filter: brightness(1.2);
            transition: filter 0.2s ease-in-out;
        }
        .arena-icon, .leader-icon {
            width: 24px;
            height: 24px;
            vertical-align: middle;
            margin-right: 4px;
        }
        .team-header {
            background: linear-gradient(to right, #4338ca, #1e1b4b);
            border: 2px double #fcd34d;
            border-top: 1px solid #ffffff;
            border-bottom: 1px solid #ffffff;
            text-shadow: 0 0 8px rgba(252, 211, 77, 0.8);
        }
        .summary-container {
            background: linear-gradient(to right, #4338ca, #1e1b4b);
            border: 2px double #fcd34d;
            border-top: 1px solid #ffffff;
            border-bottom: 1px solid #ffffff;
            box-shadow: 0 0 10px rgba(252, 211, 77, 0.5);
            transition: all 0.3s ease-in-out;
        }
        .summary-container:hover {
            border-color: #4dd0e1;
            transform: scale(1.02);
        }
        .summary-item-2v2 {
            background: linear-gradient(to right, #dc2626, #7f1d1d);
        }
        .summary-item-3v3 {
            background: linear-gradient(to right, #15803d, #064e3b);
        }
        .summary-item-5v5 {
            background: linear-gradient(to right, #1e40af, #1e1b4b);
        }
        .summary-item-default {
            background: linear-gradient(to right, #4338ca, #1e1b4b);
        }
        .summary-item {
            box-shadow: 0 0 5px rgba(252, 211, 77, 0.3);
            transition: filter 0.2s ease-in-out;
        }
        .summary-item:hover {
            filter: brightness(1.2);
        }
        .summary-value {
            text-shadow: 0 0 5px rgba(252, 211, 77, 0.6);
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
<div class="main-content">
    <div class="container max-w-6xl mx-auto ml-0 mr-auto sm:mx-auto px-2 py-4 sm:px-4 sm:py-8">
        <?php if (!$team): ?>
            <div class="text-center text-base sm:text-lg text-amber-400 bg-gray-800 p-4 sm:p-6 rounded-lg shadow-lg max-w-6xl mx-auto">
                No arena team found.
            </div>
        <?php else: ?>
            <h1 class="team-header text-[2rem] sm:text-[2.5rem] font-bold text-center text-gold-300 mb-6 p-2 sm:p-4 rounded-xl max-w-6xl mx-auto">
                <img src="/Sahtout/img/armory/arena.webp" alt="Arena Team" title="Arena Team" class="arena-icon inline-block">
                <?php echo htmlspecialchars($team['team_name']); ?> - <?php echo getTeamTypeName($team['type']); ?> Arena Team
            </h1>

            <?php include_once '../../../includes/arenanavbar.php'; ?>

            <!-- Team Summary -->
            <div class="summary-container p-4 sm:p-6 rounded-lg shadow-lg mb-8 max-w-6xl mx-auto">
                <h2 class="text-xl sm:text-2xl font-bold text-amber-400 mb-4">Team Summary</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> p-2 sm:p-3 rounded-lg">
                        <p class="text-base sm:text-lg text-gray-300">Rating</p>
                        <p class="text-lg sm:text-xl font-semibold text-gold-300 summary-value"><?php echo $team['rating']; ?></p>
                    </div>
                    <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> p-2 sm:p-3 rounded-lg">
                        <p class="text-base sm:text-lg text-gray-300">Winrate</p>
                        <p class="text-lg sm:text-xl font-semibold text-gold-300 summary-value"><?php echo $team['winrate']; ?>%</p>
                    </div>
                    <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> p-2 sm:p-3 rounded-lg">
                        <p class="text-base sm:text-lg text-gray-300">Season Games</p>
                        <p class="text-lg sm:text-xl font-semibold text-gold-300 summary-value"><?php echo $team['seasonGames']; ?></p>
                    </div>
                    <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> p-2 sm:p-3 rounded-lg">
                        <p class="text-base sm:text-lg text-gray-300">Season Wins</p>
                        <p class="text-lg sm:text-xl font-semibold text-gold-300 summary-value"><?php echo $team['seasonWins']; ?></p>
                    </div>
                    <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> p-2 sm:p-3 rounded-lg">
                        <p class="text-base sm:text-lg text-gray-300">Season Losses</p>
                        <p class="text-lg sm:text-xl font-semibold text-gold-300 summary-value"><?php echo $team['seasonLosses']; ?></p>
                    </div>
                    <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> p-2 sm:p-3 rounded-lg">
                        <p class="text-base sm:text-lg text-gray-300">Week Games</p>
                        <p class="text-lg sm:text-xl font-semibold text-gold-300 summary-value"><?php echo $team['weekGames'] ?? 'N/A'; ?></p>
                    </div>
                    <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> p-2 sm:p-3 rounded-lg">
                        <p class="text-base sm:text-lg text-gray-300">Week Wins</p>
                        <p class="text-lg sm:text-xl font-semibold text-gold-300 summary-value"><?php echo $team['weekWins'] ?? 'N/A'; ?></p>
                    </div>
                    <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> p-2 sm:p-3 rounded-lg">
                        <p class="text-base sm:text-lg text-gray-300">Week Losses</p>
                        <p class="text-lg sm:text-xl font-semibold text-gold-300 summary-value"><?php echo $team['weekLosses'] ?? 'N/A'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Team Members -->
            <h2 class="text-xl sm:text-2xl font-bold text-amber-400 mb-4 max-w-6xl mx-auto">Team Members</h2>
            <div class="table-container overflow-x-auto rounded-lg shadow-lg max-w-6xl mx-auto">
                <table class="w-full text-xs sm:text-sm text-center bg-gray-900/90">
                    <thead class="text-gold-300 uppercase" style="background: linear-gradient(to right, #4338ca, #1e1b4b);">
                        <tr>
                            <th class="py-2 px-4 sm:py-3 sm:px-6">Name</th>
                            <th class="py-2 px-4 sm:py-3 sm:px-6">Faction</th>
                            <th class="py-2 px-4 sm:py-3 sm:px-6">Race</th>
                            <th class="py-2 px-4 sm:py-3 sm:px-6">Class</th>
                            <th class="py-2 px-4 sm:py-3 sm:px-6">Personal Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orderedMembers) == 0): ?>
                            <tr>
                                <td colspan="5" class="py-2 px-4 sm:py-3 sm:px-6 text-base sm:text-lg text-amber-400">No members found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orderedMembers as $member): ?>
                                <?php $faction = getFaction($member['race']); ?>
                                <tr class="<?php echo $member['guid'] == $team['captainGuid'] ? 'captain-row' : ''; ?> transition duration-200" onclick="window.location='/sahtout/pages/character.php?guid=<?php echo $member['guid']; ?>';">
                                    <td class="py-2 px-4 sm:py-3 sm:px-6">
                                        <?php if ($member['guid'] == $team['captainGuid']): ?>
                                            <img src="/Sahtout/img/armory/leader.png" alt="Team Captain" title="Team Captain" class="leader-icon inline-block">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($member['name']); ?>
                                    </td>
                                    <td class="py-2 px-4 sm:py-3 sm:px-6">
                                        <img src="<?php echo factionIconByName($faction); ?>" alt="<?php echo $faction; ?>" title="<?php echo $faction; ?>" class="inline-block w-5 h-5 sm:w-6 sm:h-6 rounded">
                                    </td>
                                    <td class="py-2 px-4 sm:py-3 sm:px-6">
                                        <img src="<?php echo raceIcon($member['race'], $member['gender']); ?>" alt="Race" class="inline-block w-5 h-5 sm:w-6 sm:h-6 rounded">
                                    </td>
                                    <td class="py-2 px-4 sm:py-3 sm:px-6">
                                        <img src="<?php echo classIcon($member['class']); ?>" alt="Class" class="inline-block w-5 h-5 sm:w-6 sm:h-6 rounded">
                                    </td>
                                    <td class="py-2 px-4 sm:py-3 sm:px-6"><?php echo $member['personal_rating']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once '../../../includes/footer.php'; ?>
</body>
</html>