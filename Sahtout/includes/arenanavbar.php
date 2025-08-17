<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
?>
<style>
    .nav-container {
        border: 2px double #fcd34d;
        border-image: none;
        box-shadow: 0 0 10px rgba(252, 211, 77, 0.5);
        transition: border-color 0.3s ease-in-out;
    }
    .nav-container:hover {
        border-color: #4dd0e1;
    }
    .nav-button {
        background: linear-gradient(to right, #4338ca, #1e1b4b);
        border: 2px double #fcd34d;
        border-top: 1px solid #ffffff;
        border-bottom: 1px solid #ffffff;
        text-shadow: 0 0 8px rgba(252, 211, 77, 0.8);
        transition: all 0.3s ease-in-out;
    }
    .nav-button-2v2 {
        background: linear-gradient(to right, #dc2626, #7f1d1d);
    }
    .nav-button-3v3 {
        background: linear-gradient(to right, #15803d, #064e3b);
    }
    .nav-button-5v5 {
        background: linear-gradient(to right, #8c1dadff, #36012dff);
    }
    .nav-button:hover,
    .nav-button-2v2:hover,
    .nav-button-3v3:hover,
    .nav-button-5v5:hover {
        background: #0078c9;
        color: #fbfbfb;
        transform: scale(1.05);
        text-shadow: none;
        cursor: url('/Sahtout/img/hover_wow.gif')16 16, auto;
    }
    .nav-icon {
        width: 24px;
        height: 24px;
        vertical-align: middle;
        margin-right: 8px;
    }
</style>
<div class="flex justify-center mb-10">
    <div class="nav-container bg-gray-900/75 rounded-xl shadow-lg p-4 sm:p-4 max-w-6xl mx-auto">
        <div class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:space-x-8 justify-center">
            <a href="armory/solo_pvp" class="nav-button px-4 py-2 sm:px-8 sm:py-4 text-gold-300 rounded-xl"><img src="/Sahtout/img/armory/sword.webp" alt="Solo PVP" class="nav-icon inline-block">SOLO PVP Ladder</a>
            <a href="armory/arena_2v2" class="nav-button nav-button-2v2 px-4 py-2 sm:px-8 sm:py-4 text-gold-300 rounded-xl"><img src="/Sahtout/img/armory/arena.webp" alt="Arena" class="nav-icon inline-block">2v2 Arena</a>
            <a href="armory/arena_3v3" class="nav-button nav-button-3v3 px-4 py-2 sm:px-8 sm:py-4 text-gold-300 rounded-xl"><img src="/Sahtout/img/armory/arena.webp" alt="Arena" class="nav-icon inline-block">3v3 Arena</a>
            <a href="armory/arena_5v5" class="nav-button nav-button-5v5 px-4 py-2 sm:px-8 sm:py-4 text-gold-300 rounded-xl"><img src="/Sahtout/img/armory/arena.webp" alt="Arena" class="nav-icon inline-block">5v5 Arena</a>
        </div>
    </div>
</div>