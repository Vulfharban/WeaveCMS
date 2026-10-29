<?php
/**
 * ============================================================================
 * WeaveCMS - Custom Fields Manager (CustomFieldManager)
 * ============================================================================
 * Verwaltet frei definierbare Zusatzfelder pro Inhaltsmodul (Seiten, Blog,
 * Kurse, Downloads) inklusive Validierung und dynamischer Frontend-Ausgabe
 * (Phase 28.2 & 28.3).
 *
 * @package WeaveCMS\Core
 */

class CustomFieldManager {

    /**
     * Unterstützte Feldtypen
     */
    public const TYPE_TEXT     = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_NUMBER   = 'number';
    public const TYPE_BOOLEAN  = 'boolean';
    public const TYPE_URL      = 'url';
    public const TYPE_DATE     = 'date';
    public const TYPE_FILE     = 'file';

    /**
     * Gibt alle verfügbaren Feldtypen mit Labels zurück.
     *
     * @return array
     */
    public static function getSupportedTypes(): array {
        return [
            self::TYPE_TEXT     => 'Textzeile',
            self::TYPE_TEXTAREA => 'Textbereich',
            self::TYPE_NUMBER   => 'Zahl',
            self::TYPE_BOOLEAN  => 'Ja/Nein (Checkbox)',
            self::TYPE_URL      => 'Hyperlink (URL)',
            self::TYPE_DATE     => 'Datum',
            self::TYPE_FILE     => 'Datei / Dokument'
        ];
    }

    /**
     * Gibt alle Felddefinitionen für ein bestimmtes Inhaltsmodul zurück.
     *
     * @param array $data
     * @param string $module z. B. 'pages', 'blog', 'courses', 'downloads'
     * @return array
     */
    public static function getDefinitions(array $data, string $module): array {
        $defs = $data['custom_fields_def'][$module] ?? [];
        return is_array($defs) ? array_values($defs) : [];
    }

    /**
     * Speichert die Felddefinitionen für ein bestimmtes Inhaltsmodul.
     *
     * @param string $dataFile
     * @param array $data
     * @param string $module
     * @param array $definitions
     * @return bool
     */
    public static function saveDefinitions(string $dataFile, array &$data, string $module, array $definitions): bool {
        if (!isset($data['custom_fields_def']) || !is_array($data['custom_fields_def'])) {
            $data['custom_fields_def'] = [];
        }

        $clean = [];
        foreach ($definitions as $def) {
            $key = strtolower(preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', trim($def['key'] ?? ''))));
            if (empty($key)) continue;

            $label = trim($def['label'] ?? ucfirst($key));
            $type = in_array($def['type'] ?? '', array_keys(self::getSupportedTypes()), true) ? $def['type'] : self::TYPE_TEXT;
            $required = !empty($def['required']);
            $placeholder = trim($def['placeholder'] ?? '');
            $help = trim($def['help'] ?? '');

            $clean[$key] = [
                'key'         => $key,
                'label'       => $label,
                'type'        => $type,
                'required'    => $required,
                'placeholder' => $placeholder,
                'help'        => $help
            ];
        }

        $data['custom_fields_def'][$module] = array_values($clean);
        return save_data($dataFile, $data);
    }

    /**
     * Liest die übermittelten Formularwerte für Custom Fields aus,
     * validiert sie anhand der Felddefinitionen und gibt das Ergebnis zurück.
     *
     * @param string $module
     * @param array $postValues
     * @param array $data
     * @param string|null $error Fehlermeldung bei Validierungsfehler
     * @return array Saubere Key-Value-Map
     */
    public static function extractAndValidate(string $module, array $postValues, array $data, ?string &$error = null): array {
        $defs = self::getDefinitions($data, $module);
        $result = [];
        $error = null;

        foreach ($defs as $def) {
            $key = $def['key'];
            $val = $postValues[$key] ?? null;

            if ($def['type'] === self::TYPE_BOOLEAN) {
                $result[$key] = !empty($val) ? 1 : 0;
                continue;
            }

            $strVal = is_string($val) ? trim($val) : '';

            // Pflichtfeldprüfung
            if (!empty($def['required']) && $strVal === '') {
                $error = 'Das Pflichtfeld „' . htmlspecialchars($def['label']) . '“ darf nicht leer sein.';
                return [];
            }

            // Typenspezifische Validierung
            if ($strVal !== '') {
                if ($def['type'] === self::TYPE_NUMBER) {
                    if (!is_numeric($strVal)) {
                        $error = 'Das Feld „' . htmlspecialchars($def['label']) . '“ muss eine gültige Zahl sein.';
                        return [];
                    }
                    $strVal = (strpos($strVal, '.') !== false) ? (float)$strVal : (int)$strVal;
                } elseif ($def['type'] === self::TYPE_URL) {
                    if (!filter_var($strVal, FILTER_VALIDATE_URL) && !str_starts_with($strVal, '/') && !str_starts_with($strVal, '#')) {
                        $error = 'Das Feld „' . htmlspecialchars($def['label']) . '“ erfordert eine gültige URL.';
                        return [];
                    }
                } elseif ($def['type'] === self::TYPE_DATE) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $strVal)) {
                        $error = 'Das Feld „' . htmlspecialchars($def['label']) . '“ erfordert ein gültiges Datum (JJJJ-MM-TT).';
                        return [];
                    }
                }
            }

