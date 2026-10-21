<?php
/**
 * WeaveCMS – Admin Tab: Kontaktanfragen-Postfach
 * 
 * @var array $inbox_entries Liste aller Kontaktanfragen
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ──────────────── SECTION: KONTAKTANFRAGEN-POSTFACH ──────────────── -->
        <div id="tab-inbox" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 0.5rem;">📥 Kontaktanfragen</h2>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 2rem;">
                Alle über das Kontaktformular eingegangenen Anfragen werden hier lokal archiviert – unabhängig davon, ob der Mailversand funktioniert.
            </p>
            <?php if (empty($inbox_entries)): ?>
                <p style="color: var(--text-secondary);">Noch keine Anfragen eingegangen.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid var(--border-color); color:var(--text-secondary);">
                            <th style="padding:10px 8px;">Eingang</th>
                            <th style="padding:10px 8px;">Absender</th>
                            <th style="padding:10px 8px;">Nachricht</th>
                            <th style="padding:10px 8px;">Status</th>
                            <th style="padding:10px 8px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($inbox_entries as $ie):
                        $is_new = ($ie['status'] ?? '') === 'neu';
                        $subject = 'Re: Deine Anfrage' . (!empty($ie['course_ref']) ? ' - ' . $ie['course_ref'] : '');
                        $mailto = 'mailto:' . $ie['email'] . '?subject=' . rawurlencode($subject);
                        $preview = mb_strimwidth($ie['message'] ?? '', 0, 80, '…', 'UTF-8');
                    ?>
                        <tr style="border-bottom:1px solid var(--border-color); <?php echo $is_new ? 'background:rgba(239,68,68,0.05);' : ''; ?>">
                            <td style="padding:10px 8px; white-space:nowrap;">
                                <?php if ($is_new): ?><span style="background:#ef4444; color:#fff; border-radius:999px; padding:1px 8px; font-size:0.7rem; font-weight:700; margin-right:4px;">NEU</span><?php endif; ?>
                                <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($ie['created_at'] ?? 'now'))); ?>
                            </td>
                            <td style="padding:10px 8px;">
                                <strong><?php echo htmlspecialchars($ie['name'] ?? ''); ?></strong><br>
                                <small style="color:var(--text-secondary);"><?php echo htmlspecialchars($ie['email'] ?? ''); ?><?php echo !empty($ie['phone']) ? ' · ' . htmlspecialchars($ie['phone']) : ''; ?></small>
                                <?php if (!empty($ie['course_ref'])): ?><br><small style="color:var(--accent);">Kurs: <?php echo htmlspecialchars($ie['course_ref']); ?></small><?php endif; ?>
                            </td>
                            <td style="padding:10px 8px; max-width:320px;">
                                <a href="#" style="color:var(--text-primary); text-decoration:none;" onclick="openInboxModal(<?php echo htmlspecialchars(json_encode($ie, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES); ?>); return false;">
                                    <?php echo htmlspecialchars($preview); ?>
                                </a>
                            </td>
                            <td style="padding:10px 8px;">
                                <form method="POST" action="" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="inbox_set_status">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($ie['id']); ?>">
                                    <select name="status" class="form-control" style="padding:4px 8px;" onchange="this.form.submit()">
                                        <option value="neu" <?php echo ($ie['status'] ?? '') === 'neu' ? 'selected' : ''; ?>>Neu</option>
                                        <option value="in_bearbeitung" <?php echo ($ie['status'] ?? '') === 'in_bearbeitung' ? 'selected' : ''; ?>>In Bearbeitung</option>
                                        <option value="erledigt" <?php echo ($ie['status'] ?? '') === 'erledigt' ? 'selected' : ''; ?>>Erledigt</option>
                                    </select>
                                </form>
                            </td>
                            <td style="padding:10px 8px; white-space:nowrap;">
                                <a href="<?php echo htmlspecialchars($mailto); ?>" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px;"><span class="material-icons" style="font-size:15px;">reply</span> Antworten</a>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Diese Anfrage wirklich unwiderruflich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="inbox_delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($ie['id']); ?>">
                                    <button type="submit" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; color:#f87171; border-color:#f87171; display:inline-flex; align-items:center;"><span class="material-icons" style="font-size:15px;">delete</span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Volltext-Modal für Kontaktanfragen -->
        <div id="inbox-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; padding:1rem;" onclick="if(event.target===this) closeInboxModal();">
            <div class="card" style="max-width:640px; width:100%; max-height:85vh; overflow:auto; padding:2rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1rem;">
                    <h3 id="inbox-modal-title" style="margin:0; color:var(--primary);"></h3>
                    <button type="button" class="btn btn-outline" style="padding:4px 10px;" onclick="closeInboxModal()">✕</button>
                </div>
                <div id="inbox-modal-meta" style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:1rem; line-height:1.5;"></div>
                <div id="inbox-modal-msg" style="white-space:pre-wrap; line-height:1.6;"></div>
            </div>
        </div>
        <script>
        function openInboxModal(e) {
            document.getElementById('inbox-modal-title').textContent = e.name || '';
            var meta = [e.created_at, e.email, e.phone, e.course_ref ? ('Kurs: ' + e.course_ref) : ''].filter(Boolean);
            document.getElementById('inbox-modal-meta').textContent = meta.join(' · ');
            document.getElementById('inbox-modal-msg').textContent = e.message || '';
            document.getElementById('inbox-modal').style.display = 'flex';
        }
        function closeInboxModal() {
            document.getElementById('inbox-modal').style.display = 'none';
        }
        </script>
