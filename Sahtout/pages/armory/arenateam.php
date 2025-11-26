<?php
define('ALLOWED_ACCESS', true);
// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/header.php';

// Functions to get faction and icon paths
function getFaction($race) {
    $alliance = [1,3,4,7,11,22,25,29];
    return in_array($race, $alliance) ? 'Alliance' : 'Horde';
}

function factionIconByName($faction) {
    global $base_path;
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

function getTeamTypeName($type) {
    switch ($type) {
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
    <title><?php echo  $site_title_name ." ". translate('arenateam_page_title', 'Arena Team Details'); ?></title>
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
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/armory/arenateam.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/armory/arenanavbar.css">
</head>
<style>
       :root{
            --bg-armory:url('<?php echo $base_path; ?>img/backgrounds/bg-armory.jpg');
        }
    </style>
<body class="tw-bg-gray-900 tw-text-white">
    <div class="arena-content">
        <div class="tw-container tw-max-w-6xl tw-mx-auto tw-ml-0 tw-mr-auto sm:tw-mx-auto tw-px-2 tw-py-4 sm:tw-px-4 sm:tw-py-8">
            <?php if (!$team): ?>
                <div class="tw-text-center tw-text-base sm:tw-text-lg tw-text-amber-400 tw-bg-gray-800 tw-p-4 sm:tw-p-6 tw-rounded-lg tw-shadow-lg tw-max-w-6xl tw-mx-auto">
                    <?php echo translate('arenateam_no_team', 'No arena team found.'); ?>
                </div>
            <?php else: ?>
                <h1 class="team-header tw-text-[2rem] sm:tw-text-[2.5rem] tw-font-bold tw-text-center tw-text-gold-300 tw-mb-6 tw-p-2 sm:tw-p-4 tw-rounded-xl tw-max-w-6xl tw-mx-auto">
                    <img src="<?php echo $base_path; ?>img/armory/arena.webp" alt="Arena Team" title="Arena Team" class="arena-icon inline-block">
                    <?php echo htmlspecialchars($team['team_name']); ?> - <?php echo getTeamTypeName($team['type']); ?> <?php echo translate('arenateam_suffix', 'Arena Team'); ?>
                </h1>

                <div class="arena-nav-wrapper">
                    <?php include_once $project_root . 'includes/arenanavbar.php'; ?>
                </div>

                <!-- Team Summary -->
                <div class="summary-container tw-p-4 sm:tw-p-6 tw-rounded-lg tw-shadow-lg tw-mb-8 tw-max-w-6xl tw-mx-auto">
                    <h2 class="tw-text-xl sm:tw-text-2xl tw-font-bold tw-text-amber-400 tw-mb-4"><?php echo translate('arenateam_team_summary', 'Team Summary'); ?></h2>
                    <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-4 tw-gap-4 tw-text-center">
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300"><?php echo translate('arenateam_rating', 'Rating'); ?></p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['rating']; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300"><?php echo translate('arenateam_winrate', 'Winrate'); ?></p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['winrate']; ?>%</p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300"><?php echo translate('arenateam_season_games', 'Season Games'); ?></p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['seasonGames']; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300"><?php echo translate('arenateam_season_wins', 'Season Wins'); ?></p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['seasonWins']; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300"><?php echo translate('arenateam_season_losses', 'Season Losses'); ?></p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['seasonLosses']; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300"><?php echo translate('arenateam_week_games', 'Week Games'); ?></p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['weekGames'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300"><?php echo translate('arenateam_week_wins', 'Week Wins'); ?></p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['weekWins'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="summary-item summary-item-<?php echo $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : ($team['type'] == 5 ? '5v5' : 'default')); ?> tw-p-2 sm:tw-p-3 tw-rounded-lg">
                            <p class="tw-text-base sm:tw-text-lg tw-text-gray-300"><?php echo translate('arenateam_week_losses', 'Week Losses'); ?></p>
                            <p class="tw-text-lg sm:tw-text-xl tw-font-semibold tw-text-gold-300 summary-value"><?php echo $team['weekLosses'] ?? 'N/A'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Team Members -->
                <h2 class="tw-text-xl sm:tw-text-2xl tw-font-bold tw-text-amber-400 tw-mb-4 tw-max-w-6xl tw-mx-auto"><?php echo translate('arenateam_team_members', 'Team Members'); ?></h2>
                <div class="table-container tw-overflow-x-auto tw-rounded-lg tw-shadow-lg tw-max-w-6xl tw-mx-auto">
                    <table class="tw-w-full tw-text-xs sm:tw-text-sm tw-text-center tw-bg-gray-900/90">
                        <thead class="tw-text-gold-300 tw-uppercase" style="background: linear-gradient(to right, #4338ca, #1e1b4b);">
                            <tr>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6"><?php echo translate('arenateam_name', 'Name'); ?></th>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6"><?php echo translate('arenateam_faction', 'Faction'); ?></th>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6"><?php echo translate('arenateam_race', 'Race'); ?></th>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6"><?php echo translate('arenateam_class', 'Class'); ?></th>
                                <th class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6"><?php echo translate('arenateam_personal_rating', 'Personal Rating'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($orderedMembers) == 0): ?>
                                <tr>
                                    <td colspan="5" class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6 tw-text-base sm:tw-text-lg tw-text-amber-400"><?php echo translate('arenateam_no_members', 'No members found.'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orderedMembers as $member): ?>
                                    <?php $faction = getFaction($member['race']); ?>
                                    <tr class="<?php echo $member['guid'] == $team['captainGuid'] ? 'captain-row' : ''; ?> tw-transition tw-duration-200" onclick="window.location='<?php echo $base_path; ?>pages/character.php?guid=<?php echo $member['guid']; ?>';">
                                        <td class="tw-py-2 tw-px-4 sm:tw-py-3 sm:tw-px-6">
                                            <?php if ($member['guid'] == $team['captainGuid']): ?>
                                                <img src="<?php echo $base_path; ?>img/armory/leader.png" alt="Team Captain" title="Team Captain" class="leader-icon inline-block">
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
    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>