<?php
/**
 * WeaveCMS – Admin Tab: Häufige Fragen & Antworten (FAQ Akkordeon)
 * 
 * Ermöglicht das Anlegen, Bearbeiten und Sortieren von FAQ-Einträgen
 * für das interaktive Frontend-Akkordeon mit automatischem Google FAQPage Schema.
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */

$faqs = $data['faqs'] ?? [];
$total_faqs = count($faqs);
$faq_title = $data['texts']['faq_title'] ?? 'Häufige Fragen (FAQ)';
$faq_subtitle = $data['texts']['faq_subtitle'] ?? 'Hier findest du Antworten auf die meistgestellten Fragen rund um unsere Workshops, Werkstücke und Pflege.';
?>
        <!-- ──────────────── SECTION FAQS: FRAGEN & ANTWORTEN ──────────────── -->
        <div id="tab-faqs" class="admin-section card" style="padding: 2.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:28px;">quiz</span> <?php echo __a('admin.faqs.title', 'Häufige Fragen (FAQ) verwalten'); ?>
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        <?php echo __a('admin.faqs.desc', 'Erstelle und verwalte häufig gestellte Fragen und Antworten für deine Besucher.'); ?>
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openFaqEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> <?php echo __a('admin.faqs.btn_new', '+ Neue Frage anlegen'); ?>
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.faqs.kpi_total', 'Fragen gesamt'); ?></div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_faqs; ?></div>
                </div>
                <div style="background: rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.3); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: #10b981; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.faqs.kpi_schema', 'Schema.org FAQPage'); ?></div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: #10b981; margin-top:6px;"><?php echo __a('admin.faqs.kpi_schema_active', 'Aktiv (Google Rich Results)'); ?></div>
                </div>
            </div>

            <?php 
                $section_id = 'faq';
                $current_tab = 'tab-faqs';
                $active_langs = $active_langs ?? (class_exists('LanguageManager') ? LanguageManager::getActiveLanguages($data) : ['de']);
                $primary_lang = $primary_lang ?? (class_exists('LanguageManager') ? LanguageManager::getPrimaryLanguage($data) : 'de');
                $supported_langs = $supported_langs ?? (class_exists('LanguageManager') ? LanguageManager::getSupportedLanguages() : []);
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>

            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="faq-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="faq-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">edit_note</span> <?php echo __a('admin.faqs.drawer_new', 'Neue FAQ-Frage anlegen'); ?>
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeFaqEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> <?php echo __a('admin.faqs.drawer_close', 'Schließen'); ?>
                    </button>
                </div>

                <form method="POST" action="" id="faq-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="faq_form_action" value="add_faq">
                    <input type="hidden" name="id" id="faq_id" value="">

                    <!-- Sprach-Reiter im FAQ-Editor -->
                    <?php if (count($active_langs) > 1): ?>
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:1.5rem; flex-wrap:wrap; background:rgba(255,255,255,0.03); padding:10px 14px; border-radius:10px; border:1px solid var(--border-color);">
                            <span style="font-size:0.88rem; font-weight:700; color:var(--text-secondary); margin-right:4px;">
                                <span class="material-icons" style="font-size:16px; vertical-align:middle;">translate</span> <?php echo __a('admin.faqs.lang_content', 'Inhalte auf'); ?>:
                            </span>
                            <button type="button" class="btn btn-primary faq-lang-tab-btn" id="btn-faq-lang-<?php echo $primary_lang; ?>" onclick="switchFaqLangTab('<?php echo $primary_lang; ?>')" style="padding:6px 14px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                                <span><?php echo $supported_langs[$primary_lang]['flag']; ?></span>
                                <span><?php echo htmlspecialchars($supported_langs[$primary_lang]['name']); ?> <?php echo __a('admin.faqs.primary_lang_suffix', '(Hauptsprache)'); ?></span>
                            </button>
                            <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                                <button type="button" class="btn btn-outline faq-lang-tab-btn" id="btn-faq-lang-<?php echo $al; ?>" onclick="switchFaqLangTab('<?php echo $al; ?>')" style="padding:6px 14px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                                    <span><?php echo $supported_langs[$al]['flag']; ?></span>
                                    <span><?php echo htmlspecialchars($supported_langs[$al]['name']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Panel Hauptsprache -->
                    <div id="faq-lang-panel-<?php echo $primary_lang; ?>" class="faq-lang-panel">
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="faq_question" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.faqs.field_question', 'Frage:'); ?></label>
                            <input type="text" id="faq_question" name="question" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.faqs.placeholder_question', 'z. B. Welche Materialien werden verwendet?')); ?>" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="faq_answer" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                                <span><?php echo __a('admin.faqs.field_answer', 'Antwort:'); ?></span>
                                <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);"><?php echo __a('admin.faqs.markdown_hint', 'Unterstützt Markdown-Formatierung (**fett**, *kursiv*, Links, Listen etc.)'); ?></span>
                            </label>
                            <textarea id="faq_answer" name="answer" class="form-control" rows="6" placeholder="<?php echo htmlspecialchars(__a('admin.faqs.placeholder_answer', 'Ausführliche und verständliche Antwort...')); ?>" required style="font-size:0.95rem; line-height:1.6;"></textarea>
                        </div>
                    </div>

                    <!-- Panels für weitere Sprachen -->
                    <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                        <div id="faq-lang-panel-<?php echo $al; ?>" class="faq-lang-panel" style="display:none;">
                            <div style="background:rgba(59, 130, 246, 0.08); border:1px solid #3b82f6; border-radius:10px; padding:10px 14px; margin-bottom:1.25rem; font-size:0.88rem; color:#1e40af; display:flex; align-items:center; gap:8px;">
                                <span class="material-icons" style="font-size:18px; color:#2563eb;">translate</span>
                                <span><?php echo __a('admin.faqs.trans_banner', 'Übersetzungen für aktivierte Sprachen:'); ?> <strong><?php echo $supported_langs[$al]['flag'] . ' ' . htmlspecialchars($supported_langs[$al]['name']); ?></strong>.</span>
                            </div>
                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label for="faq_trans_<?php echo $al; ?>_question" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.faqs.field_question_trans', 'Frage ({lang}):', null, ['lang' => htmlspecialchars($supported_langs[$al]['name'])]); ?></label>
                                <input type="text" id="faq_trans_<?php echo $al; ?>_question" name="trans_<?php echo $al; ?>_question" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.faqs.field_question_trans', 'Frage ({lang}):', null, ['lang' => htmlspecialchars($supported_langs[$al]['name'])])); ?>">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="faq_trans_<?php echo $al; ?>_answer" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.faqs.field_answer_trans', 'Antwort ({lang}):', null, ['lang' => htmlspecialchars($supported_langs[$al]['name'])]); ?></label>
                                <textarea id="faq_trans_<?php echo $al; ?>_answer" name="trans_<?php echo $al; ?>_answer" class="form-control" rows="6" placeholder="<?php echo htmlspecialchars(__a('admin.faqs.field_answer_trans', 'Antwort ({lang}):', null, ['lang' => htmlspecialchars($supported_langs[$al]['name'])])); ?>" style="font-size:0.95rem; line-height:1.6;"></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closeFaqEditor()"><?php echo __a('admin.faqs.btn_cancel', 'Abbrechen'); ?></button>
                        <button type="submit" class="btn btn-primary" id="faq-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> <?php echo __a('admin.faqs.btn_save_new', 'Frage anlegen'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── FAQ-LISTE ──────────────── -->
            <?php if (empty($faqs)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">quiz</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);"><?php echo __a('admin.faqs.empty_title', 'Noch keine FAQs angelegt'); ?></h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;"><?php echo __a('admin.faqs.empty_desc', 'Beantworte die wichtigsten Fragen deiner Besucher vorab und verbessere gleichzeitig dein Google-Suchranking mit Rich Results.'); ?></p>
                    <button type="button" class="btn btn-primary" onclick="openFaqEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> <?php echo __a('admin.faqs.empty_btn', 'Erste Frage anlegen'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php foreach ($faqs as $f): ?>
                        <div class="card" style="padding:1.25rem 1.5rem; border-radius:12px; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                            <div style="display:flex; align-items:flex-start; gap:1rem; flex:1; min-width:280px;">
                                <div style="width:36px; height:36px; border-radius:8px; background:rgba(46,98,72,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px;">
                                    <span class="material-icons" style="font-size:20px;">help_outline</span>
                                </div>
                                <div style="flex:1;">
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                                        <h4 style="margin:0; font-size:1.02rem; color:var(--text-primary);">
                                            <?php echo htmlspecialchars($f['question'] ?? 'Frage'); ?>
                                        </h4>
                                        <?php if (!empty($f['translations']) && is_array($f['translations'])): ?>
                                            <?php foreach ($f['translations'] as $tLang => $tVals): ?>
                                                <?php if (!empty($tVals['question']) && isset($supported_langs[$tLang])): ?>
                                                    <span style="font-size:0.75rem; background:rgba(59,130,246,0.1); color:#2563eb; padding:1px 6px; border-radius:6px; font-weight:700;" title="Übersetzt auf <?php echo htmlspecialchars($supported_langs[$tLang]['name']); ?>">
                                                        <?php echo $supported_langs[$tLang]['flag'] . ' ' . strtoupper($tLang); ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.88rem; color:var(--text-secondary); line-height:1.4; max-height:48px; overflow:hidden; text-overflow:ellipsis;">
                                        <?php echo htmlspecialchars(mb_strimwidth($f['answer'] ?? '', 0, 160, '...')); ?>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px;">
                                <button type="button" class="btn btn-secondary" onclick="editFaq(<?php echo htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> <?php echo __a('admin.faqs.btn_edit', 'Bearbeiten'); ?>
                                </button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(__a('admin.faqs.confirm_delete', 'Möchtest du diese Frage wirklich löschen?'), ENT_QUOTES, 'UTF-8'); ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_faq">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($f['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="<?php echo htmlspecialchars(__a('admin.faqs.btn_delete_title', 'Frage löschen')); ?>">
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
        const FAQ_ACTIVE_LANGS = <?php echo json_encode($active_langs); ?>;
        const FAQ_PRIMARY_LANG = <?php echo json_encode($primary_lang); ?>;

        function switchFaqLangTab(lang) {
            document.querySelectorAll('.faq-lang-panel').forEach(function(p) { p.style.display = 'none'; });
            const p = document.getElementById('faq-lang-panel-' + lang);
            if (p) p.style.display = 'block';

            document.querySelectorAll('.faq-lang-tab-btn').forEach(function(b) {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline');
            });
            const b = document.getElementById('btn-faq-lang-' + lang);
            if (b) {
                b.classList.remove('btn-outline');
                b.classList.add('btn-primary');
            }
        }

        function openFaqEditor() {
            document.getElementById('faq-editor-box').style.display = 'block';
            document.getElementById('faq-editor-title').innerHTML = '<span class="material-icons">add_circle</span> ' + <?php echo json_encode(__a('admin.faqs.drawer_new', 'Neue FAQ-Frage anlegen')); ?>;
            document.getElementById('faq_form_action').value = 'add_faq';
            document.getElementById('faq_id').value = '';
            document.getElementById('faq_question').value = '';
            document.getElementById('faq_answer').value = '';

            FAQ_ACTIVE_LANGS.forEach(function(lang) {
                if (lang === FAQ_PRIMARY_LANG) return;
                const fQ = document.getElementById('faq_trans_' + lang + '_question');
                if (fQ) fQ.value = '';
                const fA = document.getElementById('faq_trans_' + lang + '_answer');
                if (fA) fA.value = '';
            });

            switchFaqLangTab(FAQ_PRIMARY_LANG);
            document.getElementById('faq-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> ' + <?php echo json_encode(__a('admin.faqs.btn_save_new', 'Frage anlegen')); ?>;
            document.getElementById('faq_question').focus();
        }

        function closeFaqEditor() {
            document.getElementById('faq-editor-box').style.display = 'none';
        }

        function editFaq(faqData) {
            document.getElementById('faq-editor-box').style.display = 'block';
            document.getElementById('faq-editor-title').innerHTML = '<span class="material-icons">edit</span> ' + <?php echo json_encode(__a('admin.faqs.drawer_edit', 'FAQ-Frage bearbeiten')); ?>;
            document.getElementById('faq_form_action').value = 'edit_faq';
            document.getElementById('faq_id').value = faqData.id || '';
            document.getElementById('faq_question').value = faqData.question || '';
            document.getElementById('faq_answer').value = faqData.answer || '';

            const trans = faqData.translations || {};
            FAQ_ACTIVE_LANGS.forEach(function(lang) {
                if (lang === FAQ_PRIMARY_LANG) return;
                const fQ = document.getElementById('faq_trans_' + lang + '_question');
                if (fQ) fQ.value = (trans[lang] && trans[lang].question) ? trans[lang].question : '';
                const fA = document.getElementById('faq_trans_' + lang + '_answer');
                if (fA) fA.value = (trans[lang] && trans[lang].answer) ? trans[lang].answer : '';
            });

            switchFaqLangTab(FAQ_PRIMARY_LANG);
            document.getElementById('faq-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> ' + <?php echo json_encode(__a('admin.faqs.btn_save_changes', 'Änderungen speichern')); ?>;
            document.getElementById('faq-editor-box').scrollIntoView({ behavior: 'smooth' });
        }
        </script>

