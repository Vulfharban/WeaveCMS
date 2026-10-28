<?php
/**
 * WeaveCMS – Dynamische XML-Sitemap (erreichbar als /sitemap.xml via .htaccess-Rewrite).
 */
require_once __DIR__ . '/config.php';

$data = load_data(__DIR__ . '/data.json', []);

header('Content-Type: application/xml; charset=UTF-8');
echo SeoManager::renderSitemapXml($data, SeoManager::getBaseUrl());
