<?php
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https://' : 'http://';
} elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $protocol = 'https://';
} else {
    $protocol = 'http://';
}

$host = $_SERVER['HTTP_HOST'];

$projectFolder = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', __DIR__ . '/..'));


$base_path = '/';

$project_root = rtrim(realpath(__DIR__ . '/..'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
