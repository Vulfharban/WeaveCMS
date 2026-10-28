<?php
/**
 * WeaveCMS – Dynamische robots.txt (erreichbar als /robots.txt via .htaccess-Rewrite).
 */
require_once __DIR__ . '/config.php';

$data = load_data(__DIR__ . '/data.json', []);

header('Content-Type: text/plain; charset=UTF-8');
echo SeoManager::renderRobotsTxt($data, SeoManager::getBaseUrl());
