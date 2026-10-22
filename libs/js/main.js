/**
 * ============================================================================
 * Frontend JavaScript: Maxima Musterfrau - Atelier & Workshops
 * Interaktive Funktionen für Galerie, Lightbox, Workshopanfragen und Menüs
 * ============================================================================
 */

// Globale Variablen für Galerie und Lightbox
let galleryItems = [];
let activeLightboxIdx = 0;
let activeGalleryCategory = 'all';

// Variablen für Spamschutz (Option 1: Natur-Icon-Klick & Option 2: Honeypot + Zeitmessung)
let spamFormInitTime = Date.now();
let currentTargetSpamIcon = null;
let selectedSpamIconId = null;
let configuredSpamIcons = null;

const SPAM_ICONS_POOL = [
    { id: 'basket', symbol: '🧺', name: 'Weidenkorb', prompt: 'den Weidenkorb 🧺' },
    { id: 'branch', symbol: '🌿', name: 'Weidenzweig', prompt: 'den Weidenzweig 🌿' },
    { id: 'paw', symbol: '🐾', name: 'Tatzen', prompt: 'die Tatzen 🐾' },
    { id: 'sun', symbol: '☀️', name: 'Sonne', prompt: 'die Sonne ☀️' },
    { id: 'moon', symbol: '🌙', name: 'Mond', prompt: 'den Mond 🌙' },
    { id: 'star', symbol: '⭐', name: 'Stern', prompt: 'den Stern ⭐' },
    { id: 'wood', symbol: '🪵', name: 'Ast', prompt: 'den Ast 🪵' },
    { id: 'stone', symbol: '🪨', name: 'Stein', prompt: 'den Stein 🪨' },
    { id: 'hazelnut', symbol: '🌰', name: 'Haselnuss', prompt: 'die Haselnuss 🌰' },
    { id: 'flower_pink', symbol: '🌸', name: 'Rosa Blüte', prompt: 'die rosa Blüte 🌸' },
    { id: 'flower_yellow', symbol: '🌻', name: 'Sonnenblume', prompt: 'die Sonnenblume 🌻' },
    { id: 'flower_tulip', symbol: '🌷', name: 'Tulpe', prompt: 'die Tulpe 🌷' }
];

// Server-Konfiguration direkt beim Laden übernehmen falls bereits im Window definiert
if (typeof window !== 'undefined' && Array.isArray(window.SERVER_SPAM_ICONS) && window.SERVER_SPAM_ICONS.length >= 4) {
    configuredSpamIcons = window.SERVER_SPAM_ICONS;
}
if (typeof window !== 'undefined' && Array.isArray(window.SERVER_GALLERY_ITEMS)) {
    galleryItems = window.SERVER_GALLERY_ITEMS;
}

/**
 * Initialisiert die 4 im Adminbereich ausgewählten Spamschutz-Icons.
 * @param {Array} icons - Liste der 4 konfigurierten Symbole
 */
function initSpamIcons(icons) {
    if (Array.isArray(icons) && icons.length >= 4) {
        configuredSpamIcons = icons;
        // Falls das DOM bereits existiert, Spamschutz mit den konfigurierten Icons aktualisieren
        const grid = document.getElementById('spam-icon-grid');
        if (grid) {
            generateSpamProtection();
        }
    }
}

/**
 * Initialisiert die Galeriedaten aus dem PHP-Backend.
 * @param {Array} items - Liste aller Galerie-Elemente
 */
function initGalleryData(items) {
    galleryItems = items || [];
}

/**
 * Ermittelt die Indizes der aktuell sichtbaren Galerie-Elemente basierend auf der aktiven Filter-Kategorie.
 * @returns {Array<number>} Array von Indizes sichtbarer Elemente
 */
function getVisibleIndices() {
    if (activeGalleryCategory === 'all') {
        return galleryItems.map((_, i) => i);
    }
    const list = [];
    galleryItems.forEach((item, idx) => {
        if (item.category === activeGalleryCategory) {
            list.push(idx);
        }
    });
    return list.length > 0 ? list : galleryItems.map((_, i) => i);
}

/**
 * Öffnet die Lightbox-Vollbildansicht für das Bild am übergebenen Index.
 * @param {number} idx - Index des anzuzeigenden Bildes
 */
