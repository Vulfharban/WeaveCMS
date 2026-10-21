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
                        <span class="material-icons" style="font-size:28px;">quiz</span> Häufige Fragen &amp; Antworten (FAQ)
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        Pflege Fragen und Antworten für dein Akkordeon. Google nutzt diese automatisch für Rich-Snippets in den Suchergebnissen.
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openFaqEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Neue FAQ-Frage
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Fragen Gesamt</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_faqs; ?></div>
                </div>
                <div style="background: rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.3); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: #10b981; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Google Schema.org</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: #10b981; margin-top:6px;">Aktiv (FAQPage)</div>
                </div>
            </div>

            <?php 
                $section_id = 'faq';
                $current_tab = 'tab-faqs';
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>

            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="faq-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="faq-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">edit_note</span> Neue FAQ-Frage anlegen
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeFaqEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> Schließen
                    </button>
                </div>

                <form method="POST" action="" id="faq-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="faq_form_action" value="add_faq">
                    <input type="hidden" name="id" id="faq_id" value="">

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="faq_question" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Frage *</label>
                        <input type="text" id="faq_question" name="question" class="form-control" placeholder="z. B. Muss ich Vorkenntnisse für die Kurse mitbringen?" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="faq_answer" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                            <span>Antwort (Markdown unterstützt) *</span>
                            <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">**Fett**, Absätze, Listen, [Links](url)</span>
                        </label>
                        <textarea id="faq_answer" name="answer" class="form-control" rows="6" placeholder="Formuliere hier deine klare, freundliche Antwort..." required style="font-size:0.95rem; line-height:1.6;"></textarea>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closeFaqEditor()">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" id="faq-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> Frage speichern
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── FAQ-LISTE ──────────────── -->
            <?php if (empty($faqs)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">quiz</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);">Noch keine FAQ-Fragen hinterlegt</h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;">Beantworte typische Fragen zu Werkstücken, Anfahrt, Gutscheinen oder Pflege.</p>
                    <button type="button" class="btn btn-primary" onclick="openFaqEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Erste FAQ-Frage anlegen
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
                                    <h4 style="margin:0 0 6px 0; font-size:1.02rem; color:var(--text-primary);">
                                        <?php echo htmlspecialchars($f['question'] ?? 'Frage'); ?>
                                    </h4>
                                    <div style="font-size:0.88rem; color:var(--text-secondary); line-height:1.4; max-height:48px; overflow:hidden; text-overflow:ellipsis;">
                                        <?php echo htmlspecialchars(mb_strimwidth($f['answer'] ?? '', 0, 160, '...')); ?>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px;">
                                <button type="button" class="btn btn-secondary" onclick="editFaq(<?php echo htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> Bearbeiten
                                </button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Möchtest du diese FAQ-Frage wirklich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_faq">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($f['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="Frage löschen">
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
        function openFaqEditor() {
            document.getElementById('faq-editor-box').style.display = 'block';
            document.getElementById('faq-editor-title').innerHTML = '<span class="material-icons">add_circle</span> Neue FAQ-Frage anlegen';
            document.getElementById('faq_form_action').value = 'add_faq';
            document.getElementById('faq_id').value = '';
            document.getElementById('faq_question').value = '';
            document.getElementById('faq_answer').value = '';
            document.getElementById('faq-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Frage anlegen';
            document.getElementById('faq_question').focus();
        }

        function closeFaqEditor() {
            document.getElementById('faq-editor-box').style.display = 'none';
        }

        function editFaq(faqData) {
            document.getElementById('faq-editor-box').style.display = 'block';
            document.getElementById('faq-editor-title').innerHTML = '<span class="material-icons">edit</span> FAQ-Frage bearbeiten';
            document.getElementById('faq_form_action').value = 'edit_faq';
            document.getElementById('faq_id').value = faqData.id || '';
            document.getElementById('faq_question').value = faqData.question || '';
            document.getElementById('faq_answer').value = faqData.answer || '';
            document.getElementById('faq-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Änderungen speichern';
            document.getElementById('faq-editor-box').scrollIntoView({ behavior: 'smooth' });
        }
        </script>
