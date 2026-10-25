<?php
/**
 * ============================================================================
 * WeaveCMS Storage Driver Interface (Phase 7 Vorbereitung)
 * ============================================================================
 * Definiert die universelle Speicher-Schnittstelle für Flat-File JSON
 * und relationale SQL-Datenbanken (MySQL / MariaDB / SQLite).
 */

interface StorageDriverInterface {
    /**
     * Initialisiert den Treiber (z. B. Verzeichnisse anlegen oder Tabellen erzeugen).
     * @return bool
     */
    public function init(): bool;

    /**
     * Lädt alle Anwendungsdaten als verschachteltes Array.
     * @return array
     */
    public function loadData(): array;

    /**
     * Speichert das vollständige Datenarray atomar und persistent.
     * @param array $data
     * @return bool
     */
    public function saveData(array $data): bool;

    /**
     * Prüft die Verfügbarkeit des Speichermediums (Health-Ping).
     * @return bool
     */
    public function ping(): bool;

    /**
     * Gibt den sprechenden Namen des Treibers zurück.
     * @return string
     */
    public function getDriverName(): string;

    /**
     * Gibt die letzte Fehlermeldung bei Lese- oder Schreibfehlern zurück.
     * @return string|null
     */
    public function getLastError(): ?string;
}

