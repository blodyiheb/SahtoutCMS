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
        'active_bg'   => 'bg-gradient-to-r from-indigo-900/90 to-blue-900/90 border-indigo-400 text-indigo-200 shadow-indigo-500/20',
        'idle_bg'     => 'bg-gradient-to-r from-indigo-950/60 to-slate-900/80 border-indigo-900/50 hover:border-indigo-500/70 hover:shadow-indigo-500/20',
        'active_text' => 'text-amber-300 drop-shadow-[0_0_8px_rgba(251,191,36,0.6)]',
    ],
    [
        'id'          => 'arena_2v2',
        'url'         => $base_path . 'armory/arena_2v2',
        'label'       => translate('arenanav_2v2_arena', '2v2 Arena'),
        'icon'        => $base_path . 'img/armory/arena.webp',
        'active_bg'   => 'bg-gradient-to-r from-red-900/90 to-rose-950/90 border-red-500 text-red-200 shadow-red-500/20',
        'idle_bg'     => 'bg-gradient-to-r from-red-950/60 to-slate-900/80 border-red-900/50 hover:border-red-500/70 hover:shadow-red-500/20',
        'active_text' => 'text-amber-300 drop-shadow-[0_0_8px_rgba(251,191,36,0.6)]',
    ],
    [
        'id'          => 'arena_3v3',
        'url'         => $base_path . 'armory/arena_3v3',
        'label'       => translate('arenanav_3v3_arena', '3v3 Arena'),
        'icon'        => $base_path . 'img/armory/arena.webp',
        'active_bg'   => 'bg-gradient-to-r from-emerald-900/90 to-teal-950/90 border-emerald-500 text-emerald-200 shadow-emerald-500/20',
        'idle_bg'     => 'bg-gradient-to-r from-emerald-950/60 to-slate-900/80 border-emerald-900/50 hover:border-emerald-500/70 hover:shadow-emerald-500/20',
        'active_text' => 'text-amber-300 drop-shadow-[0_0_8px_rgba(251,191,36,0.6)]',
    ],
    [
        'id'          => 'arena_5v5',
        'url'         => $base_path . 'armory/arena_5v5',
        'label'       => translate('arenanav_5v5_arena', '5v5 Arena'),
        'icon'        => $base_path . 'img/armory/arena.webp',
        'active_bg'   => 'bg-gradient-to-r from-purple-900/90 to-fuchsia-950/90 border-purple-500 text-purple-200 shadow-purple-500/20',
        'idle_bg'     => 'bg-gradient-to-r from-purple-950/60 to-slate-900/80 border-purple-900/50 hover:border-purple-500/70 hover:shadow-purple-500/20',
        'active_text' => 'text-amber-300 drop-shadow-[0_0_8px_rgba(251,191,36,0.6)]',
    ],
];
?>

<nav class="w-full flex justify-center mb-10 px-4">
    <!-- Outer Container with Glassmorphism & WoW Gold Border -->
    <div class="relative w-full max-w-6xl p-2 sm:p-3 bg-slate-950/80 backdrop-blur-md rounded-2xl border border-amber-500/30 shadow-[0_0_20px_rgba(0,0,0,0.8),0_0_15px_rgba(245,158,11,0.15)]">
        
        <!-- Navigation Buttons Layout -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <?php foreach ($nav_items as $item): ?>
                <?php $is_active = (strpos($current_uri, $item['id']) !== false); ?>
                
                <a href="<?php echo $item['url']; ?>" 
                   class="group relative flex items-center justify-center space-x-3 px-5 py-3 sm:py-4 rounded-xl border text-sm font-semibold tracking-wide uppercase transition-all duration-300 ease-out shadow-md hover:-translate-y-0.5 hover:shadow-lg active:translate-y-0 <?php echo $is_active ? $item['active_bg'] : $item['idle_bg']; ?>">
                    
                    <!-- Icon with glow effect -->
                    <img src="<?php echo $item['icon']; ?>" 
                         alt="<?php echo $item['label']; ?>" 
                         class="w-6 h-6 object-contain transition-transform duration-300 group-hover:scale-110 filter drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                    
                    <!-- Label with text glow -->
                    <span class="transition-colors duration-300 group-hover:text-amber-300 <?php echo $is_active ? $item['active_text'] : 'text-amber-200/90'; ?>">
                        <?php echo $item['label']; ?>
                    </span>

                    <!-- Active indicator dot -->
                    <?php if ($is_active): ?>
                        <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-amber-400 animate-pulse shadow-[0_0_8px_#fbbf24]"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>