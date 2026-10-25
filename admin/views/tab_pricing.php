<?php
/**
 * WeaveCMS – Admin Tab: Leistungen & Preise (Pricing Tables)
 * 
 * Ermöglicht das Anlegen und Verwalten von Preistabellen,
 * Leistungspaketen und Angeboten mit Hervorhebungen und Feature-Listen.
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */

$pricing = $data['pricing'] ?? [];
$total_pricing = count($pricing);
?>
        <!-- ──────────────── SECTION PRICING: LEISTUNGEN & PREISE ──────────────── -->
        <div id="tab-pricing" class="admin-section card" style="padding: 2.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:28px;">sell</span> Leistungen &amp; Preise
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        Erstelle transparente Preispakete für deine handgefertigten Produkte, Workshops oder Auftragsarbeiten.
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openPricingEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Neues Angebot / Paket
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Pakete Gesamt</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_pricing; ?></div>
                </div>
            </div>

            <?php 
                $section_id = 'pricing';
                $current_tab = 'tab-pricing';
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>

<?php
$active_langs = LanguageManager::getActiveLanguages($data);
$primary_lang = LanguageManager::getPrimaryLanguage($data);
$all_languages = LanguageManager::getSupportedLanguages();
?>
            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="pricing-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="pricing-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">sell</span> Neues Preispaket anlegen
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closePricingEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> Schließen
                    </button>
                </div>

                <form method="POST" action="" id="pricing-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="pricing_form_action" value="add_pricing_plan">
                    <input type="hidden" name="id" id="pricing_id" value="">

                    <!-- Gemeinsame Parameter (Preis & Hervorhebung) -->
                    <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1rem;">
                        <div class="form-group" style="margin:0;">
                            <label for="pricing_price" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Preis *</label>
                            <input type="text" id="pricing_price" name="price" class="form-control" placeholder="z. B. 79 € oder ab 120 €" required>
                        </div>
                        <div class="form-group" style="display:flex; flex-direction:column; justify-content:center; margin:0;">
                            <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Hervorhebung</label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.95rem; margin-top:6px;">
                                <input type="checkbox" id="pricing_highlighted" name="highlighted" value="1">
                                Als Hauptpaket hervorheben (Farbrand)
                            </label>
                        </div>
                    </div>

                    <?php if (count($active_langs) > 1): ?>
                        <!-- Sprach-Tabs im Editor -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="font-weight:700; color:var(--text-primary); font-size:0.9rem; margin-bottom:0.5rem; display:block;">Inhaltssprachen:</label>
                            <div style="display:flex; gap:6px; border-bottom:1px solid var(--border-color); padding-bottom:4px;" id="pricing-lang-tab-bar">
                                <?php foreach ($active_langs as $idx => $al): 
                                    $is_prim = ($al === $primary_lang);
                                    $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                                ?>
                                    <button type="button" 
                                            class="pricing-lang-tab-btn btn <?php echo $is_prim ? 'btn-primary' : 'btn-secondary'; ?>" 
                                            id="pricing-btn-tab-<?php echo $al; ?>"
                                            onclick="switchPricingLangTab('<?php echo $al; ?>')"
                                            style="padding: 5px 12px; font-size: 0.85rem; display:flex; align-items:center; gap:6px;">
                                        <span><?php echo $lang_info['flag']; ?></span>
                                        <span><?php echo htmlspecialchars($lang_info['name']); ?><?php echo $is_prim ? ' (Hauptsprache)' : ''; ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab-Inhalt: Hauptsprache -->
                    <div id="pricing-lang-content-<?php echo $primary_lang; ?>" class="pricing-lang-content">
                        <div class="grid-3" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                            <div class="form-group">
                                <label for="pricing_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    Paketname * <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span>
                                </label>
                                <input type="text" id="pricing_title" name="title" class="form-control" placeholder="z. B. Einsteiger Workshop" required>
                            </div>
                            <div class="form-group">
                                <label for="pricing_period" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Einheit / Zeitraum</label>
                                <input type="text" id="pricing_period" name="period" class="form-control" placeholder="z. B. / Person oder / Werkstück">
                            </div>
                            <div class="form-group">
                                <label for="pricing_badge" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Badge / Label</label>
                                <input type="text" id="pricing_badge" name="badge" class="form-control" placeholder="z. B. Bestseller oder Beliebt">
                            </div>
                        </div>

                        <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                            <div class="form-group">
                                <label for="pricing_button_text" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Button-Text</label>
                                <input type="text" id="pricing_button_text" name="button_text" class="form-control" value="Jetzt anfragen">
                            </div>
                            <div class="form-group">
                                <label for="pricing_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Kurzbeschreibung</label>
                                <input type="text" id="pricing_desc" name="description" class="form-control" placeholder="z. B. Ideal für alle, die das traditionelle Flechthandwerk kennenlernen möchten.">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="pricing_features" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                                <span>Inklusivleistungen / Merkmale *</span>
                                <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">Ein Merkmal pro Zeile eintragen</span>
                            </label>
                            <textarea id="pricing_features" name="features" class="form-control" rows="5" placeholder="Inklusive aller Naturmaterialien&#10;Eigenes fertiges Werkstück zum Mitnehmen&#10;Werkzeugausleihe inklusive&#10;Getränke &amp; kleine Snacks" required></textarea>
                        </div>
                    </div>

                    <?php foreach ($active_langs as $al): ?>
                        <?php if ($al === $primary_lang) continue; 
                            $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                        ?>
                        <div id="pricing-lang-content-<?php echo $al; ?>" class="pricing-lang-content" style="display:none;">
                            <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; padding: 0.6rem 0.9rem; margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                                ℹ️ Übersetze Paketname, Einheit, Button-Text und Features für Besucher mit Sprache <strong><?php echo $lang_info['flag'] . ' ' . htmlspecialchars($lang_info['name']); ?></strong>. Falls leer, wird die Hauptsprache verwendet.
                            </div>

                            <div class="grid-3" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                                <div class="form-group">
                                    <label for="pricing_trans_<?php echo $al; ?>_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                        Paketname (<?php echo htmlspecialchars($lang_info['name']); ?>)
                                    </label>
                                    <input type="text" id="pricing_trans_<?php echo $al; ?>_title" name="trans_<?php echo $al; ?>_title" class="form-control" placeholder="z. B. Beginner Workshop">
                                </div>
                                <div class="form-group">
                                    <label for="pricing_trans_<?php echo $al; ?>_period" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                        Einheit / Zeitraum (<?php echo htmlspecialchars($lang_info['name']); ?>)
                                    </label>
                                    <input type="text" id="pricing_trans_<?php echo $al; ?>_period" name="trans_<?php echo $al; ?>_period" class="form-control" placeholder="z. B. / Person">
                                </div>
                                <div class="form-group">
                                    <label for="pricing_trans_<?php echo $al; ?>_badge" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                        Badge / Label (<?php echo htmlspecialchars($lang_info['name']); ?>)
                                    </label>
                                    <input type="text" id="pricing_trans_<?php echo $al; ?>_badge" name="trans_<?php echo $al; ?>_badge" class="form-control" placeholder="z. B. Popular">
                                </div>
                            </div>

                            <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                                <div class="form-group">
                                    <label for="pricing_trans_<?php echo $al; ?>_button_text" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                        Button-Text (<?php echo htmlspecialchars($lang_info['name']); ?>)
                                    </label>
                                    <input type="text" id="pricing_trans_<?php echo $al; ?>_button_text" name="trans_<?php echo $al; ?>_button_text" class="form-control" placeholder="z. B. Book now">
                                </div>
                                <div class="form-group">
                                    <label for="pricing_trans_<?php echo $al; ?>_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                        Kurzbeschreibung (<?php echo htmlspecialchars($lang_info['name']); ?>)
                                    </label>
                                    <input type="text" id="pricing_trans_<?php echo $al; ?>_desc" name="trans_<?php echo $al; ?>_description" class="form-control" placeholder="Short description...">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="pricing_trans_<?php echo $al; ?>_features" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                                    <span>Inklusivleistungen / Merkmale (<?php echo htmlspecialchars($lang_info['name']); ?>)</span>
                                    <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">Ein Merkmal pro Zeile</span>
                                </label>
                                <textarea id="pricing_trans_<?php echo $al; ?>_features" name="trans_<?php echo $al; ?>_features" class="form-control" rows="5" placeholder="All natural materials included&#10;Your own finished product..."></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closePricingEditor()">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" id="pricing-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> Paket speichern
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── PREIS-LISTE ──────────────── -->
            <?php if (empty($pricing)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">sell</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);">Noch keine Preis- oder Leistungsangebote angelegt</h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;">Definiere Workshop-Pakete oder Richtpreise für Sonderanfertigungen.</p>
                    <button type="button" class="btn btn-primary" onclick="openPricingEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Erstes Preispaket anlegen
                    </button>
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1.25rem;">
                    <?php foreach ($pricing as $p): ?>
                        <div class="card" style="padding:1.5rem; border-radius:14px; display:flex; flex-direction:column; justify-content:space-between; gap:1rem; <?php echo !empty($p['highlighted']) ? 'border:2px solid var(--primary);' : ''; ?>">
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem; flex-wrap:wrap; gap:6px;">
                                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">
                                            <?php echo htmlspecialchars($p['title'] ?? 'Angebot'); ?>
                                        </h4>
                                        <?php if (count($active_langs) > 1): ?>
                                            <?php foreach ($active_langs as $al): ?>
                                                <?php if ($al === $primary_lang) continue; ?>
                                                <?php if (!empty($p['translations'][$al]['title']) || !empty($p['translations'][$al]['description'])): 
                                                    $li = $all_languages[$al] ?? ['flag' => '🌐'];
                                                ?>
                                                    <span title="Übersetzung für <?php echo strtoupper($al); ?> gepflegt" style="font-size:0.75rem; background:rgba(16,185,129,0.12); color:var(--primary); border:1px solid rgba(16,185,129,0.25); padding:1px 6px; border-radius:4px; display:inline-flex; align-items:center; gap:2px;">
                                                        <?php echo $li['flag']; ?> <?php echo strtoupper($al); ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($p['badge'])): ?>
                                        <span style="font-size:0.72rem; font-weight:700; color:#fff; background:var(--primary); padding:2px 8px; border-radius:999px;">
                                            <?php echo htmlspecialchars($p['badge']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div style="font-size:1.5rem; font-weight:800; color:var(--primary); margin-bottom:0.5rem;">
                                    <?php echo htmlspecialchars($p['price'] ?? ''); ?>
                                    <?php if (!empty($p['period'])): ?>
                                        <span style="font-size:0.85rem; font-weight:normal; color:var(--text-secondary);"><?php echo htmlspecialchars($p['period']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($p['description'])): ?>
                                    <p style="font-size:0.88rem; color:var(--text-secondary); margin-bottom:0.75rem; line-height:1.4;">
                                        <?php echo htmlspecialchars($p['description']); ?>
                                    </p>
                                <?php endif; ?>

                                <?php 
                                    $feats = is_array($p['features'] ?? null) 
                                        ? $p['features'] 
                                        : array_filter(array_map('trim', explode("\n", $p['features'] ?? '')));
                                ?>
                                <?php if (!empty($feats)): ?>
                                    <ul style="list-style:none; padding:0; margin:0; font-size:0.85rem; color:var(--text-primary); display:flex; flex-direction:column; gap:4px;">
                                        <?php foreach (array_slice($feats, 0, 4) as $ft): ?>
                                            <li style="display:flex; align-items:center; gap:6px;">
                                                <span class="material-icons" style="font-size:15px; color:var(--primary);">check</span>
                                                <span><?php echo htmlspecialchars($ft); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (count($feats) > 4): ?>
                                            <li style="color:var(--text-secondary); font-size:0.8rem; margin-top:2px;">+ <?php echo (count($feats) - 4); ?> weitere Punkte</li>
                                        <?php endif; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>

                            <div style="display:flex; justify-content:flex-end; gap:8px; border-top:1px solid var(--border-color); padding-top:0.75rem; margin-top:0.5rem;">
                                <button type="button" class="btn btn-secondary" onclick="editPricing(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> Bearbeiten
                                </button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Möchtest du dieses Preispaket wirklich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_pricing_plan">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="Paket löschen">
                                        <span class="material-icons" style="font-size:16px;">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        const activePricingLangs = <?php echo json_encode($active_langs); ?>;
        const primaryPricingLang = <?php echo json_encode($primary_lang); ?>;

        function switchPricingLangTab(lang) {
            document.querySelectorAll('.pricing-lang-content').forEach(el => el.style.display = 'none');
            const target = document.getElementById('pricing-lang-content-' + lang);
            if (target) target.style.display = 'block';

            document.querySelectorAll('.pricing-lang-tab-btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            const activeBtn = document.getElementById('pricing-btn-tab-' + lang);
            if (activeBtn) {
                activeBtn.classList.remove('btn-secondary');
                activeBtn.classList.add('btn-primary');
            }
        }

        function openPricingEditor() {
            document.getElementById('pricing-editor-box').style.display = 'block';
            document.getElementById('pricing-editor-title').innerHTML = '<span class="material-icons">sell</span> Neues Preispaket anlegen';
            document.getElementById('pricing_form_action').value = 'add_pricing_plan';
            document.getElementById('pricing_id').value = '';
            document.getElementById('pricing_title').value = '';
            document.getElementById('pricing_price').value = '';
            document.getElementById('pricing_period').value = '';
            document.getElementById('pricing_badge').value = '';
            document.getElementById('pricing_button_text').value = 'Jetzt anfragen';
            document.getElementById('pricing_highlighted').checked = false;
            document.getElementById('pricing_desc').value = '';
            document.getElementById('pricing_features').value = '';

            activePricingLangs.forEach(al => {
                if (al !== primaryPricingLang) {
                    const tEl = document.getElementById('pricing_trans_' + al + '_title');
                    const pEl = document.getElementById('pricing_trans_' + al + '_period');
                    const bEl = document.getElementById('pricing_trans_' + al + '_badge');
                    const btnEl = document.getElementById('pricing_trans_' + al + '_button_text');
                    const dEl = document.getElementById('pricing_trans_' + al + '_desc');
                    const fEl = document.getElementById('pricing_trans_' + al + '_features');
                    if (tEl) tEl.value = '';
                    if (pEl) pEl.value = '';
                    if (bEl) bEl.value = '';
                    if (btnEl) btnEl.value = '';
                    if (dEl) dEl.value = '';
                    if (fEl) fEl.value = '';
                }
            });

            switchPricingLangTab(primaryPricingLang);
            document.getElementById('pricing-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Paket anlegen';
            document.getElementById('pricing_title').focus();
        }

        function closePricingEditor() {
            document.getElementById('pricing-editor-box').style.display = 'none';
        }

        function editPricing(pricingData) {
            document.getElementById('pricing-editor-box').style.display = 'block';
            document.getElementById('pricing-editor-title').innerHTML = '<span class="material-icons">edit</span> Preispaket bearbeiten';
            document.getElementById('pricing_form_action').value = 'edit_pricing_plan';
            document.getElementById('pricing_id').value = pricingData.id || '';
            document.getElementById('pricing_title').value = pricingData.title || '';
            document.getElementById('pricing_price').value = pricingData.price || '';
            document.getElementById('pricing_period').value = pricingData.period || '';
            document.getElementById('pricing_badge').value = pricingData.badge || '';
            document.getElementById('pricing_button_text').value = pricingData.button_text || 'Jetzt anfragen';
            document.getElementById('pricing_highlighted').checked = !!pricingData.highlighted;
            document.getElementById('pricing_desc').value = pricingData.description || '';
            
            let feats = pricingData.features || '';
            if (Array.isArray(feats)) {
                feats = feats.join('\n');
            }
            document.getElementById('pricing_features').value = feats;

            const tr = pricingData.translations || {};
            activePricingLangs.forEach(al => {
                if (al !== primaryPricingLang) {
                    const tEl = document.getElementById('pricing_trans_' + al + '_title');
                    const pEl = document.getElementById('pricing_trans_' + al + '_period');
                    const bEl = document.getElementById('pricing_trans_' + al + '_badge');
                    const btnEl = document.getElementById('pricing_trans_' + al + '_button_text');
                    const dEl = document.getElementById('pricing_trans_' + al + '_desc');
                    const fEl = document.getElementById('pricing_trans_' + al + '_features');

                    if (tEl) tEl.value = tr[al]?.title || '';
                    if (pEl) pEl.value = tr[al]?.period || '';
                    if (bEl) bEl.value = tr[al]?.badge || '';
                    if (btnEl) btnEl.value = tr[al]?.button_text || '';
                    if (dEl) dEl.value = tr[al]?.description || '';
                    if (fEl) {
                        const trF = tr[al]?.features;
                        fEl.value = Array.isArray(trF) ? trF.join('\n') : (trF || '');
                    }
                }
            });

            switchPricingLangTab(primaryPricingLang);
            document.getElementById('pricing-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Änderungen speichern';
            document.getElementById('pricing-editor-box').scrollIntoView({ behavior: 'smooth' });
        }
        </script>
