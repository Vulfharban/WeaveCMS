<?php
/**
 * WeaveCMS – Admin Tab: SEO & Social Sharing Management
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 * @var string $site_url Basis-URL
 * @var string $cur_meta_title Aktueller Meta-Titel
 * @var string $cur_meta_desc Aktuelle Meta-Beschreibung
 * @var string $cur_meta_keywords Aktuelle Meta-Keywords
 * @var string $cur_robots Aktuelle Robots-Einstellung
 * @var string $cur_canonical Aktuelle kanonische URL
 * @var string $cur_og_image Aktuelles OpenGraph-Vorschaubild
 */
?>
        <!-- ──────────────── SECTION 7: SEO & SOCIAL SHARING (PHASE 3) ──────────────── -->
        <div id="tab-seo" class="admin-section card" style="padding: 2.5rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom: 0.5rem;">
                <h2 style="color: var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary); font-size:28px;">travel_explore</span> Suchmaschinen &amp; Social Sharing
                </h2>
                <span style="font-size:0.85rem; padding:4px 12px; border-radius:20px; background:rgba(46,98,72,0.12); color:var(--primary); font-weight:600;">
                    SEO &amp; OpenGraph
                </span>
            </div>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 2rem; line-height:1.45;">
                Optimiere die Auffindbarkeit deiner Website bei Google, Bing und Co. und steuere, wie dein Webauftritt beim Teilen über WhatsApp, Facebook, LinkedIn und X/Twitter visualisiert wird.
            </p>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_seo">

                <!-- 1. Meta-Title mit Zähler -->
                <div class="form-group" style="margin-bottom: 1.75rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label style="font-weight:600; margin:0;">Meta-Title (Seitentitel in Google)</label>
                        <span id="title-counter" style="font-size:0.82rem; font-weight:600; color:var(--text-secondary);">
                            <span id="title-count">0</span> / 60 Zeichen empfohlen
                        </span>
                    </div>
                    <input type="text" id="meta_title_input" name="meta_title" class="form-control" 
                           value="<?php echo htmlspecialchars($cur_meta_title); ?>" 
                           placeholder="<?php echo htmlspecialchars(($data['texts']['hero_title'] ?? 'WeaveCMS') . ' | ' . ($data['texts']['contact_name'] ?? 'Website')); ?>"
                           oninput="updateSeoCountersAndPreview()">
                    <small style="color: var(--text-secondary); display:block; margin-top:4px;">
                        Wird als blaue Hauptüberschrift in Google-Suchergebnissen und als Titel beim Teilen auf Social Media angezeigt.
                    </small>
                </div>

                <!-- 2. Meta-Description mit Zähler -->
                <div class="form-group" style="margin-bottom: 1.75rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label style="font-weight:600; margin:0;">Meta-Description (Suchmaschinen-Beschreibung)</label>
                        <span id="desc-counter" style="font-size:0.82rem; font-weight:600; color:var(--text-secondary);">
                            <span id="desc-count">0</span> / 155 Zeichen empfohlen
                        </span>
                    </div>
                    <textarea id="meta_desc_input" name="meta_description" class="form-control" rows="3" 
                              placeholder="Beschreibe kurz und ansprechend, worum es auf deiner Website geht..."
                              oninput="updateSeoCountersAndPreview()"><?php echo htmlspecialchars($cur_meta_desc); ?></textarea>
                    <small style="color: var(--text-secondary); display:block; margin-top:4px;">
                        Erscheint als Snippet-Fließtext unter dem Seitentitel in den Suchergebnissen.
                    </small>
                </div>

                <!-- 3. Keywords & Roboter im 2-Spalten-Grid -->
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.75rem;">
                    <!-- Keywords -->
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-weight:600; margin-bottom:6px; display:block;">Meta-Keywords (Suchbegriffe)</label>
                        <input type="text" name="meta_keywords" class="form-control" 
                               value="<?php echo htmlspecialchars($cur_meta_keywords); ?>" 
                               placeholder="z. B. Handwerk, Flechtkunst, Kurse, Korbwaren">
                        <small style="color: var(--text-secondary); display:block; margin-top:4px;">
                            Kommagetrennt. Hilft kleineren Suchmaschinen und internen Katalogen.
                        </small>
                    </div>

                    <!-- Roboter-Steuerung (Robots) -->
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-weight:600; margin-bottom:6px; display:block;">Suchmaschinen-Roboter (Robots-Tag)</label>
                        <select name="robots" class="form-control">
                            <option value="index, follow" <?php echo ($cur_robots === 'index, follow') ? 'selected' : ''; ?>>
                                index, follow (Standard – Suchmaschinen indexieren &amp; Links folgen)
                            </option>
                            <option value="noindex, follow" <?php echo ($cur_robots === 'noindex, follow') ? 'selected' : ''; ?>>
                                noindex, follow (Nicht bei Google listen, aber Links folgen)
                            </option>
                            <option value="noindex, nofollow" <?php echo ($cur_robots === 'noindex, nofollow') ? 'selected' : ''; ?>>
                                noindex, nofollow (Suchmaschinen komplett sperren – Testbetrieb)
                            </option>
                        </select>
                        <small style="color: var(--text-secondary); display:block; margin-top:4px;">
                            Steuert, ob Suchmaschinen diese Seite in ihren Index aufnehmen dürfen.
                        </small>
                    </div>
                </div>

                <!-- 4. Kanonische URL (Canonical) -->
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label style="font-weight:600; margin-bottom:6px; display:block;">Kanonische URL (Canonical URL, optional)</label>
                    <input type="url" name="canonical_url" class="form-control" 
                           value="<?php echo htmlspecialchars($cur_canonical); ?>" 
                           placeholder="<?php echo htmlspecialchars($site_url); ?>">
                    <small style="color: var(--text-secondary); display:block; margin-top:4px;">
                        Leer lassen, um automatisch die aktuelle Domain/Adresse (<code><?php echo htmlspecialchars($site_url); ?></code>) zu verwenden.
                    </small>
                </div>

                <!-- 5. OpenGraph Sharing-Bild -->
                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <label style="font-weight:700; color:var(--text-primary); font-size:1rem; margin-bottom:0.5rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="color:var(--accent); font-size:22px;">image</span> Social-Sharing Vorschaubild (OpenGraph &amp; Twitter Card)
                    </label>
                    <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem;">
                        Dieses Bild wird großflächig dargestellt, wenn deine Website auf WhatsApp, Facebook, LinkedIn oder X geteilt wird (optimales Format: 1200 x 630 Pixel, JPG/PNG/WebP).
                    </p>

                    <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">
                        <!-- Aktuelle Vorschau -->
                        <div style="width: 220px; height: 124px; border-radius: 10px; border: 1px solid var(--border-color); overflow: hidden; background: #e5e7eb; display:flex; align-items:center; justify-content:center; position:relative; flex-shrink:0;">
                            <?php if (!empty($cur_og_image)): ?>
                                <img id="og-preview-img" src="../<?php echo htmlspecialchars(ltrim($cur_og_image, '/')); ?>" alt="Sharing Bild" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <img id="og-preview-img" src="../images/default_wicker1.png" alt="Sharing Bild" style="width:100%; height:100%; object-fit:cover;">
                            <?php endif; ?>
                        </div>

                        <!-- Upload & Aktionen -->
                        <div style="flex:1; min-width:240px;">
                            <div class="form-group" style="margin-bottom:0.75rem;">
                                <label style="font-size:0.85rem; margin-bottom:4px; display:block;">Neues Sharing-Bild hochladen:</label>
                                <input type="file" name="og_image_file" class="form-control" accept="image/jpeg,image/png,image/webp" style="padding:7px;">
                            </div>
                            <?php if (!empty($cur_og_image)): ?>
                                <label style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; color:#ef4444; cursor:pointer; margin-top:4px;">
                                    <input type="checkbox" name="remove_og_image" value="1">
                                    Aktuelles Bild entfernen (auf Standard zurücksetzen)
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 6. LIVE-VORSCHAU BOX (SERP & SOCIAL CARD) -->
                <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
                    <h4 style="margin:0 0 1.25rem 0; font-size:0.95rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:18px; color:var(--primary);">preview</span> Live-Vorschau in Google &amp; Social Media
                    </h4>

                    <!-- Google SERP Vorschau -->
                    <div style="margin-bottom: 1.75rem; padding: 1.25rem; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; font-family:Arial, sans-serif;">
                        <div style="font-size:0.75rem; color:#475569; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                            <span style="display:inline-block; width:18px; height:18px; border-radius:50%; background:#10b981; color:#fff; font-size:11px; text-align:center; line-height:18px; font-weight:700;">G</span>
                            <span id="serp-preview-url"><?php echo htmlspecialchars($site_url); ?></span>
                        </div>
                        <div id="serp-preview-title" style="color:#1a0dab; font-size:1.18rem; font-weight:400; line-height:1.3; text-decoration:none; margin-bottom:6px;">
                            <?php echo htmlspecialchars($cur_meta_title ?: (($data['texts']['hero_title'] ?? 'WeaveCMS') . ' | ' . ($data['texts']['contact_name'] ?? 'Website'))); ?>
                        </div>
                        <div id="serp-preview-desc" style="color:#4d5156; font-size:0.88rem; line-height:1.45;">
                            <?php echo htmlspecialchars($cur_meta_desc ?: ($data['texts']['hero_subtitle'] ?? 'Entdecke handgefertigte Naturgeflechte und Workshops.')); ?>
                        </div>
                    </div>

                    <!-- Social Card Vorschau (WhatsApp / Facebook) -->
                    <div style="max-width: 480px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #f8fafc; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                        <div style="height: 190px; background: #cbd5e1; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                            <?php if (!empty($cur_og_image)): ?>
                                <img id="social-card-img" src="../<?php echo htmlspecialchars(ltrim($cur_og_image, '/')); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <img id="social-card-img" src="../images/default_wicker1.png" style="width:100%; height:100%; object-fit:cover;">
                            <?php endif; ?>
                        </div>
                        <div style="padding: 12px 14px; background: #ffffff;">
                            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                                <?php echo htmlspecialchars(parse_url($site_url, PHP_URL_HOST) ?? 'localhost'); ?>
                            </div>
                            <div id="social-card-title" style="font-size:0.98rem; font-weight:600; color:#0f172a; line-height:1.3; margin-bottom:4px;">
                                <?php echo htmlspecialchars($cur_meta_title ?: (($data['texts']['hero_title'] ?? 'WeaveCMS') . ' | ' . ($data['texts']['contact_name'] ?? 'Website'))); ?>
                            </div>
                            <div id="social-card-desc" style="font-size:0.82rem; color:#64748b; line-height:1.35; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($cur_meta_desc ?: ($data['texts']['hero_subtitle'] ?? 'Entdecke handgefertigte Naturgeflechte und Workshops.')); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="font-size:18px;">save</span> SEO- &amp; Social-Sharing speichern
                </button>
            </form>
        </div>
