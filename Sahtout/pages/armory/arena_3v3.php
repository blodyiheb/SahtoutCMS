<?php
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
        .top3 {
            background: linear-gradient(to right, #f59e0b, #d97706) !important;
        }
        tr {
            cursor: pointer;
        }
        tr:not(.top3):hover {
            background-color: #4b5563; /* Tailwind's gray-600 */
            transition: background-color 0.2s ease-in-out;
        }
        tr.top3:hover {
            filter: brightness(1.2); /* Slightly brighten the gradient */
            transition: filter 0.2s ease-in-out;
            cursor: url('/Sahtout/img/hover_wow.gif')16 16, auto;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
<div class="main-content">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center text-amber-400 mb-6">Top 50 3v3 Arena Teams</h1>

        <?php include_once '../../includes/arenanavbar.php'; ?>
<style>
    .nav-container {
        border: 2px double #15803d;
    }
</style>
        <?php if (count($teams) == 0): ?>
            <div class="text-center text-lg text-amber-400 bg-gray-800 p-6 rounded-lg shadow-lg">
                No 3v3 arena teams found.
            </div>
        <?php else: ?>
            <div class="table-container overflow-x-auto rounded-lg shadow-lg">
                <table class="w-full text-sm text-center bg-gray-800">
                    <thead class="bg-gray-700 text-amber-400 uppercase">
                        <tr>
                            <th class="py-3 px-6">Rank</th>
                            <th class="py-3 px-6">Name</th>
                            <th class="py-3 px-6">Faction</th>
                            <th class="py-3 px-6">Wins</th>
                            <th class="py-3 px-6">Losses</th>
                            <th class="py-3 px-6">Winrate</th>
                            <th class="py-3 px-6">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        $teamCount = count($teams);
                        foreach ($teams as $team) {
                            $rowClass = ($rank <= 3 && $teamCount >= 3) ? 'top3' : '';
                            $faction = getFaction($team['race']);
                            echo "<tr class='{$rowClass} transition duration-200' onclick=\"window.location='/sahtout/pages/armory/arena/arenateam.php?arenaTeamId={$team['arenaTeamId']}';\">
                                <td class='py-3 px-6'>{$rank}</td>
                                <td class='py-3 px-6'>" . htmlspecialchars($team['team_name']) . "</td>
                                <td class='py-3 px-6'>
                                    <img src='" . factionIconByName($faction) . "' alt='{$faction}' title='{$faction}' class='inline-block w-6 h-6 rounded'>
                                </td>
                                <td class='py-3 px-6'>{$team['seasonWins']}</td>
                                <td class='py-3 px-6'>{$team['seasonLosses']}</td>
                                <td class='py-3 px-6'>{$team['winrate']}%</td>
                                <td class='py-3 px-6'>{$team['rating']}</td>
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