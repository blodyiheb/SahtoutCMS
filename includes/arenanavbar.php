<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Helper logic to mark the active button based on current URL path
$current_uri = $_SERVER['REQUEST_URI'] ?? '';

$nav_items = [
    [
        'id'          => 'solo_pvp',
        'url'         => $base_path . 'armory/solo_pvp',
        'label'       => translate('arenanav_solo_pvp', 'SOLO PVP Ladder'),
        'icon'        => $base_path . 'img/armory/sword.webp',
        'color'       => '#f2cf5b', // Gold
        'color_dark'  => '#c9a227',
        'active_bg'   => 'bg-gradient-to-r from-[rgba(242,207,82,0.3)] to-[rgba(201,162,39,0.2)]',
        'idle_bg'     => 'bg-[rgba(10,14,22,0.6)]',
        'border_color' => 'rgba(242,207,82,0.4)',
        'glow_color'  => 'rgba(242,207,82,0.3)',
    ],
    [
        'id'          => 'arena_2v2',
        'url'         => $base_path . 'armory/arena_2v2',
        'label'       => translate('arenanav_2v2_arena', '2v2 Arena'),
        'icon'        => $base_path . 'img/armory/arena.webp',
        'color'       => '#ef4444', // Red
        'color_dark'  => '#b91c1c',
        'active_bg'   => 'bg-gradient-to-r from-[rgba(239,68,68,0.3)] to-[rgba(185,28,28,0.2)]',
        'idle_bg'     => 'bg-[rgba(10,14,22,0.6)]',
        'border_color' => 'rgba(239,68,68,0.4)',
        'glow_color'  => 'rgba(239,68,68,0.3)',
    ],
    [
        'id'          => 'arena_3v3',
        'url'         => $base_path . 'armory/arena_3v3',
        'label'       => translate('arenanav_3v3_arena', '3v3 Arena'),
        'icon'        => $base_path . 'img/armory/arena.webp',
        'color'       => '#22c55e', // Green
        'color_dark'  => '#15803d',
        'active_bg'   => 'bg-gradient-to-r from-[rgba(34,197,94,0.3)] to-[rgba(21,128,61,0.2)]',
        'idle_bg'     => 'bg-[rgba(10,14,22,0.6)]',
        'border_color' => 'rgba(34,197,94,0.4)',
        'glow_color'  => 'rgba(34,197,94,0.3)',
    ],
    [
        'id'          => 'arena_5v5',
        'url'         => $base_path . 'armory/arena_5v5',
        'label'       => translate('arenanav_5v5_arena', '5v5 Arena'),
        'icon'        => $base_path . 'img/armory/arena.webp',
        'color'       => '#8b5cf6', // Purple
        'color_dark'  => '#6d28d9',
        'active_bg'   => 'bg-gradient-to-r from-[rgba(139,92,246,0.3)] to-[rgba(109,40,217,0.2)]',
        'idle_bg'     => 'bg-[rgba(10,14,22,0.6)]',
        'border_color' => 'rgba(139,92,246,0.4)',
        'glow_color'  => 'rgba(139,92,246,0.3)',
    ],
];
?>

<nav class="w-full flex justify-center mb-10 px-4">
    <!-- Outer Container with Glassmorphism & WoW Gold Border -->
    <div class="relative w-full max-w-6xl p-2 sm:p-3 bg-[rgba(5,7,11,0.85)] backdrop-blur-md rounded-none border border-[rgba(201,162,39,0.22)] shadow-[0_20px_40px_-12px_rgba(0,0,0,0.8),inset_0_0_60px_rgba(0,0,0,0.25)]">
        
        <!-- Inner border decoration -->
        <div class="absolute inset-[3px] border border-[rgba(201,162,39,0.08)] pointer-events-none"></div>
        
        <!-- Corner decorations -->
        <div class="absolute inset-0 pointer-events-none" style="background:
            linear-gradient(#e8c552,#e8c552) left top / 12px 1px,
            linear-gradient(#e8c552,#e8c552) left top / 1px 12px,
            linear-gradient(#e8c552,#e8c552) right top / 12px 1px,
            linear-gradient(#e8c552,#e8c552) right top / 1px 12px,
            linear-gradient(#e8c552,#e8c552) left bottom / 12px 1px,
            linear-gradient(#e8c552,#e8c552) left bottom / 1px 12px,
            linear-gradient(#e8c552,#e8c552) right bottom / 12px 1px,
            linear-gradient(#e8c552,#e8c552) right bottom / 1px 12px;
            background-repeat: no-repeat;">
        </div>
        
        <!-- Navigation Buttons Layout -->
        <div class="relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <?php foreach ($nav_items as $item): ?>
                <?php $is_active = (strpos($current_uri, $item['id']) !== false); ?>
                
                <a href="<?php echo $item['url']; ?>" 
                   class="group relative flex items-center justify-center space-x-3 px-5 py-3 sm:py-4 rounded-none border-2 text-sm font-semibold tracking-wide uppercase transition-all duration-300 ease-out shadow-md hover:-translate-y-0.5 hover:shadow-lg active:translate-y-0 clip-path-tab"
                   style="<?php 
                   if ($is_active) {
                       echo 'background: linear-gradient(135deg, ' . $item['color'] . '25, ' . $item['color_dark'] . '15); border-color: ' . $item['color'] . '; box-shadow: 0 0 30px ' . $item['glow_color'] . ';';
                   } else {
                       echo 'background: rgba(10,14,22,0.6); border-color: ' . $item['border_color'] . ';';
                   }
                   ?>">
                    
                    <!-- Icon with glow effect -->
                    <img src="<?php echo $item['icon']; ?>" 
                         alt="<?php echo $item['label']; ?>" 
                         class="w-6 h-6 object-contain transition-transform duration-300 group-hover:scale-110 filter drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                    
                    <!-- Label -->
                    <span class="transition-all duration-300 group-hover:text-[<?php echo $item['color']; ?>] group-hover:drop-shadow-[0_0_8px_<?php echo $item['color']; ?>] <?php echo $is_active ? 'drop-shadow-[0_0_12px_' . $item['color'] . ']' : ''; ?>" style="<?php echo $is_active ? 'color: ' . $item['color'] . ';' : 'color: rgba(255,255,255,0.8);'; ?>">
                        <?php echo $item['label']; ?>
                    </span>

                    <!-- Active indicator dot -->
                    <?php if ($is_active): ?>
                        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full animate-pulse shadow-[0_0_12px_<?php echo $item['color']; ?>]" style="background: <?php echo $item['color']; ?>;"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<style>
/* Clip-path for tab-like appearance */
.clip-path-tab {
    clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
}

/* Hover effect for non-active tabs */
.clip-path-tab:not(.active):hover {
    transform: translateY(-2px) !important;
}

/* Active tab stays highlighted */
.clip-path-tab.active {
    /* Styles applied via inline style */
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .clip-path-tab {
        clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px);
    }
}
</style>