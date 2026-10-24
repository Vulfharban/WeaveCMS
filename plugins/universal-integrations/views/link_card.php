<?php
/**
 * Frontend: Direct Link Button / Card (Universal Integrations)
 * 
 * @var array $item
 * @var string $targetUrl
 */

$title = $params['title'] ?? ($item['title'] ?? ($item['name'] ?? 'Integration'));
$icon = $item['icon'] ?? 'link';
?>

<div class="inline-block my-4">
    <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank" rel="noopener noreferrer" 
       class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding: 10px 20px; font-weight:600; text-decoration:none;">
        <span class="material-icons"><?= htmlspecialchars($icon) ?></span>
        <span><?= htmlspecialchars($title) ?> öffnen</span>
        <span class="material-icons" style="font-size:16px;">open_in_new</span>
    </a>
</div>