function openLightbox(idx) {
    if (!galleryItems || !galleryItems[idx]) return;
    activeLightboxIdx = idx;
    updateLightboxContent();
    const modal = document.getElementById('gallery-lightbox');
    if (modal) {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Schließt die Lightbox und stellt den normalen Seiten-Scroll wieder her.
 */
function closeLightbox() {
    const modal = document.getElementById('gallery-lightbox');
    if (modal) {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
}

/**
 * Schaltet in der Lightbox um `dir` Schritte vor oder zurück (+1 oder -1).
 * Berücksichtigt dabei nur die aktuell gefilterten Bilder.
 * @param {number} dir - Schrittweite (+1 = vorwärts, -1 = rückwärts)
 */
function changeLightbox(dir) {
    const visible = getVisibleIndices();
    if (visible.length === 0) return;
    let currentPos = visible.indexOf(activeLightboxIdx);
    if (currentPos === -1) currentPos = 0;
    const nextPos = (currentPos + dir + visible.length) % visible.length;
    activeLightboxIdx = visible[nextPos];
    updateLightboxContent();
}

/**
 * Aktualisiert Bild, Titel, Kategorie, Beschreibung und Seitenzähler der Lightbox.
 */
function updateLightboxContent() {
    const item = galleryItems[activeLightboxIdx];
    if (!item) return;
    
    const img = document.getElementById('lightbox-img');
    if (img) {
        img.src = item.image;
        img.alt = item.title;
    }
    
    const titleEl = document.getElementById('lightbox-title');
    if (titleEl) titleEl.textContent = item.title;
    
    const catEl = document.getElementById('lightbox-category');
    if (catEl) catEl.textContent = item.category;
    
    const descEl = document.getElementById('lightbox-desc');
    if (descEl) descEl.textContent = item.description || '';
    
    const visible = getVisibleIndices();
    const counterEl = document.getElementById('lightbox-counter');
    if (counterEl) {
        const currentPos = visible.indexOf(activeLightboxIdx);
        counterEl.textContent = `${(currentPos >= 0 ? currentPos : 0) + 1} / ${visible.length}`;
    }
}

/**
 * Filtert die Galerie nach der gewählten Kategorie.
 * @param {string} category - Kategorie-Name ('all' für alle)
 * @param {HTMLElement} [btnEl] - Der geklickte Filter-Button
 */
function filterGallery(category, btnEl) {
    activeGalleryCategory = category;
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    
    const activeBtn = btnEl || (window.event && window.event.currentTarget) || (window.event && window.event.target);
    if (activeBtn && activeBtn.classList) {
        activeBtn.classList.add('active');
    }

    document.querySelectorAll('.gallery-card').forEach(card => {
        const match = (category === 'all' || card.getAttribute('data-category') === category);
        if (match) {
            card.classList.remove('is-hidden');
        } else {
            card.classList.add('is-hidden');
        }
    });
}

/**
 * Übernimmt Kursdaten in das Kontakt-Formular und öffnet das Slide-in-Menü.
 * @param {string} courseTitle - Titel des Flechtkurses
 * @param {string} courseDate - Datum des Kurses
 * @param {string} courseTime - Uhrzeit des Kurses
 * @param {string} courseLocation - Veranstaltungsort
 */
function requestCourse(courseTitle, courseDate, courseTime, courseLocation) {
    // Kurs-Referenz für die Archivierung im Anfragen-Postfach merken
    window.CURRENT_COURSE_REF = `${courseTitle} (${courseDate}${courseTime ? ', ' + courseTime : ''})`;
    const toggle = document.getElementById('menu-toggle');
    if (toggle) toggle.checked = true;
    
    // Statusbox leeren und Spamschutz frisch initialisieren
    const statusBox = document.getElementById('contact-status-box');
    if (statusBox) {
        statusBox.style.display = 'none';
        statusBox.innerHTML = '';
    }
    spamFormInitTime = Date.now();
    generateSpamProtection();

    const msgArea = document.getElementById('req-msg');
    if (msgArea) {
        const template = (typeof window !== 'undefined' && window.COURSE_REQUEST_TEMPLATE)
            ? window.COURSE_REQUEST_TEMPLATE
            : `Hallo Maxima,\n\nich interessiere mich für deinen Workshop "{kurs}" am {datum} ({uhrzeit}) in {ort} und möchte fragen, ob noch Plätze frei sind.\n\nViele Grüße`;

        msgArea.value = template
            .replace(/\{kurs\}/gi, courseTitle)
            .replace(/\{datum\}/gi, courseDate)
            .replace(/\{uhrzeit\}/gi, courseTime)
            .replace(/\{ort\}/gi, courseLocation);
    }

    const nameInput = document.getElementById('req-name');
    if (nameInput && !nameInput.value.trim()) {
        nameInput.focus();
    } else if (msgArea) {
        msgArea.focus();
    }
}

/**
 * Teilt einen Workshop per Web Share API oder kopiert den Link in die Zwischenablage.
 * @param {string} title - Kurstitel
 * @param {string} date - Kursdatum
 * @param {string} link - Direkter Kurslink
 */
function shareCourse(title, date, link) {
    if (navigator.share) {
        navigator.share({
            title: `Workshop: ${title}`,
            text: `Workshop bei Maxima: ${title} am ${date}. Jetzt ansehen:`,
            url: link
        }).catch(() => {});
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(link).then(() => {
            showToast('Kurs-Einladungslink in Zwischenablage kopiert!');
        }).catch(() => {
            prompt('Kurslink zum Teilen:', link);
        });
    } else {
        prompt('Kurslink zum Teilen:', link);
    }
}

/**
 * Zeigt eine flüchtige Erfolgsnachricht (Toast) am unteren Bildschirmrand an.
 * @param {string} msg - Anzuzeigende Nachricht
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
 * Schließt das mobile Ausklapp-Menü.
 */
function closeMobileMenu() {
    const toggle = document.getElementById('menu-toggle-mobile');
    if (toggle) toggle.checked = false;
}

/**
 * Schließt das Mobilmenü und öffnet nach kurzer Verzögerung das Kontaktmenü.
 */
function closeMobileMenuAndOpenContact() {
    closeMobileMenu();
    setTimeout(() => {
        const toggle = document.getElementById('menu-toggle');
        if (toggle) toggle.checked = true;
    }, 300);
}

/**
 * Schließt das Mobilmenü und öffnet nach kurzer Verzögerung das Impressum-Menü.
 */
function closeMobileMenuAndOpenImpressum() {
    closeMobileMenu();
    setTimeout(() => {
        const toggle = document.getElementById('menu-toggle-impressum');
        if (toggle) toggle.checked = true;
    }, 300);
}

/**
 * Initialisiert den Natur- & Flecht-Icon-Spamschutz (Option 1) und setzt das Zeitmessungs-Gate (Option 2).
 */
function generateSpamProtection() {
    spamFormInitTime = Date.now();
    selectedSpamIconId = null;

    const label = document.getElementById('spam-protection-label');
    const grid = document.getElementById('spam-icon-grid');
    const feedback = document.getElementById('spam-feedback');

    if (feedback) {
        feedback.style.display = 'none';
        feedback.innerText = '';
    }

    if (!grid) return;

    // Die 4 im Adminbereich konfigurierten Icons verwenden (in zufälliger Reihenfolge anordnen)
    const serverIcons = (typeof window !== 'undefined' && Array.isArray(window.SERVER_SPAM_ICONS) && window.SERVER_SPAM_ICONS.length >= 4)
        ? window.SERVER_SPAM_ICONS
        : null;

    const activePool = (configuredSpamIcons && configuredSpamIcons.length >= 4)
        ? configuredSpamIcons.slice(0, 4)
        : (serverIcons ? serverIcons.slice(0, 4) : SPAM_ICONS_POOL.slice(0, 4));

    const selectedFour = [...activePool].sort(() => 0.5 - Math.random());

    // 1 zufälliges Icon aus den 4 als Zielaufgabe definieren
    currentTargetSpamIcon = selectedFour[Math.floor(Math.random() * selectedFour.length)];

    if (label) {
        label.innerHTML = `Sicherheitsprüfung: Klicke auf <strong>${currentTargetSpamIcon.prompt}</strong>`;
    }

    // Grid mit 4 Buttons befüllen
    grid.innerHTML = '';
    selectedFour.forEach(icon => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'spam-icon-btn';
        btn.title = icon.name;
        btn.setAttribute('data-icon-id', icon.id);
        btn.innerHTML = `
            <span class="spam-icon-symbol">${icon.symbol}</span>
            <span class="spam-icon-label">${icon.name}</span>
        `;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            selectSpamIcon(icon.id, this);
        });
        grid.appendChild(btn);
    });
}

