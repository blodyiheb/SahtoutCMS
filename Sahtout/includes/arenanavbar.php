<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
?>
<style>
    .arena-nav-wrapper .nav-container {
        border: 2px double #fcd34d;
        border-image: none;
        box-shadow: 0 0 10px rgba(252, 211, 77, 0.5);
        transition: border-color 0.3s ease-in-out;
    }
    .arena-nav-wrapper .nav-container:hover {
        border-color: #4dd0e1;
    }
    .arena-nav-wrapper .nav-button {
        background: linear-gradient(to right, #4338ca, #1e1b4b);
        border: 2px double #fcd34d;
        border-top: 1px solid #ffffff;
        border-bottom: 1px solid #ffffff;
        text-shadow: 0 0 8px rgba(252, 211, 77, 0.8);
        transition: all 0.3s ease-in-out;
    }
    .arena-nav-wrapper .nav-button-2v2 {
        background: linear-gradient(to right, #dc2626, #7f1d1d);
    }
    .arena-nav-wrapper .nav-button-3v3 {
        background: linear-gradient(to right, #15803d, #064e3b);
    }
    .arena-nav-wrapper .nav-button-5v5 {
        background: linear-gradient(to right, #8c1dad, #36012d);
    }
    .arena-nav-wrapper .nav-button:hover,
    .arena-nav-wrapper .nav-button-2v2:hover,
    .arena-nav-wrapper .nav-button-3v3:hover,
    .arena-nav-wrapper .nav-button-5v5:hover {
        background: #0078c9;
        color: #fbfbfb;
        transform: scale(1.05);
        text-shadow: none;
        text-decoration: none;
        cursor: url('/Sahtout/img/hover_wow.gif') 16 16, auto;
    }
    .arena-nav-wrapper .nav-icon {
        width: 24px;
        height: 24px;
        vertical-align: middle;
        margin-right: 8px;
    }
</style>
<div class="arena-nav-wrapper tw-flex tw-justify-center tw-mb-10">
    <div class="nav-container tw-bg-gray-900/75 tw-rounded-xl tw-shadow-lg tw-p-4 sm:tw-p-4 tw-max-w-6xl tw-mx-auto">
        <div class="tw-flex tw-flex-col tw-space-y-4 sm:tw-flex-row sm:tw-space-y-0 sm:tw-space-x-8 tw-justify-center">
            <a href="/sahtout/armory/solo_pvp" class="nav-button tw-px-4 tw-py-2 sm:tw-px-8 sm:tw-py-4 tw-text-amber-400 tw-rounded-xl">
                <img src="/Sahtout/img/armory/sword.webp" alt="Solo PVP" class="nav-icon tw-inline-block"><?php echo translate('arenanav_solo_pvp', 'SOLO PVP Ladder'); ?>
            </a>
            <a href="/sahtout/armory/arena_2v2" class="nav-button nav-button-2v2 tw-px-4 tw-py-2 sm:tw-px-8 sm:tw-py-4 tw-text-amber-400 tw-rounded-xl">
                <img src="/Sahtout/img/armory/arena.webp" alt="Arena" class="nav-icon tw-inline-block"><?php echo translate('arenanav_2v2_arena', '2v2 Arena'); ?>
            </a>
            <a href="/sahtout/armory/arena_3v3" class="nav-button nav-button-3v3 tw-px-4 tw-py-2 sm:tw-px-8 sm:tw-py-4 tw-text-amber-400 tw-rounded-xl">
                <img src="/Sahtout/img/armory/arena.webp" alt="Arena" class="nav-icon tw-inline-block"><?php echo translate('arenanav_3v3_arena', '3v3 Arena'); ?>
            </a>
            <a href="/sahtout/armory/arena_5v5" class="nav-button nav-button-5v5 tw-px-4 tw-py-2 sm:tw-px-8 sm:tw-py-4 tw-text-amber-400 tw-rounded-xl">
                <img src="/Sahtout/img/armory/arena.webp" alt="Arena" class="nav-icon tw-inline-block"><?php echo translate('arenanav_5v5_arena', '5v5 Arena'); ?>
            </a>
        </div>
    </div>
</div>