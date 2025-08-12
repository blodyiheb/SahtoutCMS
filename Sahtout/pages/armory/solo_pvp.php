<?php
require_once '../../includes/session.php';
$page_class = "armory";
require_once '../../includes/header.php';

// Query top 50 characters sorted by level and PvP kills
$sql = "
SELECT c.guid, c.name, c.race, c.class, c.level, c.gender, c.totalKills
FROM characters c
ORDER BY c.level DESC, c.totalKills DESC
LIMIT 50
";

$result = $char_db->query($sql);

// Prepare players array
$players = [];
while ($row = $result->fetch_assoc()) {
    $players[] = [
        'guid' => $row['guid'],
        'name' => $row['name'],
        'race' => $row['race'],
        'class' => $row['class'],
        'gender' => $row['gender'],
        'level' => $row['level'],
        'kills' => $row['totalKills'],
    ];
}

// Faction from race
function getFaction($race) {
    $alliance = [1,3,4,7,11,22,25,29];
    return in_array($race, $alliance) ? 'Alliance' : 'Horde';
}

// Image paths
function factionIcon($race) {
    $faction = getFaction($race);
    return "/Sahtout/img/accountimg/faction/" . strtolower($faction) . ".png";
}
function raceIcon($race, $gender) {
    $genderFolder = ($gender == 0) ? 'male' : 'female';
    // Map numeric race IDs to file names
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>WoW Armory - Top 50 Players</title>
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
            scrollbar-color: #ffcc00 #141f2eff;
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
       
        tr {
            cursor: pointer;
        }
        .top5 {
            background: linear-gradient(to right, #161616ff, #043a9eff) !important;
        }
        .top5:hover{
            background: linear-gradient(to right, #5807dbff, #0609c79c) !important;
            cursor: url('/Sahtout/img/hover_wow.gif')16 16, auto;
        }
        tr {
            cursor: pointer;    
        }
        tr:not(.top5):hover {
            background-color: #10369eff; /* Tailwind's gray-600 */
            transition: background-color 0.2s ease-in-out;
            
        }
        tr.top5:hover {
            filter: brightness(1.2); /* Slightly brighten the gradient */
            transition: filter 0.2s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
<div class="main-content">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center text-amber-400 mb-6">Top 50 Players</h1>

        <?php include_once '../../includes/arenanavbar.php'; ?>

        <div class="table-container overflow-x-auto rounded-lg shadow-lg">
            <table class="w-full text-sm text-center bg-gray-800">
                <thead class="bg-gray-700 text-amber-400 uppercase">
                    <tr>
                        <th class="py-3 px-6">Rank</th>
                        <th class="py-3 px-6">Name</th>
                        <th class="py-3 px-6">Faction</th>
                        <th class="py-3 px-6">Race</th>
                        <th class="py-3 px-6">Class</th>
                        <th class="py-3 px-6">Level</th>
                        <th class="py-3 px-6">PvP Kills</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($players) == 0): ?>
                        <tr>
                            <td colspan="7" class="py-3 px-6 text-lg text-amber-400">No players found.</td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $rank = 1;
                        $playerCount = count($players);
                        foreach ($players as $p) {
                            $rowClass = ($rank <= 5 && $playerCount >= 5) ? 'top5' : '';
                            echo "<tr class='{$rowClass} transition duration-200' onclick=\"window.location='/sahtout/pages/character.php?guid={$p['guid']}';\">
                                <td class='py-3 px-6'>{$rank}</td>
                                <td class='py-3 px-6'>" . htmlspecialchars($p['name']) . "</td>
                                <td class='py-3 px-6'>
                                    <img src='" . factionIcon($p['race']) . "' alt='Faction' class='inline-block w-6 h-6 rounded'>
                                </td>
                                <td class='py-3 px-6'>
                                    <img src='" . raceIcon($p['race'], $p['gender']) . "' alt='Race' class='inline-block w-6 h-6 rounded'>
                                </td>
                                <td class='py-3 px-6'>
                                    <img src='" . classIcon($p['class']) . "' alt='Class' class='inline-block w-6 h-6 rounded'>
                                </td>
                                <td class='py-3 px-6'>{$p['level']}</td>
                                <td class='py-3 px-6'>{$p['kills']}</td>
                            </tr>";
                            $rank++;
                        }
                        ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once '../../includes/footer.php'; ?>
</body>
</html>