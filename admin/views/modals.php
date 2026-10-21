<?php
/**
 * WeaveCMS – Admin Modals (Restore Modal & Welcome Modal)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ======================================================================
             MODAL: WIEDERHERSTELLUNG BESTÄTIGEN (PIN-GESCHÜTZT)
             ====================================================================== -->
        <div id="restore-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:3000; align-items:center; justify-content:center; backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px);" onclick="if(event.target===this) closeRestoreModal();">
            <div style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:18px; max-width:480px; width:calc(100% - 2rem); padding:2rem; box-shadow:0 25px 60px rgba(0,0,0,0.35); position:relative;">
                <h3 style="margin:0 0 0.75rem 0; color:var(--primary); display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="font-size:24px;">settings_backup_restore</span> Sicherung wiederherstellen
                </h3>
                <p style="color:var(--text-secondary); font-size:0.9rem; line-height:1.5; margin-bottom:1.25rem;">
                    Möchtest du die Sicherung <strong id="modal-backup-filename" style="color:var(--text-primary); font-family:monospace; word-break:break-all;"></strong> wirklich einspielen?<br>
                    Alle aktuellen Website-Daten werden durch den Stand dieser Sicherung ersetzt. Zur Sicherheit legt das System vorab automatisch einen Notfall-Snapshot an.
                </p>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="restore_backup">
                    <input type="hidden" id="modal-backup-file-input" name="backup_file" value="">

                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label style="font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem; font-size:0.9rem;">
                            Administrator-PIN zur Autorisierung <span style="color:#ef4444;">*</span>
                        </label>
                        <div style="position:relative;">
                            <input type="password" id="modal-restore-pin" name="restore_pin" class="form-control" placeholder="Aktuelle PIN eingeben" maxlength="10" required style="padding-right:40px; letter-spacing:2px;">
                            <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('modal-restore-pin')">visibility</span>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" class="btn btn-outline" onclick="closeRestoreModal()">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="font-size:16px;">check_circle</span> Jetzt wiederherstellen
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================================
             MODAL: WILLKOMMEN NACH INSTALLATION (PHASE 5 - SCHRITT 5)
             ====================================================================== -->
        <?php 
            $show_welcome = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && (isset($_GET['installed']) || empty($data['settings']['welcome_shown']));
        ?>
        <?php if ($show_welcome): ?>
        <div id="welcome-modal" style="display:flex; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.8); z-index:3500; align-items:center; justify-content:center; backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); padding: 1.5rem;">
            <div class="card" style="max-width: 680px; width:100%; border:1px solid #34d399; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6); padding: 2.2rem; background: var(--bg-card); border-radius: 20px;">
                <div style="display:flex; align-items:center; gap: 14px; margin-bottom: 1.25rem;">
                    <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(52, 211, 153, 0.15); border: 1px solid #34d399; display: flex; align-items: center; justify-content: center; font-size: 26px;">
                        🌿
                    </div>
                    <div>
                        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin:0;">Herzlich willkommen bei WeaveCMS!</h2>
                        <p style="color: var(--text-secondary); font-size: 0.92rem; margin-top: 2px;">Dein Website-System ist erfolgreich eingerichtet und bereit für deine Inhalte.</p>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1.5rem;">
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 12px 14px; border-radius: 12px;">
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--primary); display:flex; align-items:center; gap:6px; margin-bottom: 4px;">
                            <span class="material-icons" style="font-size: 18px;">edit_note</span> 1. Texte &amp; Über mich
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                            Passe Titel, Slogans und deinen persönlichen Biografie-Text an.
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 12px 14px; border-radius: 12px;">
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--primary); display:flex; align-items:center; gap:6px; margin-bottom: 4px;">
                            <span class="material-icons" style="font-size: 18px;">palette</span> 2. Farben &amp; Themes
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                            Wähle aus 4 kuratierten Natur-Farbthemen und Galerie-Layouts.
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 12px 14px; border-radius: 12px;">
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--primary); display:flex; align-items:center; gap:6px; margin-bottom: 4px;">
                            <span class="material-icons" style="font-size: 18px;">photo_library</span> 3. Galerie &amp; Workshops
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                            Lade Fotos hoch, vergebe Kategorien und trage Kurstermine ein.
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 12px 14px; border-radius: 12px;">
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--primary); display:flex; align-items:center; gap:6px; margin-bottom: 4px;">
                            <span class="material-icons" style="font-size: 18px;">security</span> 4. Schutz &amp; Backups
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                            Setup ist gesperrt (<code style="font-family:monospace; color:#34d399;">installed.lock</code>). Lege hier 1-Klick-Sicherungen an.
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap: 12px; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                    <button type="button" class="btn btn-primary" style="padding: 10px 22px; font-weight: 600; display:inline-flex; align-items:center; gap:8px;" onclick="dismissWelcomeModal()">
                        <span class="material-icons" style="font-size: 18px;">check</span> Loslegen &amp; Administrationsbereich erkunden
                    </button>
                </div>
            </div>
        </div>
        <script>
        function dismissWelcomeModal() {
            const modal = document.getElementById('welcome-modal');
            if (modal) {
                modal.style.transition = 'opacity 0.25s ease';
                modal.style.opacity = '0';
                setTimeout(() => { modal.style.display = 'none'; }, 250);
            }
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'dismiss_welcome',
                    csrf_token: <?php echo json_encode($csrf_token); ?>,
                    ajax: '1'
                })
            }).catch(e => console.error(e));
        }
        </script>
        <?php endif; ?>
