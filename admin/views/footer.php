<?php
/**
 * WeaveCMS – Admin Footer & Inline Scripts View
 * 
 * @var string $active_tab Aktiver Reiter
 * @var array $data Gesamtdatenstruktur
 */
?>
    </main>

    <script>
        const serverActiveTab = <?php echo json_encode($active_tab); ?>;
        const serverHasMessage = <?php echo (!empty($success_msg) || !empty($error_msg)) ? 'true' : 'false'; ?>;
        const DEFAULT_PRIVACY_POLICY = <?php echo json_encode(get_default_privacy_policy_text($data)); ?>;
        function loadDefaultPrivacyPolicy() {
            if (confirm(<?php echo json_encode(__a('admin.footer.privacy_confirm', 'Möchtest du die rechtssichere DSGVO-Standardvorlage in das Textfeld einfügen? Vorhandener Text wird dabei ersetzt.')); ?>)) {
                const ta = document.getElementById('privacy_policy_text');
                if (ta) {
                    ta.value = DEFAULT_PRIVACY_POLICY;
                    const insertedMsg = <?php echo json_encode(__a('admin.footer.privacy_inserted', 'Standard-Datenschutzerklärung eingefügt! Bitte vergiss nicht, unten auf "Speichern" zu klicken.')); ?>;
                    if (typeof showToast === 'function') {
                        showToast(insertedMsg);
                    } else {
                        alert(insertedMsg);
                    }
                }
            }
        }

        // Modal für PIN-geschützte Wiederherstellung öffnen/schließen
        function openRestoreModal(filename, type) {
            document.getElementById('modal-backup-filename').textContent = filename;
            document.getElementById('modal-backup-file-input').value = filename;
            const pinField = document.getElementById('modal-restore-pin');
            if (pinField) pinField.value = '';
            const modal = document.getElementById('restore-modal');
            if (modal) {
                modal.style.display = 'flex';
                setTimeout(() => { if (pinField) pinField.focus(); }, 50);
            }
        }
        function closeRestoreModal() {
            const modal = document.getElementById('restore-modal');
            if (modal) modal.style.display = 'none';
        }

        // Live-Vorschau und Zeichenzähler für SEO-Tab
        function updateSeoCountersAndPreview() {
            const titleInput = document.getElementById('meta_title_input');
            const descInput = document.getElementById('meta_desc_input');
            const titleCount = document.getElementById('title-count');
            const descCount = document.getElementById('desc-count');
            const serpTitle = document.getElementById('serp-preview-title');
            const serpDesc = document.getElementById('serp-preview-desc');
            const socialTitle = document.getElementById('social-card-title');
            const socialDesc = document.getElementById('social-card-desc');

            const lblMissing = <?php echo json_encode(__a('admin.footer.seo_missing', 'fehlt')); ?>;
            const lblTooShort = <?php echo json_encode(__a('admin.footer.seo_too_short', 'zu kurz')); ?>;
            const lblTooLong = <?php echo json_encode(__a('admin.footer.seo_too_long', 'zu lang – wird gekürzt')); ?>;
            const lblOptimal = <?php echo json_encode(__a('admin.footer.seo_optimal', 'optimal')); ?>;

            // Ampel-Bewertung (Phase 24.3): rot = leer/zu extrem, gelb = suboptimal, grün = optimal
            const seoRating = function(len, min, max) {
                if (len === 0) return { color: '#ef4444', label: lblMissing };
                if (len < min) return { color: '#f59e0b', label: lblTooShort };
                if (len > max) return { color: '#f59e0b', label: lblTooLong };
                return { color: '#10b981', label: lblOptimal };
            };

            if (titleInput && titleCount) {
                const len = titleInput.value.length;
                titleCount.textContent = len;
                const tr = seoRating(len, 30, 60);
                titleCount.style.color = tr.color;
                titleCount.title = 'Title: ' + tr.label;
                const fallbackTitle = titleInput.getAttribute('placeholder') || 'WeaveCMS Website';
                const displayTitle = titleInput.value.trim() || fallbackTitle;
                if (serpTitle) serpTitle.textContent = displayTitle;
                if (socialTitle) socialTitle.textContent = displayTitle;
            }

            if (descInput && descCount) {
                const len = descInput.value.length;
                descCount.textContent = len;
                const dr = seoRating(len, 70, 155);
                descCount.style.color = dr.color;
                descCount.title = 'Description: ' + dr.label;
                const fallbackDesc = 'Entdecke handgefertigte Naturgeflechte und Workshops.';
                const displayDesc = descInput.value.trim() || fallbackDesc;
                if (serpDesc) serpDesc.textContent = displayDesc;
                if (socialDesc) socialDesc.textContent = displayDesc;
            }
        }

        // Live-Test für die MySQL-Datenbankverbindung bei Speicher-Migration (Phase 7.3)
        function testDbMigration() {
            const host = document.getElementById('mig_db_host')?.value || '';
            const port = document.getElementById('mig_db_port')?.value || '3306';
            const db = document.getElementById('mig_db_name')?.value || '';
            const user = document.getElementById('mig_db_user')?.value || '';
            const pass = document.getElementById('mig_db_pass')?.value || '';
            const createDb = document.getElementById('mig_create_db')?.checked ? '1' : '0';
            const btn = document.getElementById('mig_test_btn');
            const fb = document.getElementById('mig_test_feedback');

            if (!btn || !fb) return;

            const lblTesting = <?php echo json_encode(__a('admin.footer.db_testing', 'Teste...')); ?>;
            const lblChecking = <?php echo json_encode(__a('admin.footer.db_checking', 'Verbindung wird geprüft...')); ?>;
            const lblTestBtn = <?php echo json_encode(__a('admin.footer.db_test_btn', 'Verbindung testen')); ?>;
            const lblSuccess = <?php echo json_encode(__a('admin.footer.db_success', 'Verbindung erfolgreich!')); ?>;
            const lblFailed = <?php echo json_encode(__a('admin.footer.db_failed', 'Verbindung fehlgeschlagen.')); ?>;
            const lblServerError = <?php echo json_encode(__a('admin.footer.db_server_error', 'Serverfehler beim Verbindungstest.')); ?>;

            btn.disabled = true;
            btn.innerHTML = '<span class="material-icons spin" style="font-size:16px;">autorenew</span> ' + lblTesting;
            fb.style.display = 'inline-flex';
            fb.style.color = '#94a3b8';
            fb.innerHTML = lblChecking;

            const formData = new URLSearchParams();
            formData.append('host', host);
            formData.append('port', port);
            formData.append('database', db);
            formData.append('username', user);
            formData.append('password', pass);
            formData.append('create_db', createDb);

            fetch('?action=test_db_migration', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-icons" style="font-size:16px;">bolt</span> ' + lblTestBtn;
                fb.style.display = 'inline-flex';
                if (data.success) {
                    fb.style.color = '#10b981';
                    fb.innerHTML = '<span class="material-icons" style="font-size:16px;">check_circle</span> ' + (data.message || lblSuccess);
                } else {
                    fb.style.color = '#ef4444';
                    fb.innerHTML = '<span class="material-icons" style="font-size:16px;">error</span> ' + (data.message || lblFailed);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-icons" style="font-size:16px;">bolt</span> ' + lblTestBtn;
                fb.style.display = 'inline-flex';
                fb.style.color = '#ef4444';
                fb.innerHTML = '<span class="material-icons" style="font-size:16px;">error</span> ' + lblServerError;
            });
        }

        document.addEventListener('DOMContentLoaded', updateSeoCountersAndPreview);
    </script>
</body>
</html>
