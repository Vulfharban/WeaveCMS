/**
 * ============================================================================
 * Backend Admin JavaScript: WeaveCMS Administration
 * Interaktive Funktionen für Tab-Navigation, Layout- & Logo-Auswahl, Toast
 * ============================================================================
 */

const CONTENT_TABS = ['tab-texts', 'tab-pages', 'tab-blog', 'tab-faqs', 'tab-team', 'tab-pricing', 'tab-downloads', 'tab-gallery', 'tab-courses', 'tab-past', 'tab-impressum'];
let lastContentTab = 'tab-texts';

/**
 * Schaltet zwischen den verschiedenen Verwaltungs-Reitern (Tabs) um.
 * Unterstützt die Bündelung aller Inhaltsseiten unter "Inhalte" mit dynamischer Sub-Navigation.
 * @param {string} tabId - ID des Zielabschnitts (z. B. 'tab-texts', 'tab-blog', 'tab-settings')
 * @param {boolean} [scrollToTop=true] - Ob nach dem Wechsel an den Seitenanfang gescrollt werden soll
 */
function switchTab(tabId, scrollToTop = true) {
    if (tabId === 'tab-content') {
        tabId = lastContentTab || 'tab-texts';
    }
    if (CONTENT_TABS.includes(tabId)) {
        lastContentTab = tabId;
    }

    // Alle Abschnitte ausblenden
    document.querySelectorAll('.admin-section').forEach(sec => {
        sec.classList.remove('active');
    });
    
    // Alle Menü-Buttons deaktivieren
    document.querySelectorAll('.admin-nav-link').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.admin-subnav-link').forEach(btn => {
        btn.classList.remove('active');
    });

    // Gewünschten Abschnitt einblenden
    const sec = document.getElementById(tabId);
    if (sec) sec.classList.add('active');
    
    const isContent = CONTENT_TABS.includes(tabId);
    const subnav = document.getElementById('admin-content-subnav');
    const contentMainBtn = document.getElementById('main-nav-content');

    if (subnav) {
        subnav.style.display = isContent ? 'flex' : 'none';
    }

    if (isContent) {
        if (contentMainBtn) contentMainBtn.classList.add('active');
        const targetSubBtn = Array.from(document.querySelectorAll('.admin-subnav-link')).find(btn => {
            return btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(tabId);
        });
        if (targetSubBtn) targetSubBtn.classList.add('active');
    } else {
        const targetBtn = Array.from(document.querySelectorAll('.admin-nav-link')).find(btn => {
            return btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(tabId);
        });
        if (targetBtn) targetBtn.classList.add('active');
    }
    
    // URL ohne veraltete Query-Parameter aktualisieren (relevante Parameter wie edit_lang beibehalten)
    if (window.history && window.history.replaceState) {
        const urlParams = new URLSearchParams(window.location.search);
        let cleanQuery = '';
        if (tabId === 'tab-texts' && urlParams.get('edit_lang')) {
            cleanQuery = '?edit_lang=' + encodeURIComponent(urlParams.get('edit_lang'));
        }
        const cleanUrl = window.location.pathname + cleanQuery + '#' + tabId;
        history.replaceState(null, '', cleanUrl);
    }

    if (scrollToTop) {
        window.scrollTo({ top: 0, behavior: 'instant' });
    }
}

/**
 * Schaltet die Sichtbarkeit eines Passwort- oder PIN-Eingabefelds um.
 * @param {string} fieldId - ID des Eingabefelds
 */
function toggleVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    const icon = field.nextElementSibling;
    if (field.type === 'password') {
        field.type = 'text';
        if (icon) icon.textContent = 'visibility_off';
    } else {
        field.type = 'password';
        if (icon) icon.textContent = 'visibility';
    }
}

/**
 * Bestätigt und sendet das Formular zum Löschen eines Flechtkurses.
 * @param {number|string} id - ID des zu löschenden Kurses
 */
function deleteCourse(id) {
    if (confirm('Möchtest du diesen Kurs wirklich komplett löschen?')) {
        const form = document.getElementById('delete-form-' + id);
        if (form) form.submit();
    }
}

/**
 * Kopiert einen beliebigen Text (z. B. Einladungslink) in die Zwischenablage.
 * @param {string} text - Der zu kopierende Link-Text
 */