/**
 * Registriert die Auswahl eines Spamschutz-Icons durch den Besucher.
 * @param {string} iconId - ID des geklickten Icons
 * @param {HTMLElement} btnEl - Das geklickte Button-Element
 */
function selectSpamIcon(iconId, btnEl) {
    selectedSpamIconId = iconId;
    const hiddenSelected = document.getElementById('req-selected-icon');
    if (hiddenSelected) hiddenSelected.value = iconId;

    const grid = document.getElementById('spam-icon-grid');
    if (grid) {
        grid.querySelectorAll('.spam-icon-btn').forEach(b => b.classList.remove('selected'));
    }
    if (btnEl) {
        btnEl.classList.add('selected');
    }
    const feedback = document.getElementById('spam-feedback');
    if (feedback) {
        feedback.style.display = 'none';
        feedback.innerText = '';
    }
}

/**
 * Erzeugt eine mailto-URL mit konfigurierbarer Anrede, Betreff und strukturierter Kontaktsignatur.
 * @param {string} recipient - Empfänger-E-Mail
 * @param {string} name - Absendername
 * @param {string} email - Absender-E-Mail
 * @param {string} phone - Telefonnummer
 * @param {string} message - Freitext-Nachricht
 * @returns {string} - Vollständige mailto:-URL
 */
