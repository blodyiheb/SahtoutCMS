<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
?>

<div class="arena-nav-wrapper tw-flex tw-justify-center tw-mb-10">
    <div class="nav-container tw-bg-gray-900/75 tw-rounded-xl tw-shadow-lg tw-p-4 sm:tw-p-4 tw-max-w-6xl tw-mx-auto">
        <div class="tw-flex tw-flex-col tw-space-y-4 sm:tw-flex-row sm:tw-space-y-0 sm:tw-space-x-8 tw-justify-center">
            <a href="<?php echo $base_path; ?>armory/solo_pvp" class="nav-button tw-px-4 tw-py-2 sm:tw-px-8 sm:tw-py-4 tw-text-amber-400 tw-rounded-xl">
                <img src="<?php echo $base_path; ?>img/armory/sword.webp" alt="Solo PVP" class="nav-icon tw-inline-block"><?php echo translate('arenanav_solo_pvp', 'SOLO PVP Ladder'); ?>
            </a>
            <a href="<?php echo $base_path; ?>armory/arena_2v2" class="nav-button nav-button-2v2 tw-px-4 tw-py-2 sm:tw-px-8 sm:tw-py-4 tw-text-amber-400 tw-rounded-xl">
                <img src="<?php echo $base_path; ?>img/armory/arena.webp" alt="Arena" class="nav-icon tw-inline-block"><?php echo translate('arenanav_2v2_arena', '2v2 Arena'); ?>
            </a>
            <a href="<?php echo $base_path; ?>armory/arena_3v3" class="nav-button nav-button-3v3 tw-px-4 tw-py-2 sm:tw-px-8 sm:tw-py-4 tw-text-amber-400 tw-rounded-xl">
                <img src="<?php echo $base_path; ?>img/armory/arena.webp" alt="Arena" class="nav-icon tw-inline-block"><?php echo translate('arenanav_3v3_arena', '3v3 Arena'); ?>
            </a>
            <a href="<?php echo $base_path; ?>armory/arena_5v5" class="nav-button nav-button-5v5 tw-px-4 tw-py-2 sm:tw-px-8 sm:tw-py-4 tw-text-amber-400 tw-rounded-xl">
                <img src="<?php echo $base_path; ?>img/armory/arena.webp" alt="Arena" class="nav-icon tw-inline-block"><?php echo translate('arenanav_5v5_arena', '5v5 Arena'); ?>
            </a>
        </div>
    </div>
</div>