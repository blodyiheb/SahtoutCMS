<?php
define('ALLOWED_ACCESS', true);
require_once '../../includes/session.php';
$page_class = "armory";
require_once '../../includes/header.php';

// Query top 50 characters sorted by level and PvP kills, including guild name
$sql = "
SELECT c.guid, c.name, c.race, c.class, c.level, c.gender, c.totalKills, g.name AS guild_name
FROM characters c
LEFT JOIN guild_member gm ON c.guid = gm.guid
LEFT JOIN guild g ON gm.guildid = g.guildid
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
        'guild_name' => $row['guild_name'] ?? 'No Guild' // Default to 'No Guild' if null
    ];
}

// Faction from race
function getFaction($race) {
    $alliance = [1, 3, 4, 7, 11, 22, 25, 29];
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

        .arena-content tr {
            cursor: pointer;
        }

        .arena-content .top5 {
            background: linear-gradient(to right, #161616, #043a9e) !important;
        }

        .arena-content .top5:hover {
            background: linear-gradient(to right, #5807db, #0609c79c) !important;
            cursor: url('/Sahtout/img/hover_wow.gif') 16 16, auto;
        }

        .arena-content tr:not(.top5):hover {
            background-color: #10369e; /* Custom blue for hover */
            transition: background-color 0.2s ease-in-out;
        }

        .arena-content tr.top5:hover {
            filter: brightness(1.2);
            transition: filter 0.2s ease-in-out;
        }

        .arena-content a {
            color: #ffffff;
            text-decoration: none;
        }

        .arena-content a:hover {
            text-decoration: underline;
        }

        /* Scope nav-container override to arena-nav-wrapper */
        .arena-nav-wrapper .nav-container {
            border: 2px double #4338ca;
            margin-top: 20px;
        }
    </style>
</head>
<body class="<?php echo $page_class; ?>">
    <div class="arena-content tw-bg-900 tw-text-white">
        <div class="tw-container tw-mx-auto tw-px-4 tw-py-8">
            <h1 class="tw-text-4xl tw-font-bold tw-text-center tw-text-amber-400 tw-mb-6">Top 50 Players</h1>

            <?php include_once '../../includes/arenanavbar.php'; ?>

            <div class="table-container tw-overflow-x-auto tw-rounded-lg tw-shadow-lg">
                <table class="tw-w-full tw-text-sm tw-text-center tw-bg-gray-800">
                    <thead class="tw-bg-gray-700 tw-text-amber-400 tw-uppercase">
                        <tr>
                            <th class="tw-py-3 tw-px-6">Rank</th>
                            <th class="tw-py-3 tw-px-6">Name</th>
                            <th class="tw-py-3 tw-px-6">Guild</th>
                            <th class="tw-py-3 tw-px-6">Faction</th>
                            <th class="tw-py-3 tw-px-6">Race</th>
                            <th class="tw-py-3 tw-px-6">Class</th>
                            <th class="tw-py-3 tw-px-6">Level</th>
                            <th class="tw-py-3 tw-px-6">PvP Kills</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($players) == 0): ?>
                            <tr>
                                <td colspan="8" class="tw-py-3 tw-px-6 tw-text-lg tw-text-amber-400">No players found.</td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank = 1;
                            $playerCount = count($players);
                            foreach ($players as $p) {
                                $rowClass = ($rank <= 5 && $playerCount >= 5) ? 'top5' : '';
                                echo "<tr class='{$rowClass} tw-transition tw-duration-200' onclick=\"window.location='/sahtout/character?guid={$p['guid']}';\"'>
                                    <td class='tw-py-3 tw-px-6'>{$rank}</td>
                                    <td class='tw-py-3 tw-px-6'><a href='/sahtout/character?guid={$p['guid']}' class='tw-text-white tw-no-underline hover:tw-underline'>" . htmlspecialchars($p['name']) . "</a></td>
                                    <td class='tw-py-3 tw-px-6'>" . htmlspecialchars($p['guild_name']) . "</td>
                                    <td class='tw-py-3 tw-px-6'>
                                        <img src='" . factionIcon($p['race']) . "' alt='Faction' class='tw-inline-block tw-w-6 tw-h-6 tw-rounded'>
                                    </td>
                                    <td class='tw-py-3 tw-px-6'>
                                        <img src='" . raceIcon($p['race'], $p['gender']) . "' alt='Race' class='tw-inline-block tw-w-6 tw-h-6 tw-rounded'>
                                    </td>
                                    <td class='tw-py-3 tw-px-6'>
                                        <img src='" . classIcon($p['class']) . "' alt='Class' class='tw-inline-block tw-w-6 tw-h-6 tw-rounded'>
                                    </td>
                                    <td class='tw-py-3 tw-px-6'>{$p['level']}</td>
                                    <td class='tw-py-3 tw-px-6'>{$p['kills']}</td>
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