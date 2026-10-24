<?php
/**
 * Frontend: Preview Card View (Universal Integrations)
 * 
 * @var array $item
 * @var array $params
 * @var string $targetUrl
 */

$title = $params['title'] ?? ($item['title'] ?? ($item['name'] ?? 'Integration'));
$subtitle = $params['subtitle'] ?? ($item['subtitle'] ?? '');
$icon = $item['icon'] ?? 'open_in_new';
?>

<div class="card p-8 border rounded-2xl shadow-sm max-w-2xl mx-auto my-8 text-center bg-white dark:bg-gray-900">
    <div style="width: 72px; height: 72px; border-radius: 50%; background: rgba(46,98,72,0.1); color: var(--primary); display:flex; align-items:center; justify-content:center; margin: 0 auto 1.25rem;">
        <span class="material-icons" style="font-size: 38px;"><?= htmlspecialchars($icon) ?></span>
    </div>
    
    <h3 style="font-size: 1.6rem; margin-bottom: 0.5rem; color: var(--text-primary);"><?= htmlspecialchars($title) ?></h3>
    <?php if (!empty($subtitle)): ?>
        <p style="color: var(--text-secondary); font-size: 1rem; line-height: 1.6; max-width: 550px; margin: 0 auto 2rem;">
            <?= htmlspecialchars($subtitle) ?>
        </p>
    <?php endif; ?>

    <div style="display:flex; justify-content:center; gap: 1rem; flex-wrap:wrap;">
        <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank" rel="noopener noreferrer" 
           class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding: 12px 28px; font-weight:bold; text-decoration:none;">
            <span class="material-icons">launch</span> Anwendung öffnen
        </a>
    </div>
</div>
