<?php
/**
 * Frontend: Foren-Hilfe, Regeln & FAQ (Forum Help & Guidelines)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}
?>

<div class="kunena-help-wrapper space-y-6">
    <!-- Header Banner -->
    <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card p-6">
        <h2 class="text-xl md:text-2xl font-bold flex items-center gap-2 text-foreground">
            <span class="material-icons text-primary">help_outline</span>
            <span>Hilfe, Forenregeln & Häufige Fragen (FAQ)</span>
        </h2>
        <p class="text-xs text-muted mt-1 leading-relaxed">
            Willkommen im Hilfe-Bereich des WeaveCMS-Forums. Hier erfährst du alles Wichtige über Benutzerrollen, Textformatierung und Foren-Funktionen.
        </p>
    </div>

    <!-- 1. Wie erstelle ich ein Thema? -->
    <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card p-6 space-y-4">
        <h3 class="text-base font-bold text-foreground flex items-center gap-2">
            <span class="material-icons text-primary text-lg">edit</span>
            <span>1. Wie kann ich ein Thema erstellen oder antworten?</span>
        </h3>
        <div class="text-xs text-muted leading-relaxed space-y-2">
            <p>
                <strong>Lesen:</strong> Alle Besucher können sämtliche öffentlichen Forenbereiche frei lesen, ohne sich anmelden zu müssen.
            </p>
            <p>
                <strong>Schreiben & Diskutieren:</strong> Um ein neues Thema zu erstellen oder auf Beiträge zu antworten, klicke oben rechts auf 
                <a href="?page=forum&view=register" class="text-primary font-bold hover:underline">Registrieren</a> oder 
                <a href="?page=forum&view=login" class="text-primary font-bold hover:underline">Anmelden</a>.
                Sobald du angemeldet bist, erscheint in jedem offenen Forum der Button <em>„+ Neues Thema erstellen“</em> sowie unter jedem Beitrag der Antwort-Button.
            </p>
        </div>
    </div>

    <!-- 2. Textformatierung & Code-Snippets -->
    <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card p-6 space-y-4">
        <h3 class="text-base font-bold text-foreground flex items-center gap-2">
            <span class="material-icons text-primary text-lg">code</span>
            <span>2. Textformatierung, Zitate & Code-Blöcke</span>
        </h3>
        <div class="text-xs text-muted leading-relaxed space-y-3">
            <p>Das WeaveCMS-Forum unterstützt Markdown und Shortcodes für übersichtliche Beiträge:</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-3 rounded-lg border border-border bg-gray-50/70 dark:bg-gray-800/40">
                    <strong class="text-foreground block mb-1">Markdown-Syntax:</strong>
                    <ul class="list-disc pl-4 space-y-1">
                        <li><code>**Fett**</code> für <strong>Fettgedruckten Text</strong></li>
                        <li><code>*Kursiv*</code> für <em>Kursiven Text</em></li>
                        <li><code>[Linktext](https://example.org)</code> für Hyperlinks</li>
                        <li><code>`inline code`</code> für Dateinamen oder Befehle</li>
                    </ul>
                </div>
                <div class="p-3 rounded-lg border border-border bg-gray-50/70 dark:bg-gray-800/40">
                    <strong class="text-foreground block mb-1">Code-Blöcke & Zitate:</strong>
                    <ul class="list-disc pl-4 space-y-1">
                        <li><code>```php ... ```</code> für mehrzeiligen Quellcode mit Syntax-Hervorhebung</li>
                        <li><code>> Zitat-Text</code> für Zitate von anderen Mitgliedern</li>
                        <li>Klicke bei Beiträgen direkt auf <em>„Zitieren“</em>, um den Text vorzubereiten.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Benutzerrollen & Farbkennzeichnung -->
    <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card p-6 space-y-4">
        <h3 class="text-base font-bold text-foreground flex items-center gap-2">
            <span class="material-icons text-primary text-lg">verified_user</span>
            <span>3. Benutzerrollen & Farbkennzeichnung</span>
        </h3>
        <div class="text-xs text-muted leading-relaxed space-y-2">
            <p>Zur besseren Übersicht sind Benutzernamen im gesamten Forum farblich gekennzeichnet:</p>
            <div class="flex items-center gap-4 flex-wrap pt-1">
                <div class="flex items-center gap-1.5 font-bold text-red-600">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span> Site Administrator (Core Team)
                </div>
                <div class="flex items-center gap-1.5 font-bold text-blue-600">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span> Moderator (Foren-Helfer)
                </div>
                <div class="flex items-center gap-1.5 font-bold text-slate-700 dark:text-slate-300">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span> Mitglied (Registrierter Nutzer)
                </div>
            </div>
        </div>
    </div>
</div>
