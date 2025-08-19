<?php
define('ALLOWED_ACCESS', true);
require_once '../../includes/session.php';
$page_class = "armory";
require_once '../../includes/header.php';

// Functions to get faction and icon paths
function getFaction($race) {
    $alliance = [1, 3, 4, 7, 11, 22, 25, 29];
    return in_array($race, $alliance) ? 'Alliance' : 'Horde';
}

function factionIconByName($faction) {
    return "/Sahtout/img/accountimg/faction/" . strtolower($faction) . ".png";
}

// Query top 50 3v3 arena teams 
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
WHERE at.type = 3 -- 3v3 teams
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
    <title>WoW Armory - Top 50 3v3 Arena Teams</title>
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
        .arena-content {
            min-height: calc(100vh - 200px); /* Adjust based on header/footer height */
        }

        .arena-content .table-container {
            scrollbar-width: thin;
            scrollbar-color: #ffcc00 #1f2937;
            font-family: 'Arial', sans-serif;
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

        .arena-content .top3 {
            background: linear-gradient(to right, #f59e0b, #d97706) !important;
        }

        .arena-content tr {
            cursor: pointer;
        }

        .arena-content tr:not(.top3):hover {
            background-color: #4b5563; /* Tailwind's gray-600 */
            transition: background-color 0.2s ease-in-out;
        }

        .arena-content tr.top3:hover {
            filter: brightness(1.2);
            transition: filter 0.2s ease-in-out;
            cursor: url('/Sahtout/img/hover_wow.gif') 16 16, auto;
        }

        /* Scope nav-container override to arena-nav-wrapper */
        .arena-nav-wrapper .nav-container {
            border: 2px double #15803d;
        }
        .arena-content a {
            color: #ffffff;
            text-decoration: none;
        }
    </style>
</head>
<body class="<?php echo $page_class; ?>">
    <div class="arena-content tw-bg-900 tw-text-white">
        <div class="tw-container tw-mx-auto tw-px-4 tw-py-8">
            <h1 class="tw-text-4xl tw-font-bold tw-text-center tw-text-amber-400 tw-mb-6">Top 50 3v3 Arena Teams</h1>

            <?php include_once '../../includes/arenanavbar.php'; ?>

            <?php if (count($teams) == 0): ?>
                <div class="tw-text-center tw-text-lg tw-text-amber-400 tw-bg-gray-800 tw-p-6 tw-rounded-lg tw-shadow-lg">
                    No 3v3 arena teams found.
                </div>
            <?php else: ?>
                <div class="table-container tw-overflow-x-auto tw-rounded-lg tw-shadow-lg">
                    <table class="tw-w-full tw-text-sm tw-text-center tw-bg-gray-800">
                        <thead class="tw-bg-gray-700 tw-text-amber-400 tw-uppercase">
                            <tr>
                                <th class="tw-py-3 tw-px-6">Rank</th>
                                <th class="tw-py-3 tw-px-6">Name</th>
                                <th class="tw-py-3 tw-px-6">Faction</th>
                                <th class="tw-py-3 tw-px-6">Wins</th>
                                <th class="tw-py-3 tw-px-6">Losses</th>
                                <th class="tw-py-3 tw-px-6">Winrate</th>
                                <th class="tw-py-3 tw-px-6">Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rank = 1;
                            $teamCount = count($teams);
                            foreach ($teams as $team) {
                                $rowClass = ($rank <= 3 && $teamCount >= 3) ? 'top3' : '';
                                $faction = getFaction($team['race']);
                                echo "<tr class='{$rowClass} tw-transition tw-duration-200' onclick=\"window.location='/sahtout/armory/arenateam?arenaTeamId={$team['arenaTeamId']}';\">
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
    <?php include_once '../../includes/footer.php'; ?>
</body>
</html>