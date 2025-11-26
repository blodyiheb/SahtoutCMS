<?php
define('ALLOWED_ACCESS', true);
// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/header.php';

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
        'guild_name' => $row['guild_name'] ?? translate('solo_pvp_no_guild', 'No Guild') // Translated default
    ];
}

// Faction from race
function getFaction($race) {
    $alliance = [1, 3, 4, 7, 11, 22, 25, 29];
    return in_array($race, $alliance) ? 'Alliance' : 'Horde';
}

// Image paths
function factionIcon($race) {
    global $base_path;
    $faction = getFaction($race);
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
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo  $site_title_name ." ". translate('solo_pvp_page_title', 'Top 50 Players'); ?></title>
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
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/armory/solo_pvp.css">
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
            <h1 class="tw-text-4xl tw-font-bold tw-text-center tw-text-amber-400 tw-mb-6"><?php echo translate('solo_pvp_title', 'Top 50 Players'); ?></h1>

            <?php include_once $project_root . 'includes/arenanavbar.php'; ?>

            <div class="table-container tw-overflow-x-auto tw-rounded-lg tw-shadow-lg">
                <table class="tw-w-full tw-text-sm tw-text-center tw-bg-gray-800">
                    <thead class="tw-bg-gray-700 tw-text-amber-400 tw-uppercase">
                        <tr>
                            <th class="tw-py-3 tw-px-6"><?php echo translate('solo_pvp_rank', 'Rank'); ?></th>
                            <th class="tw-py-3 tw-px-6"><?php echo translate('solo_pvp_name', 'Name'); ?></th>
                            <th class="tw-py-3 tw-px-6"><?php echo translate('solo_pvp_guild', 'Guild'); ?></th>
                            <th class="tw-py-3 tw-px-6"><?php echo translate('solo_pvp_faction', 'Faction'); ?></th>
                            <th class="tw-py-3 tw-px-6"><?php echo translate('solo_pvp_race', 'Race'); ?></th>
                            <th class="tw-py-3 tw-px-6"><?php echo translate('solo_pvp_class', 'Class'); ?></th>
                            <th class="tw-py-3 tw-px-6"><?php echo translate('solo_pvp_level', 'Level'); ?></th>
                            <th class="tw-py-3 tw-px-6"><?php echo translate('solo_pvp_kills', 'PvP Kills'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($players) == 0): ?>
                            <tr>
                                <td colspan="8" class="tw-py-3 tw-px-6 tw-text-lg tw-text-amber-400"><?php echo translate('solo_pvp_no_players', 'No players found.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank = 1;
                            $playerCount = count($players);
                            foreach ($players as $p) {
                                $rowClass = ($rank <= 5 && $playerCount >= 5) ? 'top5' : '';
                                echo "<tr class='{$rowClass} tw-transition tw-duration-200' onclick=\"window.location='{$base_path}character?guid={$p['guid']}';\">
                                    <td class='tw-py-3 tw-px-6'>{$rank}</td>
                                    <td class='tw-py-3 tw-px-6'><a href='{$base_path}character?guid={$p['guid']}' class='tw-text-white tw-no-underline hover:tw-underline'>" . htmlspecialchars($p['name']) . "</a></td>
                                    <td class='tw-py-3 tw-px-6'>" . htmlspecialchars($p['guild_name']) . "</td>
                                    <td class='tw-py-3 tw-px-6'>
                                        <img src='" . factionIcon($p['race']) . "' alt='" . translate('solo_pvp_faction_alt', 'Faction') . "' class='tw-inline-block tw-w-6 tw-h-6 tw-rounded'>
                                    </td>
                                    <td class='tw-py-3 tw-px-6'>
                                        <img src='" . raceIcon($p['race'], $p['gender']) . "' alt='" . translate('solo_pvp_race_alt', 'Race') . "' class='tw-inline-block tw-w-6 tw-h-6 tw-rounded'>
                                    </td>
                                    <td class='tw-py-3 tw-px-6'>
                                        <img src='" . classIcon($p['class']) . "' alt='" . translate('solo_pvp_class_alt', 'Class') . "' class='tw-inline-block tw-w-6 tw-h-6 tw-rounded'>
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
    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>