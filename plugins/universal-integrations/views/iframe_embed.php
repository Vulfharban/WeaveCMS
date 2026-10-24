<?php
/**
 * Frontend: Iframe Embed View (Universal Integrations)
 * 
 * @var array $item
 * @var array $params
 * @var string $targetUrl
 */

$height = (int)($params['height'] ?? ($item['iframe_height'] ?? 800));
if ($height < 300 || $height > 2000) {
    $height = 800;
}
$isSeamless = (($params['mode'] ?? ($item['embed_mode'] ?? '')) === 'seamless_iframe');
$sandbox = $item['sandbox_allow'] ?? 'allow-scripts allow-same-origin allow-forms allow-popups';
$title = $params['title'] ?? ($item['title'] ?? ($item['name'] ?? 'Integration'));
$subtitle = $params['subtitle'] ?? ($item['subtitle'] ?? '');
$isSection = !empty($params['is_section']);
?>

<?php if ($isSection): ?>
<section id="<?= htmlspecialchars($item['slug']) ?>" class="section section-integration-<?= htmlspecialchars($item['slug']) ?>" style="margin-bottom: 5rem; scroll-margin-top: 100px;">
    <h2 style="margin-bottom: 0.5rem; text-align: center; color: var(--accent);"><?= htmlspecialchars($title) ?></h2>
    <?php if (!empty($subtitle)): ?>
        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 2.5rem auto;">
            <?= htmlspecialchars($subtitle) ?>
        </p>
    <?php endif; ?>
<?php endif; ?>

<div class="universal-integration-wrapper" style="margin: 2rem 0;">
    <?php if (empty($targetUrl)): ?>
        <div class="card p-8 text-center max-w-lg mx-auto border dashed">
            <span class="material-icons text-4xl text-muted mb-2">link_off</span>
            <h3 class="text-lg font-bold mb-1"><?= htmlspecialchars($title) ?></h3>
            <p class="text-xs text-muted mb-4">Es wurde noch keine Ziel-URL für diese Integration hinterlegt.</p>
        </div>
    <?php else: ?>
        <div class="integration-iframe-container" style="background: var(--card-bg, #fff); border: <?= $isSeamless ? 'none' : '1px solid var(--border-color)' ?>; border-radius: <?= $isSeamless ? '0' : '16px' ?>; overflow: hidden; box-shadow: <?= $isSeamless ? 'none' : '0 10px 30px rgba(0,0,0,0.04)' ?>;">
            
            <?php if (!$isSeamless): ?>
                <!-- Header Toolbar -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.015); font-size: 0.85rem;">
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <span class="material-icons text-primary" style="font-size: 20px;"><?= htmlspecialchars($item['icon'] ?? 'open_in_browser') ?></span>
                        <strong style="color: var(--text-primary);"><?= htmlspecialchars($title) ?></strong>
                    </div>
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <button type="button" onclick="document.getElementById('iframe_<?= htmlspecialchars($item['slug']) ?>').contentWindow.location.reload();" 
                                class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem; display:inline-flex; align-items:center; gap:4px;">
                            <span class="material-icons" style="font-size:14px;">refresh</span> Neu laden
                        </button>
                        <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank" rel="noopener noreferrer" 
                           class="btn btn-primary" style="padding: 4px 10px; font-size: 0.75rem; display:inline-flex; align-items:center; gap:4px; text-decoration:none;">
                            <span class="material-icons" style="font-size:14px;">open_in_new</span> Im neuen Tab
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Responsive iFrame -->
            <iframe id="iframe_<?= htmlspecialchars($item['slug']) ?>" 
                    src="<?= htmlspecialchars($targetUrl) ?>" 
                    style="width: 100%; height: <?= $height ?>px; border: 0; display: block;" 
                    loading="lazy"
                    sandbox="<?= htmlspecialchars($sandbox) ?>"
                    title="<?= htmlspecialchars($title) ?>"
                    allowfullscreen>
            </iframe>
        </div>
    <?php endif; ?>
</div>

<?php if ($isSection): ?>
</section>
<?php endif; ?>
