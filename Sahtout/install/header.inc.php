<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sahtout CMS Installer</title>
<style>
/* Navbar styles */
.navbar {
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #0a0a0a, #1a0a0a);
    padding: 15px 30px;
    border-bottom: 3px solid #cccf22ff; /* Gold border */
    box-shadow: 0 0 20px rgba(212, 175, 55, 0.7);
    position: sticky;
    top: 0;
    z-index: 999;
}
.navbar img {
    height: 60px;
    margin-right: 20px;
    border-radius: 8px;
}
.navbar .title {
    font-family: 'Cinzel', serif;
    font-size: 2em;
    color: #ffd700; /* Brighter gold */
    text-shadow: 0 0 10px #000, 0 0 20px #d4af37;
    font-weight: 700;
}
</style>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="navbar">
    <img src="logo.png" alt="Sahtout Logo">
    <div class="title">Sahtout CMS Installer</div>
</div>
