<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../../includes/paths.php';
// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/header.php';

// Functions to get faction and icon paths
function getFaction($race) {
    $alliance = [1, 3, 4, 7, 11, 22, 25, 29];
    return in_array($race, $alliance) ? 'Alliance' : 'Horde';
}

function factionIconByName($faction) {
    global $base_path;
    return $base_path . "img/accountimg/faction/" . strtolower($faction) . ".png";
}

// Query top 50 2v2 arena teams 
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
WHERE at.type = 2 -- 2v2 teams
AND atm.guid = at.captainGuid
ORDER BY at.rating DESC
LIMIT 50
";

$result = $char_db->query($sql);

$teams = [];
while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo  $site_title_name ." ". translate('arena_2v2_page_title', 'Top 50 2v2 Arena Teams'); ?></title>
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
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/armory/arena_2v2.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/armory/arenanavbar.css">
</head>
<style>
       :root{
            --bg-armory:url('<?php echo $base_path; ?>img/backgrounds/bg-armory.jpg');
            --hover-wow-gif: url('<?php echo $base_path; ?>img/hover_wow.gif');
        }
    </style>
<body class="<?php echo $page_class; ?>">
    <div class="arena-content tw-bg-900 tw-text-white">
        <div class="tw-container tw-mx-auto tw-px-4 tw-py-8">
            <h1 class="tw-text-4xl tw-font-bold tw-text-center tw-text-amber-400 tw-mb-6"><?php echo translate('arena_2v2_title', 'Top 50 2v2 Arena Teams'); ?></h1>

            <?php include_once $project_root . 'includes/arenanavbar.php'; ?>

            <?php if (count($teams) == 0): ?>
                <div class="tw-text-center tw-text-lg tw-text-amber-400 tw-bg-gray-800 tw-p-6 tw-rounded-lg tw-shadow-lg">
                    <?php echo translate('arena_2v2_no_teams', 'No 2v2 arena teams found.'); ?>
                </div>
            <?php else: ?>
                <div class="table-container tw-overflow-x-auto tw-rounded-lg tw-shadow-lg">
                    <table class="tw-w-full tw-text-sm tw-text-center tw-bg-gray-800">
                        <thead class="tw-bg-gray-700 tw-text-amber-400 tw-uppercase">
                            <tr>
                                <th class="tw-py-3 tw-px-6"><?php echo translate('arena_2v2_rank', 'Rank'); ?></th>
                                <th class="tw-py-3 tw-px-6"><?php echo translate('arena_2v2_name', 'Name'); ?></th>
                                <th class="tw-py-3 tw-px-6"><?php echo translate('arena_2v2_faction', 'Faction'); ?></th>
                                <th class="tw-py-3 tw-px-6"><?php echo translate('arena_2v2_wins', 'Wins'); ?></th>
                                <th class="tw-py-3 tw-px-6"><?php echo translate('arena_2v2_losses', 'Losses'); ?></th>
                                <th class="tw-py-3 tw-px-6"><?php echo translate('arena_2v2_winrate', 'Winrate'); ?></th>
                                <th class="tw-py-3 tw-px-6"><?php echo translate('arena_2v2_rating', 'Rating'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rank = 1;
                            $teamCount = count($teams);
                            foreach ($teams as $team) {
                                $rowClass = ($rank <= 3 && $teamCount >= 3) ? 'top3' : '';
                                $faction = getFaction($team['race']);
                                echo "<tr class='{$rowClass} tw-transition tw-duration-200' onclick=\"window.location='{$base_path}armory/arenateam?arenaTeamId={$team['arenaTeamId']}';\">
                                    <td class='tw-py-3 tw-px-6'>{$rank}</td>
                                    <td class='tw-py-3 tw-px-6'>" . htmlspecialchars($team['team_name']) . "</td>
                                    <td class='tw-py-3 tw-px-6'>
                                        <img src='" . factionIconByName($faction) . "' alt='{$faction}' title='{$faction}' class='tw-inline-block tw-w-6 tw-h-6 tw-rounded'>
                                    </td>
                                    <td class='tw-py-3 tw-px-6'>{$team['seasonWins']}</td>
                                    <td class='tw-py-3 tw-px-6'>{$team['seasonLosses']}</td>
                                    <td class='tw-py-3 tw-px-6'>{$team['winrate']}%</td>
                                    <td class='tw-py-3 tw-px-6'>{$team['rating']}</td>
                                </tr>";
                                $rank++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>