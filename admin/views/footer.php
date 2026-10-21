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
        const DEFAULT_PRIVACY_POLICY = <?php echo json_encode(get_default_privacy_policy_text($data)); ?>;
        function loadDefaultPrivacyPolicy() {
            if (confirm('Möchtest du die rechtssichere DSGVO-Standardvorlage in das Textfeld einfügen? Vorhandener Text wird dabei ersetzt.')) {
                const ta = document.getElementById('privacy_policy_text');
                if (ta) {
                    ta.value = DEFAULT_PRIVACY_POLICY;
                    if (typeof showToast === 'function') {
                        showToast('Standard-Datenschutzerklärung eingefügt!');
                    } else {
                        alert('Standard-Datenschutzerklärung eingefügt! Bitte vergiss nicht, unten auf "Speichern" zu klicken.');
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

            if (titleInput && titleCount) {
                const len = titleInput.value.length;
                titleCount.textContent = len;
                titleCount.style.color = len > 60 ? '#f59e0b' : 'inherit';
                const fallbackTitle = titleInput.getAttribute('placeholder') || 'WeaveCMS Website';
                const displayTitle = titleInput.value.trim() || fallbackTitle;
                if (serpTitle) serpTitle.textContent = displayTitle;
                if (socialTitle) socialTitle.textContent = displayTitle;
            }

            if (descInput && descCount) {
                const len = descInput.value.length;
                descCount.textContent = len;
                descCount.style.color = len > 155 ? '#f59e0b' : 'inherit';
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

            btn.disabled = true;
            btn.innerHTML = '<span class="material-icons spin" style="font-size:16px;">autorenew</span> Teste...';
            fb.style.display = 'inline-flex';
            fb.style.color = '#94a3b8';
            fb.innerHTML = 'Verbindung wird geprüft...';

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
                btn.innerHTML = '<span class="material-icons" style="font-size:16px;">bolt</span> Verbindung testen';
                fb.style.display = 'inline-flex';
                if (data.success) {
                    fb.style.color = '#10b981';
                    fb.innerHTML = '<span class="material-icons" style="font-size:16px;">check_circle</span> ' + (data.message || 'Verbindung erfolgreich!');
                } else {
                    fb.style.color = '#ef4444';
                    fb.innerHTML = '<span class="material-icons" style="font-size:16px;">error</span> ' + (data.message || 'Verbindung fehlgeschlagen.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-icons" style="font-size:16px;">bolt</span> Verbindung testen';
                fb.style.display = 'inline-flex';
                fb.style.color = '#ef4444';
                fb.innerHTML = '<span class="material-icons" style="font-size:16px;">error</span> Serverfehler beim Verbindungstest.';
            });
        }

        document.addEventListener('DOMContentLoaded', updateSeoCountersAndPreview);
    </script>
    <script src="../libs/js/admin.js?v=<?php echo file_exists('../libs/js/admin.js') ? filemtime('../libs/js/admin.js') : time(); ?>"></script>
</body>
</html>
