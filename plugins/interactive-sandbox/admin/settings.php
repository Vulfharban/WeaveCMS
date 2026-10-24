<?php
/**
 * ============================================================================
 * WeaveCMS – Sandbox Plugin Einstellungen & Dashboard (admin/settings.php)
 * ============================================================================
 */

$isDemoActive = class_exists('SandboxManager') && SandboxManager::isDemoActive();
$sandboxDir = dirname(__DIR__, 2) . '/data/sandbox';
$activeFiles = is_dir($sandboxDir) ? array_filter((array)@scandir($sandboxDir), fn($f) => str_ends_with($f, '.json')) : [];
$activeCount = count($activeFiles);
$remainingMins = $isDemoActive ? SandboxManager::getRemainingMinutes() : 0;
?>
<div class="sandbox-settings-panel" style="background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 14px; padding: 1.75rem; margin-top: 1rem;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h3 style="margin: 0 0 0.4rem 0; font-size: 1.25rem; display: flex; align-items: center; gap: 8px; color: var(--primary);">
                <span class="material-icons" style="font-size: 26px;">science</span> Interaktive Sandbox & Live-Demo Steuerung
            </h3>
            <p style="margin: 0; color: var(--text-secondary); font-size: 0.92rem;">
                Ermöglicht Interessenten und Testern, das gesamte WeaveCMS interaktiv auszuprobieren, ohne dass Änderungen in <code>data.json</code> oder der Produktiv-Datenbank landen.
            </p>
        </div>
        <div>
            <?php if ($isDemoActive): ?>
                <span style="background: rgba(34,197,94,0.15); color: #16a34a; font-size: 0.82rem; font-weight: 700; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e;"></span>
                    Deine Demo-Sitzung ist AKTIV (~<?php echo (int)$remainingMins; ?> Min.)
                </span>
            <?php else: ?>
                <span style="background: rgba(100,116,139,0.1); color: #64748b; font-size: 0.82rem; font-weight: 600; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #94a3b8;"></span>
                    Produktiv-Modus (Sandbox inaktiv)
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Schnellstarter-Box -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.75rem;">
        <div style="background: rgba(59,130,246,0.04); border: 1px solid rgba(59,130,246,0.18); border-radius: 12px; padding: 1.25rem;">
            <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: #1e40af; display: flex; align-items: center; gap: 6px;">
                <span class="material-icons" style="font-size: 20px;">admin_panel_settings</span> Admin-Panel Live-Tryout
            </h4>
            <p style="margin: 0 0 1rem 0; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4;">
                Startet eine isolierte Admin-Sitzung mit Musterinhalten aus <code>data.sample.json</code>.
            </p>
            <a href="./?demo=start" target="_blank" class="btn btn-primary" style="padding: 7px 14px; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span class="material-icons" style="font-size: 16px;">launch</span> Admin-Demo in neuem Tab öffnen
            </a>
        </div>

        <div style="background: rgba(16,185,129,0.04); border: 1px solid rgba(16,185,129,0.18); border-radius: 12px; padding: 1.25rem;">
            <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: #065f46; display: flex; align-items: center; gap: 6px;">
                <span class="material-icons" style="font-size: 20px;">preview</span> Frontend Live-Tryout
            </h4>
            <p style="margin: 0 0 1rem 0; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4;">
                Öffnet die Website mit flüchtiger Demo-Ansicht für Besucher (z. B. für Präsentationen).
            </p>
            <a href="../?demo=start" target="_blank" class="btn btn-outline" style="padding: 7px 14px; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span class="material-icons" style="font-size: 16px;">open_in_browser</span> Website-Demo in neuem Tab öffnen
            </a>
        </div>
    </div>

    <!-- Status & Garbage Collection -->
    <div style="background: rgba(0,0,0,0.02); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem;">
                Aktive Sandbox-Instanzen im Speicher: <span style="color: var(--primary); font-weight: 700;"><?php echo (int)$activeCount; ?></span>
            </div>
            <div style="font-size: 0.82rem; color: var(--text-secondary);">
                Speicherort: <code>data/sandbox/</code> &bull; Automatische Bereinigung inaktiver Sitzungen nach 30 Minuten.
            </div>
        </div>

        <form method="POST" action="" style="margin: 0;">
            <input type="hidden" name="action" value="sandbox_cleanup">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-icons" style="font-size: 16px;">delete_sweep</span> Veraltete Sandbox-Daten jetzt bereinigen
            </button>
        </form>
    </div>
</div>
