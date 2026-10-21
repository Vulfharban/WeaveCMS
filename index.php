<?php
/**
 * ============================================================================
 * ARCHITEKTUR- & DESIGN-GOVERNANCE (STRIKTE REGEL):
 * KEIN MENÜ (WEDER IM FRONTEND NOCH IM BACKEND) DARF OHNE VORHERIGE
 * AUSDRÜCKLICHE ZUSTIMMUNG DES BENUTZERS GEÄNDERT, VERSCHOBEN ODER UMPLATZIERT WERDEN!
 * Dies gilt für:
 * - Hauptnavigation (Header Desktop & Mobile)
 * - Slide-in-Menüs (Kontakt, Impressum, Mobilnavigation)
 * - Sämtliche Menüpunkte, Reihenfolgen, Links und Trigger-Elemente
 * ============================================================================
 *
 * WeaveCMS Frontend Entry Point
 * Initialisiert das System und delegiert das Rendering an den FrontendController.
 *
 * @package WeaveCMS
 * @author WeaveCMS Team
 */

// Zentrale Konfigurationsdatei und Klassen einbinden
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/php/FrontendController.php';

// Datenbasis laden
$data_file = __DIR__ . '/data.json';
$data = load_data($data_file, [
    'settings' => [
        'upload_dir' => 'images/uploads',
        'privacy_policy_text' => ''
    ],
    'texts' => [
        'hero_title' => 'Handgefertigte Unikate & Workshops',
        'hero_subtitle' => 'Traditionelles Handwerk, kreative Formgebung, Naturdesign und inspirierende Praxiskurse.',
        'about_title' => 'Über mein Atelier & Handwerk',
        'about_text' => 'Handwerkskunst ist für mich eine lebendige Verbindung aus Natur, Tradition und zeitgenössischem Design...',
        'gallery_title' => 'Galerie & Werkstücke',
        'gallery_subtitle' => 'Ein Auszug aus meinen handgefertigten Designobjekten, Accessoires und maßgeschneiderten Auftragsarbeiten.',
        'courses_title' => 'Kurse & Kreativ-Workshops',
        'courses_subtitle' => 'Lerne die Grundlagen der handwerklichen Gestaltung in persönlicher, inspirierender Werkstatt-Atmosphäre selbst kennen.',
        'past_courses_title' => 'Impressionen vergangener Workshops',
        'past_courses_subtitle' => 'Ein Rückblick auf gemeinsame kreative Stunden und entstandene Meisterstücke.',
        'contact_intro_text' => 'Hast du Fragen zu meinen Workshops, möchtest einen Gutschein erwerben oder ein individuelles Werkstück anfragen?',
        'contact_name' => 'Maxima Musterfrau',
        'contact_street' => 'Musterstraße 12',
        'contact_city' => '12345 Musterstadt',
        'contact_phone' => '+49 123 4567890',
        'contact_email' => 'kontakt@maxima-musterfrau.de',
        'contact_instagram' => 'maxima.musterfrau'
    ],
    'gallery' => [],
    'courses' => [],
    'past_courses' => []
]);

// Frontend-Rendering über OOP FrontendController
FrontendController::renderPage($data);
