<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Current step - should be set in each page before including this file
$current_step = isset($current_step) ? (int)$current_step : 1;

// Step definitions
$steps = [
    1 => [
        'label' => translate('step_system', 'System'),
        'icon' => 'fa-server',
        'url' => 'install/'
    ],
    2 => [
        'label' => translate('step_requirements', 'Requirements'),
        'icon' => 'fa-check-double',
        'url' => 'install/step2_check'
    ],
    3 => [
        'label' => translate('step_database', 'Database'),
        'icon' => 'fa-database',
        'url' => 'install/step3_db'
    ],
    4 => [
        'label' => translate('step_realm', 'Realm'),
        'icon' => 'fa-server',
        'url' => 'install/step4_realm'
    ],
    5 => [
        'label' => translate('step_email', 'Email'),
        'icon' => 'fa-envelope',
        'url' => 'install/step5_mail'
    ],
    6 => [
        'label' => translate('step_soap', 'SOAP'),
        'icon' => 'fa-code',
        'url' => 'install/step6_soap'
    ],
    7 => [
        'label' => translate('step_finish', 'Finish'),
        'icon' => 'fa-flag-checkered',
        'url' => 'install/finish'
    ]
];
?>

<!-- Progress Stepper -->
<div class="flex items-center justify-center px-4 py-4">
    <div class="flex items-center min-w-max">
        <?php foreach ($steps as $step_num => $step): ?>
            <?php
            $is_active = $step_num === $current_step;
            $is_completed = $step_num < $current_step;
            $is_future = $step_num > $current_step;
            
            $circle_class = 'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 flex-shrink-0';
            
            if ($is_active) {
                $circle_class .= ' bg-gold-500 text-slate-900 shadow-lg shadow-gold-500/20 scale-110';
                $text_class = 'text-gold-400 font-semibold';
            } elseif ($is_completed) {
                $circle_class .= ' bg-emerald-500/80 text-white';
                $text_class = 'text-emerald-400';
            } else {
                $circle_class .= ' bg-slate-700 text-slate-500';
                $text_class = 'text-slate-500';
            }
            ?>
            
            <!-- Step Circle -->
            <div class="flex items-center">
                <div class="flex flex-col items-center">
                    <a href="<?php echo $base_path . $step['url']; ?>" 
                       class="<?php echo $circle_class; ?> hover:scale-110 transition-transform <?php echo $is_completed || $is_active ? 'cursor-pointer' : 'cursor-default'; ?>"
                       title="<?php echo htmlspecialchars($step['label']); ?>"
                       <?php echo $is_future ? 'onclick="return false;"' : ''; ?>>
                        <?php if ($is_completed): ?>
                            <i class="fas fa-check text-xs"></i>
                        <?php else: ?>
                            <?php echo $step_num; ?>
                        <?php endif; ?>
                    </a>
                    <span class="text-xs mt-1 <?php echo $text_class; ?> hidden sm:inline whitespace-nowrap">
                        <?php echo htmlspecialchars($step['label']); ?>
                    </span>
                </div>
                
                <!-- Connector Line -->
                <?php if ($step_num < count($steps)): ?>
                    <div class="w-8 sm:w-16 h-0.5 mx-1 sm:mx-2 flex-shrink-0 <?php echo $is_completed ? 'bg-emerald-500/80' : 'bg-slate-700'; ?>"></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Mobile Step Indicator -->
<div class="flex items-center justify-center px-4 mb-4 sm:hidden">
    <div class="flex items-center gap-2 text-sm">
        <span class="text-gold-400 font-bold"><?php echo $current_step; ?></span>
        <span class="text-slate-500">/</span>
        <span class="text-slate-400"><?php echo count($steps); ?></span>
        <span class="text-slate-500 mx-1">-</span>
        <span class="text-gold-400 font-semibold"><?php echo htmlspecialchars($steps[$current_step]['label']); ?></span>
    </div>
</div>

<style>
    /* Responsive adjustments for stepper */
    @media (max-width: 480px) {
        .w-8.sm\:w-16 {
            width: 12px !important;
        }
        .w-8 {
            width: 28px !important;
            height: 28px !important;
            font-size: 11px !important;
        }
    }
</style>