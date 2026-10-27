<?php
/**
 * Frontend: Foren-Hilfe, Regeln & FAQ (Forum Help & Guidelines)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}
?>

<div class="kunena-help-wrapper">
    <!-- Header Banner -->
    <div class="kunena-cat-card" style="border-left-color: #8b5cf6; margin-bottom: 24px;">
        <div class="kunena-cat-header">
            <div class="kunena-cat-title-group">
                <span class="material-icons kunena-cat-icon" style="color: #8b5cf6;">help_outline</span>
                <h2 class="kunena-cat-title" style="font-size: 1.3rem;">
                    Hilfe, Forenregeln &amp; Häufige Fragen (FAQ)
                </h2>
            </div>
        </div>
        <div style="padding:14px 22px; font-size:13px; color:#64748b; background:#ffffff; line-height:1.5;">
            Willkommen im Hilfe-Bereich des WeaveCMS-Forums. Hier erfährst du alles Wichtige über Benutzerrollen, Textformatierung und Foren-Funktionen.
        </div>
    </div>

    <!-- 1. Wie erstelle ich ein Thema? -->
    <div class="kunena-cat-card" style="margin-bottom: 24px;">
        <div class="kunena-cat-header">
            <div class="kunena-cat-title-group">
                <span class="material-icons kunena-cat-icon">edit</span>
                <h3 class="kunena-cat-title" style="font-size: 1.1rem;">1. Wie kann ich ein Thema erstellen oder antworten?</h3>
            </div>
        </div>
        <div style="padding:18px 22px; font-size:13px; color:#334155; line-height:1.6; background:#ffffff;">
            <p style="margin-bottom: 10px;">
                <strong>Lesen:</strong> Alle Besucher können sämtliche öffentlichen Forenbereiche frei lesen, ohne sich anmelden zu müssen.
            </p>
            <p style="margin: 0;">
                <strong>Schreiben &amp; Diskutieren:</strong> Um ein neues Thema zu erstellen oder auf Beiträge zu antworten, klicke oben rechts auf 
                <a href="?page=forum&view=register" style="color:var(--primary, #2e6248); font-weight:700; text-decoration:underline;">Registrieren</a> oder 
                <a href="?page=forum&view=login" style="color:var(--primary, #2e6248); font-weight:700; text-decoration:underline;">Anmelden</a>.
                Sobald du angemeldet bist, erscheint in jedem offenen Forum der Button <em>„+ Neues Thema erstellen“</em> sowie unter jedem Beitrag der Antwort-Button.
            </p>
        </div>
    </div>

    <!-- 2. Textformatierung & Code-Snippets -->
    <div class="kunena-cat-card" style="margin-bottom: 24px;">
        <div class="kunena-cat-header">
            <div class="kunena-cat-title-group">
                <span class="material-icons kunena-cat-icon">code</span>
                <h3 class="kunena-cat-title" style="font-size: 1.1rem;">2. Textformatierung, Zitate &amp; Code-Blöcke</h3>
            </div>
        </div>
        <div style="padding:18px 22px; font-size:13px; color:#334155; line-height:1.6; background:#ffffff;">
            <p style="margin-bottom: 14px;">Das WeaveCMS-Forum unterstützt Markdown und Shortcodes für übersichtliche Beiträge:</p>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
                <div style="padding:14px; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc;">
                    <strong style="display:block; margin-bottom:8px; color:#0f172a;">Markdown-Syntax:</strong>
                    <ul style="padding-left:20px; margin:0; line-height:1.6;">
                        <li><code>**Fett**</code> für <strong>Fettgedruckten Text</strong></li>
                        <li><code>*Kursiv*</code> für <em>Kursiven Text</em></li>
                        <li><code>[Linktext](https://example.org)</code> für Hyperlinks</li>
                        <li><code>`inline code`</code> für Dateinamen oder Befehle</li>
                    </ul>
                </div>
                <div style="padding:14px; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc;">
                    <strong style="display:block; margin-bottom:8px; color:#0f172a;">Code-Blöcke &amp; Zitate:</strong>
                    <ul style="padding-left:20px; margin:0; line-height:1.6;">
                        <li><code>```php ... ```</code> für mehrzeiligen Quellcode mit Syntax-Hervorhebung</li>
                        <li><code>> Zitat-Text</code> für Zitate von anderen Mitgliedern</li>
                        <li>Klicke bei Beiträgen direkt auf <em>„Zitieren“</em>, um den Text vorzubereiten.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Benutzerrollen & Farbkennzeichnung -->
    <div class="kunena-cat-card" style="margin-bottom: 24px;">
        <div class="kunena-cat-header">
            <div class="kunena-cat-title-group">
                <span class="material-icons kunena-cat-icon">verified_user</span>
                <h3 class="kunena-cat-title" style="font-size: 1.1rem;">3. Benutzerrollen &amp; Farbkennzeichnung</h3>
            </div>
        </div>
        <div style="padding:18px 22px; font-size:13px; color:#334155; line-height:1.6; background:#ffffff;">
            <p style="margin-bottom: 12px;">Zur besseren Übersicht sind Benutzernamen im gesamten Forum farblich gekennzeichnet:</p>
            <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                <div style="display:inline-flex; align-items:center; gap:6px; font-weight:700; color:#dc2626;">
                    <span style="width:10px; height:10px; border-radius:50%; background:#dc2626; display:inline-block;"></span> Site Administrator (Core Team)
                </div>
                <div style="display:inline-flex; align-items:center; gap:6px; font-weight:700; color:#2563eb;">
                    <span style="width:10px; height:10px; border-radius:50%; background:#2563eb; display:inline-block;"></span> Moderator (Foren-Helfer)
                </div>
                <div style="display:inline-flex; align-items:center; gap:6px; font-weight:700; color:#475569;">
                    <span style="width:10px; height:10px; border-radius:50%; background:#475569; display:inline-block;"></span> Mitglied (Registrierter Nutzer)
                </div>
            </div>
        </div>
    </div>
</div>