function buildMailtoUrl(recipient, name, email, phone, message) {
    const greeting = (typeof window !== 'undefined' && window.CONTACT_MAIL_GREETING)
        ? window.CONTACT_MAIL_GREETING.trim()
        : 'Hallo Maxima,';

    let content = (message || '').trim();
    // Voranstellen der Anrede, falls noch nicht im Freitext vorhanden
    if (greeting && !content.toLowerCase().startsWith(greeting.toLowerCase())) {
        content = `${greeting}\n\n${content}`;
    }
    content += `\n\nViele Grüße\n${name}\nE-Mail: ${email}\nTelefon: ${phone || 'Keine Angabe'}`;

    const mailtoSubject = encodeURIComponent(`Anfrage über Website von ${name}`);
    const mailtoBody = encodeURIComponent(content);
    return `mailto:${recipient}?subject=${mailtoSubject}&body=${mailtoBody}`;
}

/**
 * Prüft Honeypot, Zeitmessung und Icon-Auswahl, und versendet die Anfrage direkt über den PHP-Server-Mailer.
 * @param {Event} e - Submit-Event des Formulars
 * @param {string} [contactEmail] - Empfänger-E-Mail-Adresse
 */
function sendRequestEmail(e, contactEmail) {
    e.preventDefault();

    const statusBox = document.getElementById('contact-status-box');
    const feedback = document.getElementById('spam-feedback');
    const submitBtn = document.getElementById('contact-submit-btn');
    const submitText = document.getElementById('contact-submit-text');

    if (statusBox) {
        statusBox.style.display = 'none';
        statusBox.innerHTML = '';
    }

    // 1. Honeypot-Prüfung: Unsichtbares Feld für Spam-Bots
    const honeypot = document.getElementById('req-website');
    if (honeypot && honeypot.value.trim() !== '') {
        console.warn('Spam-Bot abgewiesen (Honeypot ausgelöst).');
        return; // Stillschweigend abbrechen
    }

    // 2. Zeitmessung (Time-Gate): Verhindert automatisierte Millisekunden-Submissions
    const elapsedSeconds = (Date.now() - spamFormInitTime) / 1000;
    if (elapsedSeconds < 1.0) {
        alert('Bitte nimm dir einen kurzen Moment Zeit zum Ausfüllen der Nachricht.');
        return;
    }

    // 3. Multi-Captcha Prüfung je nach Modus (Phase 16)
    const captchaModeEl = document.getElementById('req-captcha-mode');
    const captchaMode = captchaModeEl ? captchaModeEl.value : 'icons';

    let mathAnswer = '';
    let mathHash = '';
    let canvasAnswer = '';
    let canvasHash = '';

    if (captchaMode === 'icons') {
        if (!selectedSpamIconId) {
            const promptText = currentTargetSpamIcon ? currentTargetSpamIcon.prompt : 'das geforderte Symbol';
            if (feedback) {
                feedback.innerText = `Bitte klicke oben kurz zur Bestätigung auf ${promptText}.`;
                feedback.style.display = 'block';
            } else {
                alert(`Bitte bestätige den Spamschutz durch Klick auf ${promptText}.`);
            }
            return;
        }

        if (!currentTargetSpamIcon || selectedSpamIconId !== currentTargetSpamIcon.id) {
            const promptText = currentTargetSpamIcon ? currentTargetSpamIcon.prompt : 'das richtige Symbol';
            if (feedback) {
                feedback.innerText = `Das war leider nicht richtig. Bitte klicke auf ${promptText}.`;
                feedback.style.display = 'block';
            } else {
                alert(`Das war leider nicht richtig. Bitte klicke auf ${promptText}.`);
            }
            generateSpamProtection();
            return;
        }
    } else if (captchaMode === 'math') {
        const mathInput = document.getElementById('req-math-answer');
        mathAnswer = mathInput ? mathInput.value.trim() : '';
        const mathHashInput = document.getElementById('req-math-hash');
        mathHash = mathHashInput ? mathHashInput.value.trim() : '';
        if (!mathAnswer) {
            if (feedback) {
                feedback.innerText = 'Bitte löse die Rechenaufgabe zur Sicherheitsbestätigung.';
                feedback.style.display = 'block';
            } else {
                alert('Bitte löse die Rechenaufgabe.');
            }
            if (mathInput) mathInput.focus();
            return;
        }
    } else if (captchaMode === 'canvas') {
        const canvasInput = document.getElementById('req-canvas-answer');
        canvasAnswer = canvasInput ? canvasInput.value.trim() : '';
        const canvasHashInput = document.getElementById('req-canvas-hash');
        canvasHash = canvasHashInput ? canvasHashInput.value.trim() : '';
        if (!canvasAnswer || canvasAnswer.length !== 5) {
            if (feedback) {
                feedback.innerText = 'Bitte gib den 5-stelligen Code aus dem Sicherheitsbild ein.';
                feedback.style.display = 'block';
            } else {
                alert('Bitte gib den 5-stelligen Code ein.');
            }
            if (canvasInput) canvasInput.focus();
            return;
        }
    }

    // 4. Formulareingaben auslesen & validieren
    const nameInput = document.getElementById('req-name');
    const emailInput = document.getElementById('req-email');
    const phoneInput = document.getElementById('req-phone');
    const msgInput = document.getElementById('req-msg');

    const name = nameInput ? nameInput.value.trim() : '';
    const email = emailInput ? emailInput.value.trim() : '';
    const phone = phoneInput ? phoneInput.value.trim() : '';
    const message = msgInput ? msgInput.value.trim() : '';

    if (!name) {
        alert('Bitte gib deinen Namen ein.');
        if (nameInput) nameInput.focus();
        return;
    }

    if (!email || !email.includes('@') || !email.includes('.')) {
        alert('Bitte gib eine gültige E-Mail-Adresse für die Antwort ein.');
        if (emailInput) emailInput.focus();
        return;
    }

    if (!message || message.length < 5) {
        alert('Bitte gib eine Nachricht ein (mindestens 5 Zeichen).');
        if (msgInput) msgInput.focus();
        return;
    }

    // 5. Falls SMTP in den Einstellungen nicht aktiviert ist: Direkt mailto öffnen (kein PHP-Server-Mailer)
    const isSmtpEnabled = (typeof window !== 'undefined' && window.SERVER_SMTP_ENABLED === true);
    const recipient = contactEmail || 'kontakt@maxima-musterfrau.de';
    const mailtoUrl = buildMailtoUrl(recipient, name, email, phone, message);

    if (!isSmtpEnabled) {
        // Anfrage zusätzlich lokal im Admin-Postfach archivieren (ohne Mailversand)
        const archiveData = new FormData();
        archiveData.append('name', name);
        archiveData.append('email', email);
        archiveData.append('phone', phone);
        archiveData.append('message', message);
        if (captchaMode === 'icons') {
            archiveData.append('selected_icon', selectedSpamIconId);
            archiveData.append('target_icon', currentTargetSpamIcon ? currentTargetSpamIcon.id : '');
        } else if (captchaMode === 'math') {
            archiveData.append('captcha_math_answer', mathAnswer);
            archiveData.append('captcha_math_hash', mathHash);
        } else if (captchaMode === 'canvas') {
            archiveData.append('captcha_canvas_answer', canvasAnswer);
            archiveData.append('captcha_canvas_hash', canvasHash);
        }
        archiveData.append('website', honeypot ? honeypot.value : '');
        archiveData.append('course_ref', window.CURRENT_COURSE_REF || '');
        archiveData.append('archive_only', '1');
        try {
            fetch('send_mail.php', { method: 'POST', body: archiveData, keepalive: true }).catch(() => {});
        } catch (e) { /* Archivierung ist optional, Mailto hat Vorrang */ }

        // Mailto öffnen
        window.location.href = mailtoUrl;

        // Feedback in Statusbox anzeigen
        if (statusBox) {
            statusBox.style.display = 'block';
            statusBox.style.background = 'rgba(46, 98, 72, 0.12)';
            statusBox.style.border = '1px solid var(--primary)';
            statusBox.style.color = 'var(--primary)';
            statusBox.innerHTML = `
                <div style="display:flex; align-items:flex-start; gap:8px;">
                    <span class="material-icons" style="font-size:20px; color:var(--primary);">mark_email_read</span>
                    <div>
                        <strong>E-Mail-Programm geöffnet!</strong><br>
                        Deine Nachricht wurde an dein E-Mail-Programm übergeben.<br>
                        <a href="${mailtoUrl}" style="display:inline-flex; align-items:center; gap:4px; margin-top:8px; color:var(--primary); font-weight:600; text-decoration:underline;">
                            <span class="material-icons" style="font-size:15px;">open_in_new</span> Falls sich nichts geöffnet hat: Hier klicken zum Senden
                        </a>
                    </div>
                </div>
            `;
        }

        // Spamschutz frisch würfeln
        if (captchaMode === 'icons') {
            generateSpamProtection();
        }
        return;
    }

    // 6. SMTP ist aktiv: Button in Ladezustand versetzen und per AJAX an send_mail.php senden
    if (submitBtn) submitBtn.disabled = true;
    if (submitText) submitText.innerText = 'Wird gesendet...';

    const formData = new FormData();
    formData.append('name', name);
    formData.append('email', email);
    formData.append('phone', phone);
    formData.append('message', message);
    if (captchaMode === 'icons') {
        formData.append('selected_icon', selectedSpamIconId);
        formData.append('target_icon', currentTargetSpamIcon ? currentTargetSpamIcon.id : '');
    } else if (captchaMode === 'math') {
        formData.append('captcha_math_answer', mathAnswer);
        formData.append('captcha_math_hash', mathHash);
    } else if (captchaMode === 'canvas') {
        formData.append('captcha_canvas_answer', canvasAnswer);
        formData.append('captcha_canvas_hash', canvasHash);
    }
    formData.append('website', honeypot ? honeypot.value : '');
    formData.append('course_ref', window.CURRENT_COURSE_REF || '');

    fetch('send_mail.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Erfolgsmeldung anzeigen
            if (statusBox) {
                statusBox.style.display = 'block';
                statusBox.style.background = 'rgba(46, 98, 72, 0.12)';
                statusBox.style.border = '1px solid var(--primary)';
                statusBox.style.color = 'var(--primary)';
                statusBox.innerHTML = `
                    <div style="display:flex; align-items:flex-start; gap:8px;">
                        <span class="material-icons" style="font-size:20px; color:var(--primary);">check_circle</span>
                        <div><strong>Vielen Dank!</strong><br>${data.message}</div>
                    </div>
                `;
            }

            // Felder leeren
            if (nameInput) nameInput.value = '';
            if (emailInput) emailInput.value = '';
            if (phoneInput) phoneInput.value = '';
            if (msgInput) msgInput.value = '';

            showToast('Nachricht erfolgreich gesendet!');
        } else {
            // Fehlermeldung anzeigen
            if (statusBox) {
                statusBox.style.display = 'block';
                statusBox.style.background = 'rgba(239, 68, 68, 0.1)';
                statusBox.style.border = '1px solid #f87171';
                statusBox.style.color = '#dc2626';
                
                let errorHtml = `
                    <div style="display:flex; align-items:flex-start; gap:8px;">
                        <span class="material-icons" style="font-size:20px; color:#dc2626;">error_outline</span>
                        <div><strong>Versandhinweis:</strong><br>${data.error}</div>
                    </div>
                `;

                if (data.fallback_mailto) {
                    errorHtml += `
                        <div style="margin-top:10px; padding-top:8px; border-top:1px dashed rgba(220,38,38,0.25);">
                            <a href="${data.fallback_mailto}" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px; font-size:0.8rem; padding:6px 12px; color:var(--text-primary); border-color:var(--border-color); text-decoration:none;">
                                <span class="material-icons" style="font-size:15px;">mail</span> Alternativ im Mailprogramm öffnen
                            </a>
                        </div>
                    `;
                }

                statusBox.innerHTML = errorHtml;
            } else {
                alert('Fehler beim Senden: ' + data.error);
            }
        }
    })
    .catch(err => {
        console.error('Mail send error:', err);
        const recipient = contactEmail || 'kontakt@maxima-musterfrau.de';
        const fallbackMailto = buildMailtoUrl(recipient, name, email, phone, message);
        if (statusBox) {
            statusBox.style.display = 'block';
            statusBox.style.background = 'rgba(239, 68, 68, 0.1)';
            statusBox.style.border = '1px solid #f87171';
            statusBox.style.color = '#dc2626';
            statusBox.innerHTML = `
                <div style="display:flex; align-items:flex-start; gap:8px;">
                    <span class="material-icons" style="font-size:20px; color:#dc2626;">wifi_off</span>
                    <div>
                        <strong>Netzwerk- oder Serverfehler:</strong><br>
                        Die Nachricht konnte nicht über den Server gesendet werden.<br>
                        <div style="margin-top:10px; padding-top:8px; border-top:1px dashed rgba(220,38,38,0.25);">
                            <a href="${fallbackMailto}" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px; font-size:0.8rem; padding:6px 12px; color:var(--text-primary); border-color:var(--border-color); text-decoration:none;">
                                <span class="material-icons" style="font-size:15px;">mail</span> Alternativ im Mailprogramm öffnen
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }
    })
    .finally(() => {
        // Button wieder aktivieren & Spamschutz frisch würfeln
        if (submitBtn) submitBtn.disabled = false;
        if (submitText) submitText.innerText = 'Anfrage senden';
        generateSpamProtection();
    });
}

/**
 * Initialisiert Tastatur- und Touch-Events für Lightbox sowie URL-Deep-Links.
 */
function initFrontendListeners() {
    // Tastatur-Navigation (Esc = Schließen, Pfeiltasten = Weiterblättern)
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('gallery-lightbox');
        if (modal && modal.classList.contains('active')) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') changeLightbox(-1);
            if (e.key === 'ArrowRight') changeLightbox(1);
        }
    });

    // Wischgesten (Touch-Swipe) auf Mobilgeräten
    let lbTouchStartX = 0;
    let lbTouchEndX = 0;
    const lightboxModal = document.getElementById('gallery-lightbox');
    if (lightboxModal) {
        lightboxModal.addEventListener('touchstart', function(e) {
            lbTouchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        lightboxModal.addEventListener('touchend', function(e) {
            lbTouchEndX = e.changedTouches[0].screenX;
            const swipeDist = lbTouchEndX - lbTouchStartX;
            if (Math.abs(swipeDist) > 45) {
                if (swipeDist > 0) changeLightbox(-1); // Wisch nach rechts -> vorheriges Bild
                else changeLightbox(1); // Wisch nach links -> nächstes Bild
            }
        }, { passive: true });
    }

    // ──────────────── CANVAS CAPTCHA & ANTI-SCRAPING EMAIL (PHASE 16) ────────────────
    renderContactCanvasCaptcha(false);

    // Beim Laden: Spamschutz initialisieren & ggf. direkt zu per URL übergebenem Kurs scrollen
    generateSpamProtection();

    // Bei Öffnen des Kontaktmenüs: Zeitstempel erneuern und Icons frisch würfeln
    const menuToggle = document.getElementById('menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('change', function() {
            if (this.checked) {
                generateSpamProtection();
                renderContactCanvasCaptcha(false);
            }
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    const courseId = urlParams.get('course');
    if (courseId) {
        const targetCard = document.getElementById('course-' + courseId);
        if (targetCard) {
            setTimeout(() => {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        }
    }
}

/**
 * Zeichnet einen 5-stelligen Zahlencode verzerrt mit Rauschlinien auf ein HTML5 Canvas.
 * @param {HTMLCanvasElement} canvas
 * @param {string} code
 */
function drawDistortedCanvas(canvas, code) {
    if (!canvas || !code) return;
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;

    ctx.clearRect(0, 0, width, height);

    // Hintergrund-Verlauf
    const gradient = ctx.createLinearGradient(0, 0, width, height);
    gradient.addColorStop(0, '#f8f9fa');
    gradient.addColorStop(1, '#e9ecef');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, width, height);

    // Rauschlinien gegen OCR / Text-Scraper
    for (let i = 0; i < 4; i++) {
        ctx.strokeStyle = `rgba(${Math.random() * 80 + 30}, ${Math.random() * 80 + 30}, ${Math.random() * 80 + 30}, 0.25)`;
        ctx.lineWidth = Math.random() * 1.5 + 1;
        ctx.beginPath();
        ctx.moveTo(Math.random() * width, Math.random() * height);
        ctx.lineTo(Math.random() * width, Math.random() * height);
        ctx.stroke();
    }

    // Verzerrte Zeichen zeichnen
    ctx.font = 'bold 20px Arial, sans-serif';
    const spacing = width / (code.length + 1);

    for (let i = 0; i < code.length; i++) {
        ctx.save();
        const x = spacing * (i + 1) + (Math.random() - 0.5) * 5;
        const y = height / 2 + 7 + (Math.random() - 0.5) * 5;
        const rotation = (Math.random() - 0.5) * 0.35;

        ctx.translate(x, y);
        ctx.rotate(rotation);
        ctx.fillStyle = '#2e6248';
        ctx.shadowColor = 'rgba(0,0,0,0.2)';
        ctx.shadowBlur = 2;
        ctx.fillText(code[i], -6, 0);
        ctx.restore();
    }
}

/**
 * Initialisiert das Canvas-Captcha im Kontaktformular.
 */
function renderContactCanvasCaptcha(forceNew) {
    const canvas = document.getElementById('contact-captcha-canvas');
    if (!canvas) return;
    if (forceNew) {
        let code = '';
        for (let i = 0; i < 5; i++) code += Math.floor(Math.random() * 10);
        canvas.setAttribute('data-code', code);
        const hashInput = document.getElementById('req-canvas-hash');
        if (hashInput) hashInput.value = ''; // Server-Fallback
    }
    const code = canvas.getAttribute('data-code');
    drawDistortedCanvas(canvas, code);
}

/**
 * Öffnet das Canvas-Captcha für die E-Mail-Freischaltung im Impressum (Anti-Scraping).
 */
function openImpressumEmailCaptcha() {
    const box = document.getElementById('impressum-email-captcha-box');
    if (box) {
        box.style.display = 'block';
        fetchImpressumCaptcha();
    }
}

/**
 * Lädt einen neuen Canvas-Code vom Server für das Impressum.
 */
function fetchImpressumCaptcha() {
    fetch('api/verify_email_captcha.php?action=get_challenge')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.code) {
                const canvas = document.getElementById('impressum-captcha-canvas');
                if (canvas) {
                    canvas.setAttribute('data-code', data.code);
                    drawDistortedCanvas(canvas, data.code);
                }
            }
        }).catch(() => {});
}

/**
 * Verifiziert den eingegebenen Code und schaltet die E-Mail-Adresse im Impressum frei.
 */
function verifyImpressumCaptcha() {
    const input = document.getElementById('impressum-captcha-input');
    const feedback = document.getElementById('impressum-captcha-feedback');
    const code = input ? input.value.trim() : '';

    if (!code) {
        if (feedback) {
            feedback.innerText = 'Bitte gib den 5-stelligen Code ein.';
            feedback.style.display = 'block';
        }
        return;
    }

    fetch('api/verify_email_captcha.php?action=verify&code=' + encodeURIComponent(code))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.email) {
                const wrapper = document.getElementById('impressum-email-wrapper');
                const box = document.getElementById('impressum-email-captcha-box');
                if (box) box.style.display = 'none';
                if (wrapper) {
                    wrapper.innerHTML = `<a href="mailto:${data.email}" style="color:var(--primary); font-weight:600; text-decoration:underline;">${data.email}</a>`;
                }
            } else {
                if (feedback) {
                    feedback.innerText = data.error || 'Code leider nicht korrekt.';
                    feedback.style.display = 'block';
                }
                fetchImpressumCaptcha();
            }
        }).catch(() => {
            if (feedback) {
                feedback.innerText = 'Verbindungsfehler beim Verifizieren.';
                feedback.style.display = 'block';
            }
        });
}

// Event-Listener beim Laden des DOMs ausführen
window.addEventListener('DOMContentLoaded', initFrontendListeners);