            $result[$key] = $strVal;
        }

        return $result;
    }

    /**
     * Rendert die Admin-Eingabemaske für ein Modul (HTML-Markup).
     *
     * @param string $module
     * @param array $currentValues Bereits gespeicherte Werte
     * @param array $data
     * @return string
     */
    public static function renderAdminFields(string $module, array $currentValues, array $data): string {
        $defs = self::getDefinitions($data, $module);
        if (empty($defs)) {
            return '';
        }

        $html = '<div class="custom-fields-group" style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">';
        $html .= '<h4 style="color:var(--primary); margin:0 0 0.8rem 0; font-size:1rem; display:flex; align-items:center; gap:8px;">';
        $html .= '<span class="material-icons" style="font-size:18px;">tune</span> ' . htmlspecialchars(__a('admin.custom_fields.heading', 'Zusatzfelder (Custom Fields)'));
        $html .= '</h4>';
        $html .= '<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:1rem;">';

        foreach ($defs as $def) {
            $key = $def['key'];
            $label = htmlspecialchars($def['label']);
            $req = !empty($def['required']) ? ' *' : '';
            $placeholder = htmlspecialchars($def['placeholder'] ?? '');
            $val = $currentValues[$key] ?? '';
            $fieldId = 'cf_' . $module . '_' . $key;
            $fieldName = 'custom_fields[' . htmlspecialchars($key) . ']';

            $html .= '<div class="form-group" style="margin-bottom:0.5rem;">';
            $html .= '<label for="' . $fieldId . '" style="font-weight:700; font-size:0.88rem; color:var(--text-primary); margin-bottom:0.3rem; display:block;">';
            $html .= $label . '<span style="color:#ef4444;">' . $req . '</span>';
            if (!empty($def['help'])) {
                $html .= ' <span style="font-weight:normal; font-size:0.75rem; color:var(--text-secondary);">' . htmlspecialchars($def['help']) . '</span>';
            }
            $html .= '</label>';

            switch ($def['type']) {
                case self::TYPE_BOOLEAN:
                    $checked = !empty($val) ? ' checked' : '';
                    $html .= '<label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.9rem; margin-top:4px;">';
                    $html .= '<input type="checkbox" id="' . $fieldId . '" name="' . $fieldName . '" value="1"' . $checked . ' style="width:16px; height:16px; accent-color:var(--primary);">';
                    $html .= '<span>' . htmlspecialchars(__a('admin.custom_fields.active', 'Aktivieren / Ja')) . '</span>';
                    $html .= '</label>';
                    break;

                case self::TYPE_TEXTAREA:
                    $html .= '<textarea id="' . $fieldId . '" name="' . $fieldName . '" class="form-control" rows="3" placeholder="' . $placeholder . '" style="font-size:0.9rem;">' . htmlspecialchars((string)$val) . '</textarea>';
                    break;

                case self::TYPE_NUMBER:
                    $html .= '<input type="number" step="any" id="' . $fieldId . '" name="' . $fieldName . '" class="form-control" value="' . htmlspecialchars((string)$val) . '" placeholder="' . $placeholder . '" style="font-size:0.9rem;">';
                    break;

                case self::TYPE_DATE:
                    $html .= '<input type="date" id="' . $fieldId . '" name="' . $fieldName . '" class="form-control" value="' . htmlspecialchars((string)$val) . '" style="font-size:0.9rem;">';
                    break;

                case self::TYPE_URL:
                    $html .= '<input type="url" id="' . $fieldId . '" name="' . $fieldName . '" class="form-control" value="' . htmlspecialchars((string)$val) . '" placeholder="https://..." style="font-size:0.9rem;">';
                    break;

                default: // text / file
                    $html .= '<input type="text" id="' . $fieldId . '" name="' . $fieldName . '" class="form-control" value="' . htmlspecialchars((string)$val) . '" placeholder="' . $placeholder . '" style="font-size:0.9rem;">';
                    break;
            }

            $html .= '</div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Liest den Wert eines einzelnen Custom Fields aus einem Inhalts-Item.
     *
     * @param array $item
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(array $item, string $key, mixed $default = null): mixed {
        if (isset($item['custom_fields'][$key])) {
            return $item['custom_fields'][$key];
        }
        return $default;
    }

    /**
     * Rendert die Frontend-Ausgabe für Custom Fields (Phase 28.3).
     * Strukturiert mit Schema.org-Attribut-Mapping (additionalProperty).
     *
     * @param array $item
     * @param string $module
     * @param array $data
     * @return string
     */
    public static function renderFrontend(array $item, string $module, array $data = []): string {
        $defs = self::getDefinitions($data, $module);
        $values = $item['custom_fields'] ?? [];
        if (empty($defs) || empty($values)) {
            return '';
        }

        $itemsToRender = [];
        foreach ($defs as $def) {
            $key = $def['key'];
            if (!isset($values[$key]) || $values[$key] === '' || $values[$key] === null) {
                continue;
            }
            $val = $values[$key];
            if ($def['type'] === self::TYPE_BOOLEAN && empty($val)) {
                continue;
            }
            $itemsToRender[] = ['def' => $def, 'value' => $val];
        }

        if (empty($itemsToRender)) {
            return '';
        }

        $html = '<div class="weave-custom-fields-card" style="margin:2rem 0; padding:1.25rem 1.5rem; background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px;">';
        $html .= '<h4 style="font-size:1.05rem; margin:0 0 1rem 0; color:var(--text-primary); font-weight:700;">' . htmlspecialchars(__a('frontend.custom_fields.heading', 'Zusatzinformationen')) . '</h4>';
        $html .= '<dl style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin:0;">';

        foreach ($itemsToRender as $itemRow) {
            $def = $itemRow['def'];
            $val = $itemRow['value'];
            $label = htmlspecialchars($def['label']);

            $html .= '<div style="background:var(--bg-card, #fff); padding:10px 14px; border-radius:8px; border:1px solid rgba(0,0,0,0.05);" itemprop="additionalProperty" itemscope itemtype="https://schema.org/PropertyValue">';
            $html .= '<dt style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-secondary); margin-bottom:4px;" itemprop="name">' . $label . '</dt>';
            $html .= '<dd style="margin:0; font-size:0.95rem; font-weight:600; color:var(--text-primary);" itemprop="value">';

            if ($def['type'] === self::TYPE_URL) {
                $html .= '<a href="' . htmlspecialchars((string)$val) . '" target="_blank" rel="noopener noreferrer" style="color:var(--primary); text-decoration:underline;">' . htmlspecialchars((string)$val) . '</a>';
            } elseif ($def['type'] === self::TYPE_BOOLEAN) {
                $html .= '✓ ' . htmlspecialchars(__a('frontend.custom_fields.yes', 'Ja'));
            } elseif ($def['type'] === self::TYPE_DATE) {
                $html .= htmlspecialchars(date('d.m.Y', strtotime((string)$val)));
            } else {
                $html .= nl2br(htmlspecialchars((string)$val));
            }

            $html .= '</dd></div>';
        }

        $html .= '</dl></div>';
        return $html;
    }
}