function copyLink(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Einladungslink in Zwischenablage kopiert!');
        }).catch(() => {
            prompt('Einladungslink zum Kopieren:', text);
        });
    } else {
        prompt('Einladungslink zum Kopieren:', text);
    }
}

/**
 * Kopiert den Einladungslink eines Flechtkurses in die Zwischenablage (Button-Variante).
 * @param {HTMLElement} btnEl - Der geklickte Button
 * @param {string} text - Der zu kopierende Link-Text
 */
function copyInviteLink(btnEl, text) {
    copyLink(text);
}

/**
 * Wählt eine Galerie-Layout-Karte aus und markiert das zugehörige Radio-Element.
 * @param {HTMLElement} cardEl - Das angeklickte Label-Element
 */
function selectLayoutCard(cardEl) {
    const grid = cardEl.closest('.layout-options-grid');
    if (grid) {
        grid.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
    } else {
        document.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
    }
    cardEl.classList.add('selected');
    const radio = cardEl.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

/**
 * Phase 19: Wählt den Menüstil aus und schaltet bei Bedarf die Sidebar-Optionen sichtbar.
 * @param {HTMLElement} cardEl
 * @param {string} styleKey
 */
function selectMenuStyleCard(cardEl, styleKey) {
    selectLayoutCard(cardEl);
    const sidebarOpts = document.getElementById('sidebar-position-options');
    if (sidebarOpts) {
        sidebarOpts.style.display = (styleKey === 'vertical_sidebar') ? 'block' : 'none';
    }
}

/**
 * Wählt ein Hundetatzen-Motiv aus und aktiviert das zugehörige Radio-Element.
 * @param {HTMLElement} cardEl - Das angeklickte Label-Element
 * @param {string} motifVal - Wert des Motivs ('paw_simple', 'paw_beach', 'paw_willow', 'none')
 */
function selectLogoMotif(cardEl, motifVal) {
    const grid = cardEl.closest('.layout-options-grid');
    if (grid) {
        grid.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
    }
    cardEl.classList.add('selected');
    const radio = cardEl.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

/**
 * Wählt die Logoposition aus und aktiviert das zugehörige Radio-Element.
 * @param {HTMLElement} cardEl - Das angeklickte Label-Element
 * @param {string} posVal - Wert der Position ('header_before', 'header_after', 'hero_before', 'hero_after')
 */
function selectLogoPosition(cardEl, posVal) {
    const grid = cardEl.closest('.layout-options-grid');
    if (grid) {
        grid.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
    }
    cardEl.classList.add('selected');
    const radio = cardEl.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

/**
 * Aktualisiert die Farbe der SVG-Tatzen in der Live-Vorschau und den Hex-Code.
 * @param {string} colorVal - Hex-Farbcode (z. B. '#b26c39')
 */
function updateLogoColor(colorVal) {
    const disp = document.getElementById('color_code_display');
    if (disp) disp.textContent = colorVal;
    document.querySelectorAll('#tab-design .logo-paw-wrapper, #tab-design .hero-logo-wrapper, #tab-settings .logo-paw-wrapper, #tab-settings .hero-logo-wrapper').forEach(wrap => {
        wrap.style.color = colorVal;
    });
}

/**
 * Setzt eine der vordefinierten Farbpaletten (z. B. Weiden-Braun oder Wald-Grün).
 * @param {string} colorVal - Hex-Farbcode der Palette
 */
function setColorPreset(colorVal) {
    const input = document.getElementById('hero_logo_color');
    if (input) {
        input.value = colorVal;
        updateLogoColor(colorVal);
    }
}

/**
 * Vordefinierte Natur-Farbthemen (Hybrid-Modell)
 */
const DESIGN_THEME_PALETTES = {
    nature_classic: { primary: '#2e6248', accent: '#b26c39', bg: '#fbf9f6' },
    moor_amber: { primary: '#4a3f35', accent: '#d97706', bg: '#f7f4ee' },
    nordic_sage: { primary: '#3a6351', accent: '#2c3e50', bg: '#f4f6f5' },
    terracotta_atelier: { primary: '#a85832', accent: '#322822', bg: '#faf7f2' }
};

/**
 * Wählt ein vordefiniertes Design-Thema aus und synchronisiert die Farbwähler.
 * @param {HTMLElement} cardEl - Die angeklickte Design-Karte
 * @param {string} themeVal - 'nature_classic', 'moor_amber', 'nordic_sage', 'terracotta_atelier', 'custom'
 */
function selectDesignTheme(cardEl, themeVal) {
    const grid = cardEl.closest('.layout-options-grid');
    if (grid) {
        grid.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
    }
    cardEl.classList.add('selected');
    const radio = cardEl.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;

    if (DESIGN_THEME_PALETTES[themeVal]) {
        const pal = DESIGN_THEME_PALETTES[themeVal];
        
        const priInput = document.getElementById('primary_color');
        const accInput = document.getElementById('accent_color');
        const bgInput = document.getElementById('bg_color');
        
        if (priInput) {
            priInput.value = pal.primary;
            const priDisp = document.getElementById('display_primary_color');
            if (priDisp) priDisp.textContent = pal.primary;
        }
        if (accInput) {
            accInput.value = pal.accent;
            const accDisp = document.getElementById('display_accent_color');
            if (accDisp) accDisp.textContent = pal.accent;
        }
        if (bgInput) {
            bgInput.value = pal.bg;
            const bgDisp = document.getElementById('display_bg_color');
            if (bgDisp) bgDisp.textContent = pal.bg;
        }

        updateCustomSwatches(pal.primary, pal.accent, pal.bg);
    }
}

/**
 * Reagiert auf manuelle Änderungen an den Farb-Pickern und setzt das Theme auf "Individuell".
 * @param {string} type - 'primary', 'accent', oder 'bg'
 * @param {string} val - Hex-Farbcode
 */
function onCustomColorChange(type, val) {
    const disp = document.getElementById('display_' + type + '_color');
    if (disp) disp.textContent = val;

    // Theme-Karte automatisch auf 'custom' (Individuell) umstellen
    const customCard = document.getElementById('theme_card_custom');
    if (customCard) {
        const grid = customCard.closest('.layout-options-grid');
        if (grid) {
            grid.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
        }
        customCard.classList.add('selected');
        const radio = customCard.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    }

    const pri = document.getElementById('primary_color') ? document.getElementById('primary_color').value : '#2e6248';
    const acc = document.getElementById('accent_color') ? document.getElementById('accent_color').value : '#b26c39';
    const bg = document.getElementById('bg_color') ? document.getElementById('bg_color').value : '#fbf9f6';
    updateCustomSwatches(pri, acc, bg);
}

/**
 * Aktualisiert die Farbkreise in der "Individuell"-Karte in Echtzeit.
 */
function updateCustomSwatches(primary, accent, bg) {
    const swPri = document.querySelector('.custom-swatch-primary');
    const swAcc = document.querySelector('.custom-swatch-accent');
    const swBg = document.querySelector('.custom-swatch-bg');
    if (swPri) swPri.style.backgroundColor = primary;
    if (swAcc) swAcc.style.backgroundColor = accent;
    if (swBg) swBg.style.backgroundColor = bg;
}

/**
 * Bestätigungsdialog beim Löschen einer Galerie-Kategorie.
 * Warnt eindringlich, falls der Kategorie noch Galerie-Elemente zugeordnet sind.
 * @param {string} catName - Name der Kategorie
 * @param {number} count - Anzahl zugeordneter Werkstücke
 * @return {boolean} - true wenn bestätigt, sonst false
 */
function confirmCategoryDelete(catName, count) {
    let msg = '';
    if (count > 0) {
        msg = '⚠️ Achtung: Der Kategorie "' + catName + '" sind aktuell ' + count + ' Galerie-Elemente zugeordnet!\n\n' +
              'Wenn du diese Kategorie löschst, werden die zugeordneten Elemente NICHT gelöscht, aber auf der Website im Galerie-Filter nicht mehr angezeigt.\n\n' +
              'Möchtest du die Kategorie "' + catName + '" wirklich löschen?';
    } else {
        msg = 'Möchtest du die Kategorie "' + catName + '" wirklich löschen?';
    }
    return confirm(msg);
}

/**
 * Blendet eine kurze Erfolgsnachricht (Toast) am unteren Bildschirmrand ein.
 * @param {string} msg - Die Erfolgsmeldung
 */
function showToast(msg) {
    const toast = document.getElementById('toast');
    const textEl = document.getElementById('toast-text');
    if (toast && textEl) {
        textEl.innerText = msg;
        toast.className = 'show';
        setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 3000);
    }
}

/**
 * Validiert das Formular für Spamschutz-, SMTP- und Upload-Einstellungen.
 * Benötigt KEINE PIN-Eingabe!
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function validateSystemForm(form) {
    const selectedMode = form.querySelector('input[name="captcha_mode"]:checked')?.value || 'icons';
    if (selectedMode === 'icons') {
        const checkedCaptcha = form.querySelectorAll('input[name="captcha_icons[]"]:checked');
        if (checkedCaptcha.length !== 4) {
            alert(`⚠️ Spamschutz-Symbole:\n\nBitte wähle genau 4 Symbole für den Spamschutz im Kontaktformular aus!\n(Aktuell ausgewählt: ${checkedCaptcha.length} von 4)`);
            return false;
        }
    }
    return true;
}

/**
 * Validiert das separate Formular zur Änderung der Admin-Zugangsdaten (Passwort & PIN).
 * Erfordert zwingend die Eingabe der aktuell gültigen PIN.
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function validateCredentialsForm(form) {
    const curPin = form.querySelector('#current_pin');
    const newPw = form.querySelector('#new_password');
    const newPin = form.querySelector('#new_pin');
    
    const hasNewPw = newPw && newPw.value.trim() !== '';
    const hasNewPin = newPin && newPin.value.trim() !== '';

    if (!hasNewPw && !hasNewPin) {
        alert('⚠️ Keine Eingabe:\n\nBitte gib ein neues Passwort oder eine neue PIN ein, die du setzen möchtest.');
        if (newPw) newPw.focus();
        return false;
    }

    if (!curPin || curPin.value.trim() === '') {
        alert('⚠️ Sicherheitsabfrage:\n\nBitte gib deine aktuell gültige PIN ein, um Passwort oder PIN zu ändern!');
        if (curPin) {
            curPin.removeAttribute('readonly');
            curPin.focus();
        }
        return false;
    }

    return true;
}

/**
 * Abwärtskompatibler Fallback für validateSecurityForm
 */
function validateSecurityForm(form) {
    return validateSystemForm(form);
}

/**
 * Überwacht die Auswahl der Spamschutz-Symbole im Admin-Bereich (maximal 4 erlaubt).
 * Aktualisiert visuelle Hervorhebung und Zähleranzeige in Echtzeit.
 * @param {HTMLInputElement} changedCheckbox
 */
function updateCaptchaSelection(changedCheckbox) {
    const checkboxes = document.querySelectorAll('input[name="captcha_icons[]"]');
    const checked = Array.from(checkboxes).filter(cb => cb.checked);
    
    if (checked.length > 4) {
        if (changedCheckbox) {
            changedCheckbox.checked = false;
        }
        alert('⚠️ Maximal 4 Symbole:\n\nEs können genau 4 Symbole für das Kontaktformular ausgewählt werden. Bitte deaktiviere zuerst ein anderes Symbol, bevor du ein neues auswählst.');
        return;
    }
    
    checkboxes.forEach(cb => {
        const card = cb.closest('.captcha-card');
        if (card) {
            if (cb.checked) {
                card.style.borderColor = 'var(--primary)';
                card.style.background = 'rgba(46,98,72,0.08)';
            } else {
                card.style.borderColor = 'var(--border-color)';
                card.style.background = 'rgba(0,0,0,0.015)';
            }
        }
    });
    
    const countSpan = document.getElementById('captcha-count');
    const badge = document.getElementById('captcha-counter-badge');
    const currentCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    if (countSpan) countSpan.innerText = currentCount;
    if (badge) {
        if (currentCount === 4) {
            badge.style.background = 'rgba(46,98,72,0.12)';
            badge.style.color = 'var(--primary)';
        } else {
            badge.style.background = 'rgba(239,68,68,0.12)';
            badge.style.color = '#dc2626';
        }
    }
}

/**
 * Schaltet den Logo-Typ im Design-Tab um (text, image, icon_text, motif).
 * @param {HTMLElement} cardEl 
 * @param {string} typeVal 
 */
function selectLogoType(cardEl, typeVal) {
    if (!cardEl) return;
    const container = cardEl.closest('.layout-selector');
    if (container) {
        container.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
        cardEl.classList.add('selected');
    }
    const radio = cardEl.querySelector('input[type="radio"]');
    if (radio) {
        radio.checked = true;
    }

    const imgBox = document.getElementById('logo-image-settings-box');
    if (imgBox) {
        imgBox.style.display = (typeVal === 'image' || typeVal === 'icon_text') ? 'block' : 'none';
    }

    const motifBox = document.getElementById('logo-motif-settings-box');
    if (motifBox) {
        motifBox.style.display = (typeVal === 'motif') ? 'block' : 'none';
    }

    const textLight = document.getElementById('preview-logo-text-light');
    const textDark = document.getElementById('preview-logo-text-dark');
    if (textLight) textLight.style.display = (typeVal === 'image') ? 'none' : 'inline';
    if (textDark) textDark.style.display = (typeVal === 'image') ? 'none' : 'inline';
}

/**
 * Aktualisiert die Anzeige und Live-Vorschau der Logo-Höhe.
 * @param {number|string} val 
 */
function updateLogoMaxHeight(val) {
    const disp = document.getElementById('logo_height_display');
    if (disp) disp.textContent = val + ' px';

    const lightImg = document.getElementById('preview-logo-img-light');
    if (lightImg) lightImg.style.maxHeight = val + 'px';

    const darkImg = document.getElementById('preview-logo-img-dark');
    if (darkImg) darkImg.style.maxHeight = val + 'px';
}

/**
 * Erzeugt eine sofortige Live-Vorschau für hochgeladene Logo-Dateien im Browser.
 * @param {HTMLInputElement} input 
 */
function previewLogoFile(input) {
    if (!input || !input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        const hRange = document.getElementById('logo_max_height_range');
        const hVal = hRange ? hRange.value : 44;
        
        ['light', 'dark'].forEach(theme => {
            const container = document.getElementById('preview-logo-container-' + theme);
            if (!container) return;

            let img = document.getElementById('preview-logo-img-' + theme);
            const placeholder = document.getElementById('preview-logo-placeholder-' + theme);

            if (!img) {
                img = document.createElement('img');
                img.id = 'preview-logo-img-' + theme;
                img.alt = 'Logo';
                img.style.objectFit = 'contain';
                img.style.width = 'auto';
                if (placeholder) {
                    placeholder.replaceWith(img);
                } else {
                    container.insertBefore(img, container.firstChild);
                }
            } else if (placeholder) {
                placeholder.remove();
            }

            img.src = e.target.result;
            img.style.maxHeight = hVal + 'px';
        });
    };

    reader.readAsDataURL(file);
}

/* ============================================================================
 * Modul-Pool & Seiten-Aufbau Board (Phase 20)
 * ============================================================================ */

function switchPoolMobileTab(tab) {
    const grid = document.getElementById('sec-pool-grid');
    const btnActive = document.getElementById('btn-show-active');
    const btnPool = document.getElementById('btn-show-pool');
    if (!grid) return;

    if (tab === 'active') {
        grid.classList.remove('view-pool');
        grid.classList.add('view-active');
        if (btnActive) { btnActive.className = 'btn btn-primary sec-view-tab-btn active'; }
        if (btnPool) { btnPool.className = 'btn btn-outline sec-view-tab-btn'; }
    } else {
        grid.classList.remove('view-active');
        grid.classList.add('view-pool');
        if (btnActive) { btnActive.className = 'btn btn-outline sec-view-tab-btn'; }
        if (btnPool) { btnPool.className = 'btn btn-primary sec-view-tab-btn active'; }
    }
}

let draggedSecCard = null;

function handleSecDragStart(e) {
    const card = e.target.closest('.sec-card');
    if (!card) return;
    draggedSecCard = card;
    card.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', card.getAttribute('data-sec-id'));
}

function handleSecDragEnd(e) {
    if (draggedSecCard) {
        draggedSecCard.classList.remove('dragging');
    }
    draggedSecCard = null;
    document.querySelectorAll('.sec-dropzone').forEach(dz => dz.classList.remove('drag-over'));
}

function handleSecDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const dropzone = e.target.closest('.sec-dropzone');
    if (!dropzone) return;
    dropzone.classList.add('drag-over');

    // Wenn im aktiven Bereich: Dynamisch vor das nächste Element sortieren
    if (dropzone.id === 'dropzone-active' && draggedSecCard) {
        const afterElement = getDragAfterElement(dropzone, e.clientY);
        if (afterElement == null) {
            dropzone.appendChild(draggedSecCard);
        } else {
            dropzone.insertBefore(draggedSecCard, afterElement);
        }
    }
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.sec-card:not(.dragging)')];
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function handleSecDragLeave(e) {
    const dropzone = e.target.closest('.sec-dropzone');
    if (dropzone && !dropzone.contains(e.relatedTarget)) {
        dropzone.classList.remove('drag-over');
    }
}

function handleSecDrop(e, targetZone) {
    e.preventDefault();
    document.querySelectorAll('.sec-dropzone').forEach(dz => dz.classList.remove('drag-over'));
    const dropzone = document.getElementById(targetZone === 'active' ? 'dropzone-active' : 'dropzone-pool');
    if (!dropzone || !draggedSecCard) return;

    if (targetZone === 'pool') {
        dropzone.appendChild(draggedSecCard);
    }
    transformSecCardState(draggedSecCard, targetZone === 'active');
    syncAndSavePool();
}

function activateSectionFromPool(secId) {
    const card = document.querySelector(`.sec-card[data-sec-id="${secId}"]`);
    const activeZone = document.getElementById('dropzone-active');
    if (!card || !activeZone) return;

    activeZone.appendChild(card);
    transformSecCardState(card, true);
    syncAndSavePool();
}

function deactivateSectionToPool(secId) {
    const card = document.querySelector(`.sec-card[data-sec-id="${secId}"]`);
    const poolZone = document.getElementById('dropzone-pool');
    if (!card || !poolZone) return;

    poolZone.appendChild(card);
    transformSecCardState(card, false);
    syncAndSavePool();
}

function moveActiveSectionCard(secId, direction) {
    const card = document.querySelector(`.sec-card[data-sec-id="${secId}"]`);
    if (!card) return;
    const parent = card.parentElement;

    if (direction === 'up') {
        const prev = card.previousElementSibling;
        if (prev && prev.classList.contains('sec-card')) {
            parent.insertBefore(card, prev);
            syncAndSavePool();
        }
    } else if (direction === 'down') {
        const next = card.nextElementSibling;
        if (next && next.classList.contains('sec-card')) {
            parent.insertBefore(next, card);
            syncAndSavePool();
        }
    }
}

function transformSecCardState(card, isActive) {
    card.setAttribute('data-is-active', isActive ? '1' : '0');
    const badge = card.querySelector('.sec-badge-pos');
    const btnActivate = card.querySelector('.btn-activate-card');
    const activeControls = card.querySelector('.sec-active-controls');

    if (badge) badge.style.display = isActive ? 'inline-block' : 'none';
    if (btnActivate) btnActivate.style.display = isActive ? 'none' : 'inline-flex';
    if (activeControls) activeControls.style.display = isActive ? 'inline-flex' : 'none';
}

function updateAdminSubnav(activeTypes) {
    document.querySelectorAll('.admin-subnav-link[data-sec-type]').forEach(btn => {
        const type = btn.getAttribute('data-sec-type');
        if (activeTypes.includes(type)) {
            btn.style.display = 'inline-flex';
        } else {
            btn.style.display = 'none';
            // Falls Redakteur gerade auf dem deaktivierten Tab stand, sicher zu tab-texts wechseln
            if (btn.classList.contains('active')) {
                switchTab('tab-texts');
            }
        }
    });
}

function syncAndSavePool() {
    const activeCards = Array.from(document.querySelectorAll('#dropzone-active .sec-card'));
    const poolCards = Array.from(document.querySelectorAll('#dropzone-pool .sec-card'));

    const activeIds = activeCards.map(c => c.getAttribute('data-sec-id'));
    const inactiveIds = poolCards.map(c => c.getAttribute('data-sec-id'));

    // Position-Badges aktualisieren (#1, #2, ...)
    activeCards.forEach((c, idx) => {
        const posEl = c.querySelector('.sec-badge-pos');
        if (posEl) posEl.textContent = '#' + (idx + 1);
    });

    // Counts aktualisieren
    const activeCount = activeCards.length;
    const poolCount = poolCards.length;

    const elActiveBadge = document.getElementById('active-sec-badge-count');
    const elPoolBadge = document.getElementById('pool-sec-badge-count');
    const elActiveLabel = document.getElementById('active-count-label');
    const elPoolLabel = document.getElementById('pool-count-label');

    if (elActiveBadge) elActiveBadge.textContent = activeCount;
    if (elPoolBadge) elPoolBadge.textContent = poolCount;
    if (elActiveLabel) elActiveLabel.textContent = activeCount + ' aktiv';
    if (elPoolLabel) elPoolLabel.textContent = poolCount + ' im Pool';

    // Empty states ein-/ausblenden
    const poolEmpty = document.getElementById('pool-empty-state');
    const activeEmpty = document.getElementById('active-empty-state');
    if (poolEmpty) poolEmpty.style.display = (poolCount === 0) ? 'flex' : 'none';
    if (activeEmpty) activeEmpty.style.display = (activeCount === 0) ? 'flex' : 'none';

    // AJAX Speichern
    const csrfToken = window.WEAVE_CSRF_TOKEN || (document.querySelector('input[name="csrf_token"]') ? document.querySelector('input[name="csrf_token"]').value : '');
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('action', 'sync_sections_pool');
    formData.append('is_ajax', '1');

    activeIds.forEach(id => formData.append('active_ids[]', id));
    inactiveIds.forEach(id => formData.append('inactive_ids[]', id));

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.active_types) {
            updateAdminSubnav(data.active_types);
            if (typeof showToast === 'function') {
                showToast(data.message || 'Seiten-Aufbau erfolgreich gespeichert!');
            }
        }
    })
    .catch(err => {
        console.warn('Fehler beim AJAX-Speichern des Pools:', err);
    });
}

