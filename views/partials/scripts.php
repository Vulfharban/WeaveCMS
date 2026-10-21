<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Partial: Scripts & Lightbox Modal
 * ============================================================================
 *
 * @var array $data
 * @var array $visible_gallery
 */
?>
    <!-- Lightbox Modal -->
    <div id="gallery-lightbox" class="lightbox-modal" onclick="if(event.target === this) closeLightbox();" aria-hidden="true">
        <button class="lightbox-close" onclick="closeLightbox()" aria-label="Schließen" title="Schließen (Esc)">
            <span class="material-icons">close</span>
        </button>
        <button class="lightbox-nav lightbox-prev" onclick="changeLightbox(-1)" aria-label="Vorheriges Bild" title="Vorheriges Bild (Pfeil links)">
            <span class="material-icons">chevron_left</span>
        </button>
        <button class="lightbox-nav lightbox-next" onclick="changeLightbox(1)" aria-label="Nächstes Bild" title="Nächstes Bild (Pfeil rechts)">
            <span class="material-icons">chevron_right</span>
        </button>
        
        <div class="lightbox-content">
            <div class="lightbox-media-box">
                <img id="lightbox-img" src="" alt="">
            </div>
            <div class="lightbox-caption">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom: 0.4rem;">
                    <h3 id="lightbox-title" style="margin:0; font-size:1.3rem; color:#fff;"></h3>
                    <span id="lightbox-category" class="lightbox-badge"></span>
                </div>
                <p id="lightbox-desc" style="margin:0; font-size:0.95rem; color:#d1d5db; line-height:1.5;"></p>
                <div id="lightbox-counter" style="margin-top:0.5rem; font-size:0.8rem; color:#9ca3af; text-align:right;"></div>
            </div>
        </div>
    </div>

    <!-- Dynamische Server-Daten vor dem Hauptskript bereitstellen -->
    <script>
        window.SERVER_GALLERY_ITEMS = <?php echo json_encode(array_values(array_map(function($item) {
            $raw = $item['image'] ?? '';
            $img_src = (strpos($raw, 'http://') === 0 || strpos($raw, 'https://') === 0)
                ? $raw
                : ((strpos($raw, '/') === 0) ? '.' . $raw : './' . $raw);
            return [
                'id' => $item['id'],
                'title' => $item['title'] ?? '',
                'category' => $item['category'] ?? '',
                'description' => $item['description'] ?? '',
                'image' => $img_src
            ];
        }, $visible_gallery))); ?>;
        
        window.SERVER_SPAM_ICONS = <?php echo json_encode(array_values(CaptchaManager::getActiveIcons($data))); ?>;
        window.SERVER_SMTP_ENABLED = <?php echo (!empty($data['settings']['smtp']['enabled']) && !empty($data['settings']['smtp']['host'])) ? 'true' : 'false'; ?>;
        window.CONTACT_MAIL_GREETING = <?php echo json_encode($data['texts']['contact_mail_greeting'] ?? 'Hallo Maxima,'); ?>;
        window.COURSE_REQUEST_TEMPLATE = <?php echo json_encode($data['texts']['course_request_template'] ?? "Hallo Maxima,\n\nich interessiere mich für deinen Workshop \"{kurs}\" am {datum} ({uhrzeit}) in {ort} und möchte fragen, ob noch Plätze frei sind.\n\nViele Grüße"); ?>;
    </script>
    
    <!-- Externes Haupt-JavaScript einbinden -->
    <script src="libs/js/main.js?v=<?php echo file_exists(__DIR__ . '/../../libs/js/main.js') ? filemtime(__DIR__ . '/../../libs/js/main.js') : time(); ?>"></script>
    <script>
        if (typeof initGalleryData === 'function') initGalleryData(window.SERVER_GALLERY_ITEMS);
        if (typeof initSpamIcons === 'function') initSpamIcons(window.SERVER_SPAM_ICONS);

        // Umschalten zwischen Impressum und Datenschutzerklärung im Rechts-Slide-In
        function switchLegalTab(tab) {
            const impPanel = document.getElementById('legal-panel-impressum');
            const dsPanel = document.getElementById('legal-panel-datenschutz');
            const impBtn = document.getElementById('legal-tab-btn-impressum');
            const dsBtn = document.getElementById('legal-tab-btn-datenschutz');
            if (!impPanel || !dsPanel) return;

            if (tab === 'datenschutz') {
                impPanel.style.display = 'none';
                dsPanel.style.display = 'block';
                if (impBtn) { impBtn.className = 'btn btn-outline'; }
                if (dsBtn) { dsBtn.className = 'btn btn-primary'; }
            } else {
                impPanel.style.display = 'flex';
                dsPanel.style.display = 'none';
                if (impBtn) { impBtn.className = 'btn btn-primary'; }
                if (dsBtn) { dsBtn.className = 'btn btn-outline'; }
            }
        }

        // Falls Hash #datenschutz in der URL steht, direkt das Datenschutz-Panel aktivieren
        if (window.location.hash === '#datenschutz') {
            const toggle = document.getElementById('menu-toggle-impressum');
            if (toggle) toggle.checked = true;
            switchLegalTab('datenschutz');
        }
    </script>
</body>
</html>
