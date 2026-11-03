<?php
/**
 * ============================================================================
 * WeaveCMS – Visueller Sandbox- & Demo-Banner (views/banner.php) - Phase 15.4
 * ============================================================================
 * Fixes, elegantes Status-Banner am oberen Bildschirmrand im Admin und Frontend.
 */

$isDemoActive = class_exists('SandboxManager') && SandboxManager::isDemoActive();
$isDemoPaused = class_exists('SandboxManager') && SandboxManager::isDemoPaused();

if (!$isDemoActive && !$isDemoPaused) {
    return;
}

$inAdmin = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'admin') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');

if ($isDemoPaused): 
    $resumeUrl = $inAdmin ? './?demo=resume' : './?demo=resume';
    $discardUrl = $inAdmin ? './?demo=stop' : './?demo=stop';
?>
<div id="weave-demo-banner-paused" style="position: sticky; top: 0; z-index: 999999; background: #0f172a; color: #f8fafc; border-bottom: 2px solid #64748b; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif; font-size: 0.86rem; padding: 8px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; line-height: 1.4;">
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 6px; background: rgba(148, 163, 184, 0.2); color: #cbd5e1; font-weight: 700; font-size: 14px;">
            ⏸️
        </span>
        <div>
            <strong style="color: #ffffff;">Normaler Modus:</strong>
            <span style="color: #94a3b8; margin-left: 4px;">Du siehst die reguläre Produktiv-Website. Deine Sandbox-Sitzung ist im Hintergrund pausiert.</span>
        </div>
    </div>
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <a href="<?php echo htmlspecialchars($resumeUrl); ?>" style="display: inline-flex; align-items: center; gap: 5px; background: #3b82f6; color: #ffffff; text-decoration: none; padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 0.82rem; transition: background 0.2s;">
            <span class="material-icons" style="font-size: 15px;">play_arrow</span> Demo fortsetzen
        </a>
        <a href="<?php echo htmlspecialchars($discardUrl); ?>" onclick="return confirm('Möchtest du die pausierte Demo-Sitzung endgültig verwerfen und alle temporären Daten löschen?');" style="display: inline-flex; align-items: center; gap: 5px; background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); text-decoration: none; padding: 4px 10px; border-radius: 6px; font-weight: 500; font-size: 0.82rem; transition: background 0.2s;">
            <span class="material-icons" style="font-size: 15px;">delete_outline</span> Demo verwerfen
        </a>
    </div>
</div>
<?php return; endif; ?>

<?php
$remainingMins = SandboxManager::getRemainingMinutes();
$resetUrl = $inAdmin ? './?demo=reset' : './?demo=reset';
$pauseUrl = $inAdmin ? './?demo=pause' : './?demo=pause';
$stopUrl = $inAdmin ? './?demo=stop' : './?demo=stop';
$switchUrl = $inAdmin ? '../?demo=1' : './admin/?demo=1';
$switchLabel = $inAdmin ? 'Website live ansehen' : 'Admin-Panel testen';
$uploadCount = count($_SESSION['weave_demo_uploads'] ?? []);
$maxUploads = defined('SandboxManager::MAX_UPLOADS') ? SandboxManager::MAX_UPLOADS : 5;
?>
<div id="weave-demo-banner" style="position: sticky; top: 0; z-index: 999999; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #f8fafc; border-bottom: 2px solid #3b82f6; box-shadow: 0 4px 20px rgba(0,0,0,0.25); font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif; font-size: 0.88rem; padding: 10px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; line-height: 1.4;">
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; background: rgba(59, 130, 246, 0.2); color: #60a5fa; font-weight: 700; font-size: 16px;">
            🌿
        </span>
        <div>
            <strong style="color: #ffffff; letter-spacing: 0.3px;">WeaveCMS Live-Demo:</strong>
            <span style="color: #cbd5e1; margin-left: 4px;">Du kannst alle Funktionen ausprobieren. Änderungen werden <u>nicht</u> dauerhaft gespeichert.</span>
        </div>
        <span style="background: rgba(255,255,255,0.08); padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; color: #94a3b8; display: inline-flex; align-items: center; gap: 5px;">
            <span style="width: 7px; height: 7px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
            Sitzung aktiv (~<?php echo (int)$remainingMins; ?> Min. | Uploads: <?php echo (int)$uploadCount; ?>/<?php echo (int)$maxUploads; ?>)
        </span>
    </div>

    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <a href="<?php echo htmlspecialchars($switchUrl); ?>" style="display: inline-flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.1); color: #f1f5f9; text-decoration: none; padding: 5px 12px; border-radius: 6px; font-weight: 500; font-size: 0.82rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.18)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
            <span class="material-icons" style="font-size: 15px;">open_in_new</span> <?php echo htmlspecialchars($switchLabel); ?>
        </a>

        <a href="<?php echo htmlspecialchars($pauseUrl); ?>" style="display: inline-flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.08); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.15); text-decoration: none; padding: 5px 12px; border-radius: 6px; font-weight: 500; font-size: 0.82rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'" title="Zur normalen Website/Admin zurückkehren, ohne Demo-Änderungen zu löschen">
            <span class="material-icons" style="font-size: 15px;">visibility</span> Normaler Modus
        </a>

        <a href="<?php echo htmlspecialchars($resetUrl); ?>" onclick="return confirm('Möchtest du wirklich alle Demo-Änderungen verwerfen und auf den Ursprungszustand zurücksetzen?');" style="display: inline-flex; align-items: center; gap: 5px; background: #eab308; color: #713f12; text-decoration: none; padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 0.82rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            <span class="material-icons" style="font-size: 15px;">restart_alt</span> Demo zurücksetzen
        </a>

        <a href="<?php echo htmlspecialchars($stopUrl); ?>" onclick="return confirm('Demo-Modus jetzt beenden, alle temporären Daten löschen und zur normalen Website zurückkehren?');" style="display: inline-flex; align-items: center; gap: 5px; background: #ef4444; color: #ffffff; text-decoration: none; padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 0.82rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            <span class="material-icons" style="font-size: 15px;">close</span> Demo beenden
        </a>
    </div>
</div>