/**
 * Initialisiert den Admin-Bereich beim Laden (liest Hash aus der URL).
 */
function initAdminListeners() {
    let targetTab = '';

    // 1. URL-Hash #tab-... priorisieren (falls vorhanden)
    if (location.hash && location.hash.length > 1) {
        const hashTab = location.hash.substring(1);
        if (hashTab && document.getElementById(hashTab)) {
            targetTab = hashTab;
        }
    }

    // 2. URL Query-Parameter ?tab= prüfen (nur falls kein Hash da ist)
    if (!targetTab && window.location.search) {
        const urlParams = new URLSearchParams(window.location.search);
        const qTab = urlParams.get('tab');
        if (qTab && document.getElementById(qTab)) {
            targetTab = qTab;
        } else if (urlParams.get('edit_lang') && document.getElementById('tab-texts')) {
            targetTab = 'tab-texts';
        }
    }

    // 3. Serverseitig ermittelten aktiven Tab nutzen
    if (!targetTab && typeof serverActiveTab !== 'undefined' && serverActiveTab && document.getElementById(serverActiveTab)) {
        targetTab = serverActiveTab;
    }

    if (targetTab) {
        switchTab(targetTab, false);
    }

    // Automatisch geöffnetes Plugin-Drawer aufrufen, falls plugin_settings übergeben wurde
    if (window.location.search) {
        const urlParams = new URLSearchParams(window.location.search);
        const pluginSlug = urlParams.get('plugin_settings') || urlParams.get('plugin');
        if (pluginSlug) {
            const drawer = document.getElementById('plugin-settings-drawer-' + pluginSlug);
            if (drawer) {
                drawer.style.display = 'block';
            }
        }
    }

    // Sicherstellen, dass sensible PIN- & Passwortfelder NIEMALS durch Cache oder Browser-Autofill vorbelegt werden
    const clearSensitiveFields = () => {
        ['current_pin', 'new_password', 'new_pin'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.value = '';
            }
        });
    };
    clearSensitiveFields();
    setTimeout(clearSensitiveFields, 150);
    setTimeout(clearSensitiveFields, 450);
}

// Beim Laden des DOMs ausführen
window.addEventListener('DOMContentLoaded', initAdminListeners);
