<?php
/**
 * WeaveCMS – Admin Partial: Headless REST-API & Event-Webhooks (Phase 31)
 *
 * @var array $data Gesamtdaten
 * @var string $csrf_token CSRF-Schutztoken
 */

require_once dirname(__DIR__, 3) . '/libs/php/ApiTokenManager.php';
require_once dirname(__DIR__, 3) . '/libs/php/WebhookManager.php';

$api_tokens = ApiTokenManager::getTokens();
$api_scopes = ApiTokenManager::getAvailableScopes();
$webhooks = WebhookManager::getWebhooks();
$webhook_events = WebhookManager::getSupportedEvents();
$webhook_logs = WebhookManager::getLogs(30);

$new_token_plain = $_SESSION['new_api_token_plain'] ?? null;
$new_token_name = $_SESSION['new_api_token_name'] ?? 'API-Token';
if ($new_token_plain) {
    unset($_SESSION['new_api_token_plain'], $_SESSION['new_api_token_name']);
}

$site_url = (class_exists('SecurityManager') && method_exists('SecurityManager', 'getBaseUrl'))
    ? SecurityManager::getBaseUrl()
    : (($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
?>

<!-- 8. HEADLESS REST-API & EVENT-WEBHOOKS (PHASE 31) -->
<div style="margin-top: 3rem; border-top: 2px solid var(--border-color); padding-top: 2rem;">

    <!-- ERFOLGSMELDUNG BEI NEUEM TOKEN -->
    <?php if (!empty($new_token_plain)): ?>
        <div style="background: rgba(16, 185, 129, 0.12); border: 2px solid #10b981; border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.15);">
            <div style="display:flex; align-items:center; gap:10px; color:#10b981; margin-bottom:0.75rem;">
                <span class="material-icons" style="font-size:24px;">key</span>
                <h4 style="margin:0; font-size:1.15rem; font-weight:700;">API-Token erfolgreich generiert: <?php echo htmlspecialchars($new_token_name); ?></h4>
            </div>
            <p style="color:var(--text-primary); font-size:0.9rem; margin-bottom:1rem; line-height:1.4;">
                <strong style="color:#ef4444;">⚠️ Wichtig:</strong> <?php echo __a('admin.api.alert_copy', 'Kopiere dein neues Token jetzt. Aus Sicherheitsgründen wird es nie wieder in voller Länge angezeigt!'); ?>
            </p>
            <div style="display:flex; align-items:center; gap:10px; background:rgba(0,0,0,0.25); border:1px solid rgba(16, 185, 129, 0.4); border-radius:8px; padding:8px 12px; font-family:monospace; font-size:0.95rem; word-break:break-all;">
                <span id="new-api-token-val" style="flex:1; color:#34d399; font-weight:600;"><?php echo htmlspecialchars($new_token_plain); ?></span>
                <button type="button" class="btn btn-primary" onclick="copyTokenText('<?php echo htmlspecialchars($new_token_plain, ENT_QUOTES); ?>')" style="padding:6px 12px; font-size:0.82rem; display:inline-flex; align-items:center; gap:5px;">
                    <span class="material-icons" style="font-size:16px;">content_copy</span> Kopieren
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- TITELZEILE -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom: 1.5rem;">
        <div>
            <h3 style="color: var(--primary); font-size: 1.3rem; margin:0 0 0.25rem 0; display:flex; align-items:center; gap:8px;">
                <span class="material-icons" style="font-size:24px;">api</span>
                <?php echo __a('admin.api.title', 'Headless REST-API (/api/v1/) & Event-Webhooks'); ?>
            </h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin:0;">
                <?php echo __a('admin.api.desc', 'Strukturierte JSON-Lese-Endpunkte für Seiten, Blogartikel, Produkte und Termine. Sicher authentifiziert über Bearer-Tokens.'); ?>
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-outline" onclick="toggleApiModal(true)" style="display:inline-flex; align-items:center; gap:6px;">
                <span class="material-icons" style="font-size:18px;">key</span> <?php echo __a('admin.api.btn_create_token', 'Neues API-Token'); ?>
            </button>
            <button type="button" class="btn btn-primary" onclick="toggleWebhookModal(true)" style="display:inline-flex; align-items:center; gap:6px;">
                <span class="material-icons" style="font-size:18px;">webhook</span> <?php echo __a('admin.webhooks.btn_new', 'Neuen Webhook'); ?>
            </button>
        </div>
    </div>

    <!-- 1. KACHEL: REST-API ENDPUNKTE ÜBERSICHT -->
    <div style="background: rgba(46,98,72,0.04); border: 1px solid rgba(46,98,72,0.25); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
        <h4 style="margin:0 0 0.75rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
            <span class="material-icons" style="color:var(--primary); font-size:20px;">terminal</span> Verfügbare REST-Lese-Endpunkte
        </h4>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
            <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span style="font-weight:700; color:#10b981; font-size:0.8rem; background:rgba(16,185,129,0.15); padding:2px 6px; border-radius:4px;">GET</span>
                    <span style="font-size:0.78rem; color:var(--text-secondary);">Scope: <code>pages:read</code></span>
                </div>
                <code style="font-size:0.85rem; color:var(--text-primary); display:block; margin-bottom:4px;">/api/v1/pages</code>
                <span style="font-size:0.78rem; color:var(--text-secondary);">Alle Seiten abfragen (Filter: <code>?slug=...</code>)</span>
            </div>

            <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span style="font-weight:700; color:#10b981; font-size:0.8rem; background:rgba(16,185,129,0.15); padding:2px 6px; border-radius:4px;">GET</span>
                    <span style="font-size:0.78rem; color:var(--text-secondary);">Scope: <code>posts:read</code></span>
                </div>
                <code style="font-size:0.85rem; color:var(--text-primary); display:block; margin-bottom:4px;">/api/v1/posts</code>
                <span style="font-size:0.78rem; color:var(--text-secondary);">Blog-Artikel abfragen (Filter: <code>?slug=...</code>)</span>
            </div>

            <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span style="font-weight:700; color:#10b981; font-size:0.8rem; background:rgba(16,185,129,0.15); padding:2px 6px; border-radius:4px;">GET</span>
                    <span style="font-size:0.78rem; color:var(--text-secondary);">Scope: <code>products:read</code></span>
                </div>
                <code style="font-size:0.85rem; color:var(--text-primary); display:block; margin-bottom:4px;">/api/v1/products</code>
                <span style="font-size:0.78rem; color:var(--text-secondary);">Shop-Produkte abfragen (Filter: <code>?id=...</code>)</span>
            </div>

            <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span style="font-weight:700; color:#10b981; font-size:0.8rem; background:rgba(16,185,129,0.15); padding:2px 6px; border-radius:4px;">GET</span>
                    <span style="font-size:0.78rem; color:var(--text-secondary);">Scope: <code>courses:read</code></span>
                </div>
                <code style="font-size:0.85rem; color:var(--text-primary); display:block; margin-bottom:4px;">/api/v1/courses</code>
                <span style="font-size:0.78rem; color:var(--text-secondary);">Termine &amp; Events abfragen</span>
            </div>
        </div>
        <div style="margin-top:12px; font-size:0.82rem; color:var(--text-secondary); display:flex; align-items:center; gap:8px;">
            <span class="material-icons" style="font-size:16px; color:var(--primary);">info</span>
            Header für Aufrufe: <code style="color:var(--primary);">Authorization: Bearer wct_...</code> oder Query-Parameter: <code style="color:var(--primary);">?api_token=wct_...</code>
        </div>
    </div>

    <!-- 2. TABELLE: AKTIVE API BEARER-TOKENS -->
    <div style="margin-bottom: 2.5rem;">
        <h4 style="margin:0 0 0.75rem 0; font-size:1.1rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
            <span class="material-icons" style="color:var(--primary); font-size:20px;">vpn_key</span> <?php echo __a('admin.api.tokens_title', 'Aktive API Bearer-Tokens'); ?>
        </h4>
        
        <?php if (empty($api_tokens)): ?>
            <div style="background:rgba(0,0,0,0.02); border:1px dashed var(--border-color); border-radius:12px; padding:1.5rem; text-align:center; color:var(--text-secondary); font-size:0.9rem;">
                <?php echo __a('admin.api.empty_tokens', 'Noch keine API-Tokens angelegt. Erstelle ein Token, um Drittsystemen Lesezugriff zu gewähren.'); ?>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border-color); text-align:left; color:var(--text-secondary); font-size:0.78rem; text-transform:uppercase;">
                            <th style="padding:10px 12px;"><?php echo __a('admin.api.col_name', 'Name / Client'); ?></th>
                            <th style="padding:10px 12px;"><?php echo __a('admin.api.col_preview', 'Vorschau'); ?></th>
                            <th style="padding:10px 12px;"><?php echo __a('admin.api.col_scopes', 'Rechte (Scopes)'); ?></th>
                            <th style="padding:10px 12px;"><?php echo __a('admin.api.col_last_used', 'Zuletzt genutzt'); ?></th>
                            <th style="padding:10px 12px;"><?php echo __a('admin.api.col_status', 'Status'); ?></th>
                            <th style="padding:10px 12px; text-align:right;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($api_tokens as $t): ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                                <td style="padding:10px 12px; font-weight:600; color:var(--text-primary);">
                                    <?php echo htmlspecialchars($t['name'] ?? 'Token'); ?>
                                </td>
                                <td style="padding:10px 12px; font-family:monospace; color:var(--text-secondary); font-size:0.82rem;">
                                    <code><?php echo htmlspecialchars($t['preview'] ?? 'wct_...'); ?></code>
                                </td>
                                <td style="padding:10px 12px;">
                                    <?php foreach (($t['permissions'] ?? ['*']) as $scope): ?>
                                        <span style="background:rgba(46,98,72,0.12); color:var(--primary); padding:2px 8px; border-radius:4px; font-size:0.75rem; font-family:monospace; margin-right:4px;">
                                            <?php echo htmlspecialchars($scope); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td style="padding:10px 12px; color:var(--text-secondary); font-size:0.82rem;">
                                    <?php echo !empty($t['last_used_at']) ? date('d.m.Y H:i', strtotime($t['last_used_at'])) : '<em>Nie</em>'; ?>
                                </td>
                                <td style="padding:10px 12px;">
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="toggle_api_token">
                                        <input type="hidden" name="token_id" value="<?php echo htmlspecialchars($t['id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding:3px 8px; font-size:0.75rem; border-radius:999px;">
                                            <?php if (!empty($t['is_active'])): ?>
                                                <span style="color:#10b981; display:inline-flex; align-items:center; gap:4px;">● Aktiv</span>
                                            <?php else: ?>
                                                <span style="color:#ef4444; display:inline-flex; align-items:center; gap:4px;">○ Inaktiv</span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td style="padding:10px 12px; text-align:right;">
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('API-Token unwiderruflich löschen?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="delete_api_token">
                                        <input type="hidden" name="token_id" value="<?php echo htmlspecialchars($t['id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:4px 8px; font-size:0.78rem; background:rgba(239,68,68,0.12); color:#ef4444; border:1px solid rgba(239,68,68,0.25);" title="Löschen">
                                            <span class="material-icons" style="font-size:16px;">delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. TABELLE: KONFIGURIERTE WEBHOOKS -->
    <div style="margin-bottom: 2.5rem;">
        <h4 style="margin:0 0 0.75rem 0; font-size:1.1rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
            <span class="material-icons" style="color:var(--primary); font-size:20px;">webhook</span> <?php echo __a('admin.webhooks.title', 'Event-Webhook-Dispatcher'); ?>
        </h4>
        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0 0 1rem 0;">
            <?php echo __a('admin.webhooks.desc', 'Sendet automatisierte HTTP-POST-Ereignisse mit HMAC-SHA256 Signatur (X-Weave-Signature) bei Kontaktanfragen, Bestellungen, Blog-Veröffentlichungen und Backups.'); ?>
        </p>

        <?php if (empty($webhooks)): ?>
            <div style="background:rgba(0,0,0,0.02); border:1px dashed var(--border-color); border-radius:12px; padding:1.5rem; text-align:center; color:var(--text-secondary); font-size:0.9rem;">
                <?php echo __a('admin.webhooks.empty', 'Bisher sind keine Webhooks hinterlegt.'); ?>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border-color); text-align:left; color:var(--text-secondary); font-size:0.78rem; text-transform:uppercase;">
                            <th style="padding:10px 12px;"><?php echo __a('admin.webhooks.col_name', 'Name'); ?></th>
                            <th style="padding:10px 12px;"><?php echo __a('admin.webhooks.col_url', 'Ziel-URL'); ?></th>
                            <th style="padding:10px 12px;"><?php echo __a('admin.webhooks.col_events', 'Abonnierte Events'); ?></th>
                            <th style="padding:10px 12px;"><?php echo __a('admin.webhooks.col_status', 'Status'); ?></th>
                            <th style="padding:10px 12px; text-align:right;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($webhooks as $w): ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                                <td style="padding:10px 12px; font-weight:600; color:var(--text-primary);">
                                    <?php echo htmlspecialchars($w['name'] ?? 'Webhook'); ?>
                                </td>
                                <td style="padding:10px 12px; font-family:monospace; color:var(--text-secondary); font-size:0.82rem; max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?php echo htmlspecialchars($w['url'] ?? ''); ?>
                                </td>
                                <td style="padding:10px 12px;">
                                    <?php foreach (($w['events'] ?? ['*']) as $ev): ?>
                                        <span style="background:rgba(178,108,57,0.12); color:var(--accent); padding:2px 8px; border-radius:4px; font-size:0.75rem; font-family:monospace; margin-right:4px;">
                                            <?php echo htmlspecialchars($ev); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td style="padding:10px 12px;">
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="toggle_webhook">
                                        <input type="hidden" name="webhook_id" value="<?php echo htmlspecialchars($w['id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding:3px 8px; font-size:0.75rem; border-radius:999px;">
                                            <?php if (!empty($w['is_active'])): ?>
                                                <span style="color:#10b981; display:inline-flex; align-items:center; gap:4px;">● Aktiv</span>
                                            <?php else: ?>
                                                <span style="color:#ef4444; display:inline-flex; align-items:center; gap:4px;">○ Inaktiv</span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td style="padding:10px 12px; text-align:right; white-space:nowrap;">
                                    <!-- TEST PING BUTTON -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="test_webhook">
                                        <input type="hidden" name="webhook_id" value="<?php echo htmlspecialchars($w['id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding:4px 8px; font-size:0.78rem;" title="<?php echo htmlspecialchars(__a('admin.webhooks.btn_test', 'Test-Ping')); ?>">
                                            <span class="material-icons" style="font-size:16px;">bolt</span>
                                        </button>
                                    </form>

                                    <!-- DELETE BUTTON -->
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Webhook unwiderruflich löschen?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="delete_webhook">
                                        <input type="hidden" name="webhook_id" value="<?php echo htmlspecialchars($w['id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:4px 8px; font-size:0.78rem; background:rgba(239,68,68,0.12); color:#ef4444; border:1px solid rgba(239,68,68,0.25);" title="Löschen">
                                            <span class="material-icons" style="font-size:16px;">delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 4. WEBHOOK LIEFERPROTOKOLL (DELIVERY LOG) & RETRY -->
    <div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.75rem;">
            <h4 style="margin:0; font-size:1.1rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                <span class="material-icons" style="color:var(--primary); font-size:20px;">history</span> <?php echo __a('admin.webhooks.logs_title', 'Webhook-Lieferprotokoll (Delivery Log)'); ?>
            </h4>
            <?php if (!empty($webhook_logs)): ?>
                <form method="POST" action="" onsubmit="return confirm('Möchtest du das gesamte Webhook-Protokoll leeren?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="clear_webhook_logs">
                    <button type="submit" class="btn btn-secondary" style="padding:4px 10px; font-size:0.78rem; display:inline-flex; align-items:center; gap:4px;">
                        <span class="material-icons" style="font-size:14px;">delete_sweep</span> <?php echo __a('admin.webhooks.btn_clear_logs', 'Protokoll leeren'); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0 0 1rem 0;">
            <?php echo __a('admin.webhooks.logs_desc', 'Protokolliert die letzten 100 Zustellungen inklusive HTTP-Statuscode, Payload und Ausführungszeit.'); ?>
        </p>

        <?php if (empty($webhook_logs)): ?>
            <div style="background:rgba(0,0,0,0.02); border:1px dashed var(--border-color); border-radius:12px; padding:1.5rem; text-align:center; color:var(--text-secondary); font-size:0.9rem;">
                <?php echo __a('admin.webhooks.log_empty', 'Noch keine Webhook-Zustellungen protokolliert.'); ?>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border-color); text-align:left; color:var(--text-secondary); font-size:0.75rem; text-transform:uppercase;">
                            <th style="padding:8px 10px;">Zeitstempel</th>
                            <th style="padding:8px 10px;">Event</th>
                            <th style="padding:8px 10px;">Status</th>
                            <th style="padding:8px 10px;">Dauer</th>
                            <th style="padding:8px 10px;">Ziel-URL</th>
                            <th style="padding:8px 10px; text-align:right;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($webhook_logs as $log): ?>
                            <?php 
                                $code = (int)($log['response_code'] ?? 0);
                                $isOk = ($code >= 200 && $code < 300);
                            ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                                <td style="padding:8px 10px; color:var(--text-secondary); font-size:0.8rem; white-space:nowrap;">
                                    <?php echo !empty($log['timestamp']) ? date('d.m.Y H:i:s', strtotime($log['timestamp'])) : '-'; ?>
                                </td>
                                <td style="padding:8px 10px; font-family:monospace; font-weight:600; color:var(--text-primary);">
                                    <?php echo htmlspecialchars($log['event'] ?? 'event'); ?>
                                </td>
                                <td style="padding:8px 10px;">
                                    <?php if ($isOk): ?>
                                        <span style="background:rgba(16,185,129,0.15); color:#10b981; padding:2px 8px; border-radius:4px; font-weight:700; font-size:0.75rem;">
                                            HTTP <?php echo $code; ?> OK
                                        </span>
                                    <?php else: ?>
                                        <span style="background:rgba(239,68,68,0.15); color:#ef4444; padding:2px 8px; border-radius:4px; font-weight:700; font-size:0.75rem;" title="<?php echo htmlspecialchars($log['error'] ?? ''); ?>">
                                            <?php echo ($code > 0) ? "HTTP {$code}" : 'Fehler'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:8px 10px; color:var(--text-secondary); font-size:0.8rem;">
                                    <?php echo (int)($log['duration_ms'] ?? 0); ?> ms
                                </td>
                                <td style="padding:8px 10px; font-family:monospace; color:var(--text-secondary); font-size:0.8rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?php echo htmlspecialchars($log['url'] ?? ''); ?>
                                </td>
                                <td style="padding:8px 10px; text-align:right;">
                                    <!-- RETRY BUTTON -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="retry_webhook">
                                        <input type="hidden" name="delivery_id" value="<?php echo htmlspecialchars($log['id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding:3px 8px; font-size:0.75rem; display:inline-flex; align-items:center; gap:4px;" title="Payload erneut an URL senden">
                                            <span class="material-icons" style="font-size:14px;">replay</span> <?php echo __a('admin.webhooks.btn_retry', 'Erneut senden'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: NEUES API-TOKEN ERSTELLEN -->
<div id="modal-create-api-token" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); z-index:9999; backdrop-filter:blur(3px); align-items:center; justify-content:center;">
    <div class="card" style="width:90%; max-width:480px; padding:2rem; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.4); position:relative;">
        <h3 style="margin:0 0 1rem 0; color:var(--primary); font-size:1.2rem; display:flex; align-items:center; gap:8px;">
            <span class="material-icons">vpn_key</span> <?php echo __a('admin.api.modal_title', 'Neues API-Bearer-Token generieren'); ?>
        </h3>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="create_api_token">

            <div class="form-group" style="margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:600;"><?php echo __a('admin.api.token_name_label', 'Name des Tokens / Clients'); ?> <span style="color:#ef4444;">*</span></label>
                <input type="text" name="token_name" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.api.token_name_ph', 'z. B. Mobile App, Zapier, Headless Frontend')); ?>" required>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label style="font-size:0.85rem; font-weight:600; margin-bottom:6px; display:block;"><?php echo __a('admin.api.scopes_label', 'Berechtigungen (Scopes)'); ?></label>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($api_scopes as $scopeKey => $scopeLabel): ?>
                        <label style="display:inline-flex; align-items:center; gap:8px; font-size:0.85rem; cursor:pointer; color:var(--text-secondary);">
                            <input type="checkbox" name="token_scopes[]" value="<?php echo htmlspecialchars($scopeKey); ?>" <?php echo ($scopeKey === '*') ? 'checked' : ''; ?> style="accent-color:var(--primary);">
                            <span><code><?php echo htmlspecialchars($scopeKey); ?></code> – <?php echo htmlspecialchars($scopeLabel); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="toggleApiModal(false)"><?php echo __a('admin.btn.cancel', 'Abbrechen'); ?></button>
                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:18px;">key</span> Generieren
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: NEUEN WEBHOOK ANLEGEN -->
<div id="modal-create-webhook" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); z-index:9999; backdrop-filter:blur(3px); align-items:center; justify-content:center;">
    <div class="card" style="width:90%; max-width:520px; padding:2rem; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.4); position:relative;">
        <h3 style="margin:0 0 1rem 0; color:var(--primary); font-size:1.2rem; display:flex; align-items:center; gap:8px;">
            <span class="material-icons">webhook</span> <?php echo __a('admin.webhooks.modal_title', 'Neuen Webhook einrichten'); ?>
        </h3>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="add_webhook">

            <div class="form-group" style="margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:600;"><?php echo __a('admin.webhooks.name_label', 'Webhook Bezeichnung'); ?> <span style="color:#ef4444;">*</span></label>
                <input type="text" name="webhook_name" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.webhooks.name_ph', 'z. B. CRM Lead-Sync, Slack Benachrichtigung')); ?>" required>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:600;"><?php echo __a('admin.webhooks.url_label', 'Ziel-URL (Payload URL)'); ?> <span style="color:#ef4444;">*</span></label>
                <input type="url" name="webhook_url" class="form-control" placeholder="https://example.com/webhook" required>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:600;"><?php echo __a('admin.webhooks.secret_label', 'HMAC-Secret (Geheimschlüssel)'); ?></label>
                <input type="text" name="webhook_secret" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.webhooks.secret_ph', 'Optional leer lassen für automatische Generierung')); ?>">
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label style="font-size:0.85rem; font-weight:600; margin-bottom:6px; display:block;"><?php echo __a('admin.webhooks.events_label', 'Ereignisse auswählen'); ?></label>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($webhook_events as $evKey => $evLabel): ?>
                        <label style="display:inline-flex; align-items:center; gap:8px; font-size:0.85rem; cursor:pointer; color:var(--text-secondary);">
                            <input type="checkbox" name="webhook_events[]" value="<?php echo htmlspecialchars($evKey); ?>" <?php echo ($evKey === '*') ? 'checked' : ''; ?> style="accent-color:var(--primary);">
                            <span><code><?php echo htmlspecialchars($evKey); ?></code> – <?php echo htmlspecialchars($evLabel); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="toggleWebhookModal(false)"><?php echo __a('admin.btn.cancel', 'Abbrechen'); ?></button>
                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:18px;">save</span> Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleApiModal(show) {
    const m = document.getElementById('modal-create-api-token');
    if (m) m.style.display = show ? 'flex' : 'none';
}

function toggleWebhookModal(show) {
    const m = document.getElementById('modal-create-webhook');
    if (m) m.style.display = show ? 'flex' : 'none';
}

function copyTokenText(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            alert('API-Token in die Zwischenablage kopiert!');
        });
    } else {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        alert('API-Token in die Zwischenablage kopiert!');
    }
}
</script>
