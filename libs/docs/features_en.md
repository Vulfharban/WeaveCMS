# WeaveCMS – Feature Overview & Functional Catalog

> **The lightweight, modular Flat-File & SQL Content Management System for small businesses, service providers & creative minds.**  
> *Official documentation for product website, download portal, and release distribution.*

---

## 🧭 Core Philosophy: The WeaveCMS Promise

WeaveCMS was built to bridge the gap between bloated legacy CMS solutions (such as WordPress) and rigid static HTML websites. It offers full content control via a modern administration panel without requiring **databases, command-line tools, or server build chains**, running instantly on standard web hosting environments.

* **Zero Bloat:** 100% native PHP (PHP 8.0+) – no Node.js, no Webpack, no server-side Composer dependencies required.
* **Privacy First:** Fully GDPR/DSGVO compliant out-of-the-box without tracking cookies, third-party CDNs, or telemetry.
* **Data Loss Prevention:** Built-in 1-click backup and PIN-protected restoration system.

---

## 📦 Feature Overview by Category

### 1. Core Architecture & Storage Engines

| Feature | Description | Status |
| :--- | :--- | :---: |
| **Flat-File JSON Storage** | Runs out-of-the-box without database configuration (`data.json`). Ideal for small and medium-sized websites. | ✅ Integrated (v0.2.0) |
| **SQL Storage Abstraction** | Seamless choice between local JSON storage and relational databases (MySQL / MariaDB / SQLite). | ✅ Integrated (v0.5.0) |
| **1-Click Data Migration** | Live 2-way migration between JSON flat-files and MySQL/MariaDB directly in the admin panel. | ✅ Integrated (v0.7.0) |
| **Atomic Data Persistence** | Write concurrency protection via temp-file replacement preventing corruption. | ✅ Integrated |
| **Zero Build Tools Required** | Upload files and go. No `npm install`, compiler, or build step needed. | ✅ Integrated |
| **Minimal Resource Footprint** | Page loads in milliseconds, perfectly tuned for cost-effective shared hosting. | ✅ Integrated |

---

### 2. Design, Themes & Presentation Engine

| Feature | Description | Status |
| :--- | :--- | :---: |
| **Dynamic Theme System** | Fully token-based styling using native CSS variables (`:root`). | ✅ Integrated |
| **Curated Color Palettes** | Predefined color harmonies (*Willow Joy*, *Moor & Amber*, *Nordic Clean*, *Warm Atelier*) plus custom hex color picker. | ✅ Integrated |
| **Adjustable Layout Widths** | Flexible container widths: Compact (1050 px), Standard (1200 px), Generous (1400 px). | ✅ Integrated |
| **Typography & Corner Radii Fine-Tuning** | Font scaling (95%, 100%, 108%) and card border radiuses (10 px, 20 px, 28 px). | ✅ Integrated |
| **4 Selectable Gallery Layouts** | **Smart Ambient-Blur** (soft image background blur), **Masonry Grid** (Pinterest style), **Editorial Grid** (magazine look), and **Classic 4:3** (fixed aspect ratio). | ✅ Integrated |
| **Flexible Logo System** | Configurable vector paw motifs and flexible placement (header or hero section). | ✅ Integrated |
| **Advanced Logo & Favicon Management** | Dedicated upload for SVG, PNG, WebP, and Favicons (.ico, .svg, .png), continuous height scaling (24–100 px), side-by-side live light/dark preview, SVG XSS sanitization, and automatic HTML head injection. | ✅ Integrated (Phase 11) |
| **Mobile Responsiveness & WCAG Audit** | WCAG 2.5.5 / 2.5.8 compliant touch targets (>= 44 × 44 px) for navigation, modal close triggers, and lightbox; iOS Safari auto-zoom prevention (16px input rule), and `overflow-x: clip` overflow protection. | ✅ Integrated (Phase 11) |
| **3 Navigation Menu Styles & Position Selector** | Selectable under *Design & Appearance*: `Horizontal Minimal` (standard), `Horizontal with Icons`, and `Vertical / Sidebar` (fixed left or right with responsive collapse <= 992px). | ✅ Integrated (v0.9.7) |
| **Icon & Symbol Assignment for Nav Items** | Automatic fallback to Google Material Icons (`photo_library`, `event`, `newspaper`, etc.) and custom icon override in section and page editors. | ✅ Integrated (v0.9.7) |
| **Plugin Theme & Navigation Hooks** | Filter hooks `theme_register_menu_types` and `theme_register_layouts`, plus action hook `frontend_render_navigation` allowing plugins to customize or replace navigation bars. | ✅ Integrated (v0.9.7) |
| **In-Navbar Accessibility (Phase 20)** | Native Dark/Light toggle (🌙/☀️ with tooltip) and 3-step font size zoom (A-/A/A+) directly in the main navigation & mobile drawer, eliminating redundant top bars and duplicate language switches. | ✅ Integrated (v1.0.0) |

---

### 3. Content Management & Section Content

| Feature | Description | Status |
| :--- | :--- | :---: |
| **100% Content Autonomy** | Edit all headings, sublines, and text blurbs directly through the admin panel. | ✅ Integrated (v0.2.0) |
| **Gallery & Portfolio Manager** | Upload images, name portfolio items, write descriptions, and assign categories. | ✅ Integrated |
| **Dynamic Categories** | Create, filter, and delete custom categories on the fly. | ✅ Integrated |
| **Manual Gallery Sorting** | Numerically reorder portfolio items with drag-friendly priority fields. | ✅ Integrated |
| **Event & Date Manager** | Manage dates, times, locations, pricing, capacity limits, and booking status. | ✅ Integrated |
| **Direct Event Links & Sharing** | 1-click invitation link copying and native Web Share API integration. | ✅ Integrated |
| **Impressions & Highlights** | Photo and review uploads for past events, workshops, and highlights. | ✅ Integrated |
| **Full-Featured Blog & Magazine Module** | Chronological articles, draft/published status, teasers, markdown, cover images, and individual article views (`?blog=slug`). | ✅ Integrated (v0.8.0) |
| **Custom Subpages** | Unlimited standalone pages (`?page=slug`) with Markdown, custom SEO metadata, nav toggles, and multi-language drawer tabs. | ✅ Integrated (v0.8.5) |
| **Individual Menu & Section Titles** | Customize menu label (`nav_label`), section title, and navigation visibility directly in each content tab and centrally in Section Manager. | ✅ Integrated (v0.8.5) |
| **Subpage Header Clearance & Navigation** | Optimal top spacing below sticky header on standalone pages, paired with language-aware back buttons. | ✅ Integrated (v0.8.5) |
| **FAQ Accordion** | Expandable question accordion with automatic Google `FAQPage` Schema.org JSON-LD structured data. | ✅ Integrated (v0.8.0) |
| **Team & Staff Profiles** | Profile cards with photo upload, role/specialization, biography, and email action. | ✅ Integrated (v0.8.0) |
| **Download Manager** | Public distribution of PDFs, flyers, and documents with file size and format indicators. | ✅ Integrated (v0.8.0) |
| **Services & Pricing Tables** | Pricing cards with bestseller badges, feature checklists, and direct contact inquiry links. | ✅ Integrated (v0.8.0) |
| **Modular Sections & Page Manager** | Freely order, toggle visibility, and configure sections with auto-synced navigation. | ✅ Integrated (v0.6.0) |
| **Global Architecture Switcher** | Switch seamlessly between **One-Pager** (default), **Classic Multi-Page** (home + separate `?page=slug` URLs for gallery, courses, shop, blog, downloads, genealogy), and **Hybrid Mode**. | ✅ Integrated (Phase 18) |
| **Modular Section Landing Page Builder** | Build unlimited stacked long-scroll pages with custom section combinations, unique slugs, and tailored SEO tags. | ✅ Integrated (Phase 18) |
| **Intelligent Routing & Navigation Engine** | Context-aware navigation: Anchor links (`#section`) for sections on the active page, direct URL links (`?page=slug`) for externalized sections, and language-aware logo home links. | ✅ Integrated (Phase 18) |
| **Webtrees Genealogy Section in Section Manager** | Full control of genealogy embed sections via central section manager (reorder, toggle, menu sync). | ✅ Integrated (v0.9.5) |
| **Backend Content Hub** | Clean contextual sub-navigation (`.admin-subnav`) under "Content & Pages" for fast module switching. | ✅ Integrated (v0.8.0) |
| **Module Pool & Section Layout Board (Phase 20)** | Interactive 2-zone Drag & Drop board (inactive Module Pool vs. Active Sections) for intuitive website architecture management. | ✅ Integrated (v1.0.0) |
| **Dynamic Content Sub-Navigation (Phase 20)** | Backend sub-navigation tabs under "Content & Pages" are dynamically filtered to show only active modules. | ✅ Integrated (v1.0.0) |
| **Responsive Touch Switcher for Board (Phase 20)** | Tablet and mobile optimized board controls (<= 900px) with view switching (`view-active` / `view-pool`) and 1-tap actions (+ Activate, Reorder, Settings). | ✅ Integrated (v1.0.0) |
| **Visual Column & Grid Builder (Phase 27)** | Modular multi-column layouts (`[grid]` / `[col]`), layout presets (Hero, Testimonials, Call-to-Action), modernized `[box]` shortcodes, and responsive 3-breakpoint live preview (Desktop, Tablet, Mobile). | ✅ Integrated (v1.3.2) |
| **Global Taxonomies & Tag Archive (Phase 28.1)** | Cross-module tagging for blog articles, standalone pages, courses, gallery items, and downloads with responsive tag archives (`?tag=slug`), filter cloud, and admin autocompletion via datalist. | ✅ Integrated (v1.3.2) |
| **Custom Fields & Structured Attributes (Phase 28.2 & 28.3)** | Configurable structured fields per module (text, textarea, number, boolean, url, date, file) with automatic validation, admin editor inputs, and frontend helper functions `get_custom_field()` and `render_custom_fields()`. | ✅ Integrated (v1.3.2) |
| **Grid Builder for Modular Pages (Phase 32)** | Section-level column layouts (`full`, `2`, `3`, `1-2`, `2-1`) inside the landing page stack, universal grid & shortcode toolbars for free-text and about sections, and responsive 3-breakpoint live preview. | ✅ Integrated (v1.3.3) |

---

### 4. Legal Compliance & GDPR / Privacy

| Feature | Description | Status |
| :--- | :--- | :---: |
| **GDPR Privacy Policy Editor** | Integrated editor for privacy policies in compliance with Art. 13 GDPR with Markdown support. | ✅ Integrated (v0.2.0) |
| **Compliant Default Template** | 1-click legal template covering server logs, contact forms, data retention, user rights, and cookie absence. | ✅ Integrated (v0.2.0) |
| **Slide-in Legal Drawer** | Fast switching between **Imprint / Legal Notice** and **Privacy Policy** in the frontend without page reload. | ✅ Integrated (v0.2.0) |
| **XSS-Safe Markdown Parser** | Secure rendering of headings, lists, links, tables, and typography without script injection vectors. | ✅ Integrated (v0.2.0) |
| **Zero Marketing or Tracking Cookies** | Completely cookie-banner-free in the frontend, as no user-identifying or tracking data is stored. | ✅ Integrated |

---

### 5. Backup & Disaster Recovery

| Feature | Description | Status |
| :--- | :--- | :---: |
| **1-Click Data Backup (JSON)** | Exports all website text, configuration, and courses into a lightweight `.json` archive. | ✅ Integrated (v0.2.0) |
| **1-Click Full Archive (ZIP)** | Bundles all configuration, uploaded images (`images/uploads/`), and database dumps into a `.zip` archive. | ✅ Integrated (v0.2.0) |
| **Protected Storage Location** | Dedicated server folder `backups/` shielded with `.htaccess` (`Require all denied` / 403 Forbidden). | ✅ Integrated (v0.2.0) |
| **Safety Pre-Restore Snapshot** | Automatic emergency backup generated right before any restore action is executed. | ✅ Integrated (v0.2.0) |
| **PIN-Protected Restore** | Restoration strictly requires the administrator master PIN to prevent unauthorized resets. | ✅ Integrated (v0.2.0) |
| **External Backup Upload** | Upload and restore previously exported `.zip` or `.json` backups. | ✅ Integrated (v0.2.0) |
| **Direct Download & Cleanup** | Download archives directly from the backend or purge outdated backups. | ✅ Integrated (v0.2.0) |

---

### 6. Communication, Email & Anti-Spam

| Feature | Description | Status |
| :--- | :--- | :---: |
| **Authenticated SMTP Delivery** | Reliable email dispatch using PHPMailer over dedicated mail servers (TLS/SSL, ports 587, 465, 25). | ✅ Integrated |
| **Live SMTP Connection Test** | Send instant test emails from the admin settings to verify mailbox credentials. | ✅ Integrated |
| **Mailto Fallback Mode** | If SMTP is disabled, forms launch the visitor's local mail client. | ✅ Integrated |
| **Natural Icon Captcha** | GDPR-friendly visual selection (4 out of 12 nature & everyday icons) without external tracking. | ✅ Integrated |
| **Honeypot Spam Trap** | Invisible form fields silently intercept automated spambots. | ✅ Integrated |
| **Customizable Email Templates** | Personalized greetings and customized templates for course registration confirmations. | ✅ Integrated |
| **Integrated Contact Inbox** | Built-in inquiries management directly in the backend dashboard. | ✅ Integrated (v0.3.0) |

---

### 7. Security & Administration

| Feature | Description | Status |
| :--- | :--- | :---: |
| **Two-Factor Authentication** | Secure login requiring strong master password and 4-to-6 digit master PIN. | ✅ Integrated |
| **PIN Protection for Critical Actions** | Sensitive actions (password reset, PIN update, data restore, DB migration) require active PIN re-entry. | ✅ Integrated |
| **CSRF Protection** | Cryptographic tokens validated on all administrative POST actions. | ✅ Integrated |
| **System File Access Restrictions** | Strict `.htaccess` rules block direct access to `data.json`, `config.php`, `.git`, logs, and backups. | ✅ Integrated |
| **Hardened Sessions** | Session cookies configured with `HttpOnly`, `SameSite`, and auto-destruction upon sign-out. | ✅ Integrated |
| **Role-Based Access Control (RBAC)** | User roles: Administrator, Moderator, and Editor (Content User) with granular capability checks. | ✅ Integrated (Phase 10) |
| **Brute-Force Shield & Rate Limiting** | Progressive delay and temporary IP banning following repeated failed login attempts. | ✅ Integrated (Phase 9) |
| **Headless REST-API (/api/v1/)** | JSON read endpoints for pages, posts, products, and courses with Bearer token authentication (`wct_...`) & scopes. | ✅ Integrated (Phase 31) |
| **Event Webhook Dispatcher** | Automated HTTP POST event dispatching with HMAC-SHA256 signature on system events (`contact.received`, `order.created`, `post.published`, `backup.created`) with delivery logs & retries. | ✅ Integrated (Phase 31) |
| **Audit & Security Logging** | Tamper-evident logging of security-critical administrative actions in `data/security.log`. | ✅ Integrated (Phase 9) |
| **Strict HSTS Policy & SSL Preloading** | Enforces encrypted HTTPS connections with optional preloading header for modern browsers. | ✅ Integrated (Phase 17.1) |
| **Production CSP & Subresource Integrity** | Hardened Content Security Policy against XSS and SRI hash verification helper (`renderSriTag`). | ✅ Integrated (Phase 17.2) |
| **Filesystem Permission Audit (POSIX/Linux)** | Automatic validation of critical file & folder permissions (`0640/0750`) in security tab and dashboard. | ✅ Integrated (Phase 17.3) |
| **Reverse-Proxy Anti-Spoofing Filter** | Strict IP validation for upstream proxies & Cloudflare to prevent spoofing of client addresses. | ✅ Integrated (Phase 17.4) |
| **Automated Admin Security Alerting** | Email alerts on brute-force lockouts with rate-limiting cooldown protection against email flooding. | ✅ Integrated (Phase 17.5) |
| **Live Deployment Checklist & Tooling** | Live readiness matrix and one-click audit links to SecurityHeaders.com and Qualys SSL Labs. | ✅ Integrated (Phase 17.6) |

---

### 8. Installation, Deployment & Updates

| Feature | Description | Status |
| :--- | :--- | :---: |
| **Webspace Installer & Pre-Flight Check** | Automatic setup wizard (`install.php`) with 5-part pre-flight audit (PHP environment, write permissions, extensions, web server rewrite, php.ini recommendations) and auto-locking. | ✅ Integrated (v0.9.5) |
| **Database Preflight & Permission Check** | Comprehensive connection check for MySQL/MariaDB with user privilege validation (`CREATE TABLE`, `INSERT`, `SELECT`, `DROP`), auto-create, and enforced pre-test before step 3. | ✅ Integrated (v0.9.5) |
| **SEO & OpenGraph Engine** | Dedicated *SEO & Sharing* tab, meta titles & descriptions with live counters, social sharing image uploads, robot directives, and live SERP/social card preview. | ✅ Integrated (v0.3.0) |
| **Multi-Language & i18n** | 12 European languages out-of-the-box (DE, EN, FR, IT, NL, ES, PT, EL, DA, NO, SV, FI), header language switcher, cookie persistence, fallback cascades, and dynamic `hreflang` tags. | ✅ Integrated (v0.8.0) |
| **Automated Remote Version Check** | Release checks against configurable update endpoints with notification badges in the dashboard. | ✅ Integrated (Phase 12) |
| **Modular Plugin System** | Event-driven hook & filter architecture (`PluginManager`) for plugins in `/plugins` with auto-discovery, lifecycle controls, and admin settings. | ✅ Integrated (Phase 13) |
| **Universal Multi-Ratio Demo Assets** | Curated neutral demo images across various aspect ratios (3:4, 16:9, 1:1, 4:3) for realistic theme evaluation. | ✅ Integrated (Phase 14) |
| **Interactive Sandbox & Demo Mode** | Ephemeral evaluation mode allowing users to explore WeaveCMS with session-isolated data, banner, and auto-reset. | ✅ Integrated (Phase 15) |
| **Software Download Area** | Download cards with version badges, platform tags (Win/Mac/Linux), changelog drawers, screenshot lightboxes, and tamper-resistant click counter with bot filter. | ✅ Integrated (Phase 16) |
| **Modular Captcha Selection Engine** | Choice of 4 anti-spam options: Icon Captcha, Math Equation Captcha, Canvas Numeric Captcha, and Invisible Honeypot, plus anti-scraping email protection in the imprint. | ✅ Integrated (Phase 16) |

---

### 9. E-Commerce, Shop & Extensions (Plugins)

| Feature | Description | Status |
| :--- | :--- | :---: |
| **WeaveCMS Shop Plugin (`shop`)** | Complete e-commerce store with product catalog, off-canvas cart drawer (`#shop-cart-drawer`), floating cart badge, and live inventory validation. | ✅ Integrated (v0.9.0) |
| **Inventory Management (`inventory`)** | Stock keeping system with SKU, shelf locations, purchase/sale pricing, margin analysis (€ / %), reorder alert badges, goods receipt bookings, physical stocktaking, audit trails (`inventory_log.json`), and CSV export. 100% compatible with the Shop plugin. | ✅ Integrated (v0.9.0) |
| **Bidirectional Shop Coupling & Auto-Deduction** | Real-time stock sync: Inventory items are shared as shop products; successful checkout automatically triggers stock deduction and logs an audit record via the `shop_order_created` hook. | ✅ Integrated (v0.9.0) |
| **Credit Card Payments** | Native authorization for Visa, Mastercard, and American Express with Luhn-10 checksum validation, CVC & expiration checks, and sandbox testing mode. | ✅ Integrated (v0.9.0) |
| **PayPal Express Checkout** | Streamlined PayPal payment flow with automatic transaction ID logging. | ✅ Integrated (v0.9.0) |
| **WERO European Instant Payment** | European Payments Initiative (EPI) payment gateway offering instant account-to-account settlement via dynamic EPC069-12 SEPA-QR codes (GiroCode) scanned in mobile banking apps. | ✅ Integrated (v0.9.0) |
| **Backend Order Management** | Comprehensive orders table with status workflows (`Paid`, `Processing`, `Shipped`, `Completed`, `Cancelled`), customer data, and revenue stats. | ✅ Integrated (v0.9.0) |
| **Webtrees Genealogy Integration (Same-Server)** | Embeds Webtrees family trees and genealogy charts directly into WeaveCMS. Enforced same-server policy blocks external remote URLs for privacy and security. | ✅ Integrated (v0.9.0) |
| **Community Discussion Forum (`weave-forum`)** | responsive community forum with 3-column layout (Forum, Statistics, Latest Post), 1440px wide-screen support, modular categories, sticky topics, solved status, and post reactions (Kudos). | ✅ Integrated (v1.3.1) |
| **Dual Storage Engine & 1-Click Migration** | Freedom of choice for forum data storage between flat-file JSON (`data/forum_data.json`) and indexed relational databases (SQLite `data/forum.sqlite` / MySQL) with seamless 1-click in-place data migration. | ✅ Integrated (v1.3.1) |
| **Forum User Management & RBAC (`weave-forum-users`)** | Community member system with roles (`forum_admin`, `moderator`, `expert`, `forum_user`), user profile management, GDPR data export/deletion, 1-click site admin sync, and automatic backend session recognition. | ✅ Integrated (v1.3.1) |
| **Granular Access & Read Permissions** | Configurable forum access mode (*Public: all content freely readable* vs. *Protected: registration required to read*) with dynamic whitelist for public exceptions (e.g., Announcements, Welcome area). | ✅ Integrated (v1.3.1) |
| **Frontend Moderation Center** | Dedicated frontend navigation tab *“🛡️ Moderation”* (`?page=forum&view=moderation`) for administrators and moderators featuring reported post queues, quick resolution actions, and audit logs. | ✅ Integrated (v1.3.1) |
| **Advanced Forum Search with Category Filter** | Fast full-text search with sub-board dropdown selector (with category optgroups), search scope selection (*Posts & Titles* vs. *Titles only*), sorting options, and `<mark>` keyword highlighting. | ✅ Integrated (v1.3.1) |
| **Forum Markdown & Code Engine** | Comprehensive Markdown parser supporting bold (`**`), italic (`*`), hyperlinks (`[Text](url)`), inline code (`` `code` ``), fenced multi-line code blocks (```` ```php ... ``` ````) with language badges & 1-click copy, as well as Markdown blockquotes and BBCode quotes (`[quote]`) with live preview. | ✅ Integrated (v1.3.1) |

---

## 📈 Release History & Milestones

### v1.3.2 (Global Taxonomies, Tag Archive, Custom Fields & Visual Grid Builder)

* **Global Taxonomies & Cross-Module Tagging (Phase 28.1):**
  * **Unified Tagging Engine (`TaxonomyManager`):** Aggregates and normalizes tags across blog posts, custom pages, courses, gallery items, and downloads with consistent slugification.
  * **Responsive Frontend Tag Archives (`?tag={slug}`):** Clean archive view featuring hit counter, module-categorized results (blog, pages, courses, gallery, downloads), quick-filter cloud, and dynamic localization via `LanguageManager::t()`.
  * **Interactive Admin Tagging:** Quick selection of existing tags using HTML5 datalists and interactive click pills with auto-completion in blog and page editors.
* **Custom Fields Manager (Phase 28.2):**
  * **Configurable Structured Fields:** Define custom data attributes for pages (`pages`), blog (`blog`), courses (`courses`), and downloads (`downloads`) directly in *Settings & Design* (`tab_settings.php`).
  * **7 Supported Field Types:** Single-line text (`text`), multi-line text (`textarea`), numeric (`number`), toggle switch (`boolean`), web link (`url`), date (`date`), and file upload/path (`file`) with required validation, placeholders, and helper texts.
  * **Automated Form Handling & Validation:** Type-safe extraction and validation (`CustomFieldManager::extractAndValidate`) during page and blog post saves.
* **Dynamic Frontend Rendering & Templating (Phase 28.3):**
  * **Global Template Helpers:** `get_custom_field($item, $key, $default)` for targeted field lookups and `render_custom_fields($item, $module)` for automated, responsive key-value attribute displays.
  * **Seamless Page Integration:** Automatic rendering of clickable tag badges (`TaxonomyManager::renderTagBadges`) and custom attributes on blog article pages (`blog_single.php`) and standalone pages (`page_single.php`).
* **Visual Column & Grid Builder (Phase 27):**
  * **Grid Shortcodes (`[grid]` / `[col]`):** Layout presets (50/50, 3-column, 1/3-2/3, 2/3-1/3) in the content editor powered by clean CSS grid with mobile stacking and nesting protection.
  * **Reusable Presets & Modern Boxes:** Hero teaser, testimonial card, call-to-action block, and modern shortcode-based `[box]` containers.
  * **Responsive Breakpoint Preview:** Modal with interactive `<iframe>` for real-time preview across Desktop (1200px), Tablet (768px), and Smartphone (375px) viewports.

---

### v1.3.1 (Community Discussion Forum, Access Control, Kunena UI & Markdown Engine)

* **Discussion Forum (`weave-forum`):**
  * **3-Column Layout & 1440px Width:** Maximizes widescreen desktop displays (container up to 1440px), color-coded category cards with collapsible sections, dedicated info, statistics, and latest-post columns, plus online users and community statistics widgets at page bottom.
  * **Dual Storage & 1-Click Migration:** Seamless support for flat-file JSON (`forum_data.json`) and indexed relational databases (SQLite `forum.sqlite` or MySQL) including zero-downtime data migration directly from the backend.
* **Forum User Management & Permissions (`weave-forum-users`):**
  * **Role-Based Access Control (RBAC):** Tiered permissions for Administrators, Moderators, Experts, and regular Members with color-coded author badges.
  * **1-Click Site Admin Synchronization:** Automatic synchronization of WeaveCMS site owner credentials (`contact_name`, `contact_email`) into the forum database as `forum_admin`, along with seamless auto-login for active CMS admin sessions.
  * **Dedicated Frontend Moderation Center:** New navigation tab *“🛡️ Moderation”* with dynamic badge counter for pending reports, 1-click actions (*Resolve* / *Delete*), and quick overviews of pinned and locked threads.
* **Granular Access & Read Permissions:**
  * **Forum Access Modes:** Configurable in the backend between *“🌐 Public: All categories freely readable”* and *“🔒 Protected: Registration required to read”*.
  * **Dynamic Whitelist for Public Exceptions:** In protected mode, designated sub-boards (such as *Announcements & Releases*, *Welcome & Introductions*) can be toggled via checkboxes to remain permanently open for unauthenticated guests.
  * **Informative Guest Barriers:** Visitors encountering protected boards are presented with a friendly registration call-to-action; category overviews display a `🔒 Members only` badge while masking sensitive thread content.
  * **Clear Reply Notice:** Explicit distinction between guest read-only access and write permissions (*“This topic is freely readable by all visitors. To post a reply, please log in...”*).
* **Advanced Forum Search with Board Filter:**
  * **Sub-Board Scoping:** Dropdown filter to search within specific forum sections (organized by category optgroups and respecting guest access rights).
  * **Scope & Sorting Controls:** Toggle between full-text search (*In posts & titles*) and title-only search (*Only in topic titles*); sort by latest activity, creation date, most replies, or most views.
  * **Visual Keyword Highlighting:** Automatic highlight tags (`<mark>`) around matched keywords in search result snippets.
* **Rich Markdown, Code & Quote Engine:**
  * **Rich Text Formatting:** Full support for bold (`**text**`), italic (`*text*`), hyperlinks (`[Text](url)`), and inline code (`` `code` ``).
  * **Fenced Code Blocks with Badges & Copy Button:** Multi-line syntax-styled code blocks with language badge and 1-click clipboard copy button.
  * **Blockquotes & BBCode Quotes:** Native support for Markdown blockquotes (`>`) and BBCode quotes (`[quote="Author"]...[/quote]`) with author attribution.
  * **WYSIWYG Live Preview:** Real-time preview tab in the reply editor rendering all formatting and quotes accurately before posting.
* **System & Upload Hardening:**
  * **ImageUploader::upload():** Backwards-compatible parameter signature (`string|array|null $uploadRelDir`) seamlessly accepting both directory string paths and the WeaveCMS `$data` array, eliminating fatal type errors.

---

### v1.3.1 (Complete Backend Localization, User Language Preferences & Custom Action Hub)

* **Complete Backend Localization (DE & EN):**
  * **Exhaustive Translation Catalogs:** Complete localization across all administration views (header, login screen, primary & sub-navigation, dashboard KPIs, user management, quick actions, status toasts, and confirmation dialogs) in `libs/lang/admin/de.json` and `libs/lang/admin/en.json` with 200+ synchronized keys.
  * **Login Screen Language Switcher:** Elegant switcher between German and English directly on the login screen with persistent session and cookie storage.
  * **Robust Fallback Engine:** `LanguageManager::adminT()` and `__a()` helper featuring browser locale detection, catalog fallback cascades, and placeholder replacement (`{name}`, `{count}`, `{version}`).
* **Per-User Default Backend Language Preferences:**
  * **Individual Language Settings:** Each user account (Administrator, Moderator, Editor) can define their preferred backend language (`de` or `en`).
  * **Seamless Live Synchronization:** Clicking the header language switcher immediately persists the choice into the user's profile (`admin_lang`) and updates active sessions and cookies across logins.
  * **User Management with Language Badges:** Visual language column with pixel-perfect inline SVG country flags and intuitive language selectors when creating or editing accounts.
* **Custom Action Hub & Dashboard Quick Actions (Phase 26):**
  * **Personalized Workflow Cards:** Customizable dashboard quick actions with role-based access control (RBAC) and dynamic badges (e.g. unread inbox count).
  * **Action Catalog & 1-Click Presets:** 25+ curated actions across Content, Media, Communication, Design, System, and Plugins with predefined presets (*Allrounder*, *Editor*, *Administrator*, *Minimal*).
  * **Floating Sticky Language & Page Toolbar:** Persistent editor toolbar for swift switching between languages and subpages without tedious vertical scrolling.

---

### v1.3.0 (Central Media Manager, WebP/AVIF Image Pipeline & Content Revisions)

* **Central Media Manager & Asset Hub (Phase 22):**
  * New top-level navigation tab *“Media Library”* between *Content & Pages* and *Design*.
  * Switchable grid and list views for uploaded photos, illustrations, vectors, and documents with file size indicators, MIME-type filters, live search, and sorting.
  * 1-click copy for image URLs, Markdown tags (`![Alt](url)`), and responsive HTML5 `<picture>` snippets.
  * Accessibility modal for alt-texts and image titles stored in `data/media_meta.json`.
* **Modern Image Pipeline with WebP, AVIF & Resizing (Phase 22):**
  * Automatic generation of compressed WebP and AVIF variants during upload via PHP `gd`.
  * Automatic generation of standard responsive sizes (`thumb`: 350px, `medium`: 800px, `large`: 1400px).
  * Smart variant isolation: Child variants do not clutter the main media view and are automatically cleaned up when the master file is deleted.
* **Drag & Drop Multi-Upload & 7Z Support (Phase 22):**
  * Concurrent multi-file upload with visual progress bars and filename sanitization.
  * Strict magic-bytes verification (`37 7A BC AF 27 1C` for 7z) supporting JPG, PNG, WebP, GIF, SVG, PDF, ZIP, and 7Z (excluding proprietary office formats in favor of standardized PDF).
* **Content Revisions & Draft Mode (Phase 23):**
  * Automatic snapshot versioning on every save in `data/revisions/` (up to 25 historical versions).
  * Interactive revision modal with unified diff viewer (`+` / `-`) and 1-click rollback.
  * Status management (`published`, `draft`, `scheduled`) with scheduled publishing and secure preview tokens (`?preview=...`) with frontend notification banner.
* **Full Internationalization (i18n):**
  * All media and revision components fully localized in English and German via `libs/lang/admin/`.

---

### v1.2.0 (Modernized Sections Kanban, Contact Anti-Scraping & UI Hardening)

* **Modernized Kanban Board for Active/Inactive Sections (Content & Pages):**
  * **Intuitive Kanban Architecture:** Active Pages & Sections (Live) are positioned on the left (1.25× width with primary green accent) as the central workspace; the Inactive Module Pool on the right as component reservoir.
  * **Elimination of Wireframe Borders:** Refined glassmorphic surface cards (`border-radius: 14px`) replacing rough dashed outlines, with dynamic dropzone cues during drag-over.
  * **Squircle Icon Tiles & Badges:** 38×38px squircle icon tiles (`.sec-icon-tile`), position badges (`#1`, `#2`...), and clean status tags (`Plugin`, `Menu`, placement).
  * **Ergonomic Action Toolbar:** Prominent `+ Activate` button in the pool and streamlined action buttons for reordering, editing, content management, and deactivation.
* **Anti-Scraping Email Protection for Contact Drawer (`contact_email_captcha`):**
  * **Direct Harvester Shield:** Optional obfuscation of the direct contact email address in the contact slide-in drawer to prevent automated web crawlers from scraping contact emails.
  * **HTML5 Canvas Captcha:** 5-digit distorted numeric captcha with dynamic AJAX verification for visitors.
  * **Independent Controls:** Separate toggle switches in the Security tab for imprint and contact email anti-scraping.
* **Layout & Container Hardening:**
  * **HTML Validation & Bugfix:** Removed premature closing `</div>` tag in `tab_texts.php` that previously caused section texts to leak across all admin navigation tabs.
  * **Seamless Language Tab Navigation:** Reliable client-side language switching in texts and pages without unintended redirects to the dashboard.

---

### v1.1.0 (Live Server Deployment, Production Hardening & Multi-Language Tabs / Phase 17)

* **Live Server Deployment, SSL/HSTS Hardening & Production Security (Phase 17):**
  * **Strict HSTS Policy & SSL Preloading (17.1):** Configurable activation of `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` strictly on confirmed live domains with active SSL certificates.
  * **Hardened Production CSP & Subresource Integrity (17.2):** Tightened HTTP Content Security Policy (`script-src`, `style-src`, `object-src 'none'`, `form-action 'self'`) and SRI tag helper `SecurityManager::renderSriTag`.
  * **Filesystem Permissions & POSIX Audit (17.3):** Live audit verification in the security tab and dashboard for sensitive files (`config.php`, `config.local.php`, `data/data.json`) and directories (`data/`, `backups/`, `logs/`, `images/uploads/`) including `.htaccess` shield verification.
  * **Reverse-Proxy & Trusted-Proxy IP Filter (17.4):** Robust anti-IP-spoofing mechanism; headers such as `CF-Connecting-IP` and `X-Forwarded-For` are only accepted from explicitly authorized upstream proxies or CIDR subnets.
  * **Automated Admin Security Alerts (17.5):** Automatic email alerts on brute-force lockouts with built-in cooldown protection (preventing email flooding).
  * **Live Deployment Checklist (17.6):** 4-point checklist and direct verification links to SecurityHeaders.com and Qualys SSL Labs directly in the backend. 27/27 tests in `tests/test_phase17_production_security.php` passing.
* **Streamlined Multi-Language Management with Language Tabs (Option A & B):**
  * **Language Tabs per Section & Module:** Instant switching (`[🇩🇪 Primary] [🇬🇧 English] ...`) in all section bars and module drawers (FAQ, Team, Downloads, Pricing, Courses, Gallery) without long vertical scrolling.
  * **Language Badges:** Visual indicators in all admin content module list views showing which translations are actively maintained.
  * **Automatic Frontend Fallback Cascade:** Dynamic text resolution seamlessly falling back to primary language defaults when a translation is missing.

### v1.0.0 (Official Release 1.0.0 / Phase 20)

* **In-Navbar Accessibility & UX Optimization:**
  * **Direct Menu Integration:** Dark/Light mode switch (🌙/☀️ with dynamic tooltip) and 3-step font size zoom (A- / A / A+) embedded seamlessly into header navigation (`header_nav.php`) and mobile slide-in drawer (`slide_mobile_nav.php`).
  * **Elimination of Redundancy:** Removed standalone 38px top accessibility bar (`accessibility_bar.php`) and avoided duplicate language switchers when accessibility is enabled.
  * **Clean Visual Flow:** Eliminates vertical layout shifts or header offsets in the frontend.
* **Module Pool, Drag & Drop Section Builder & Dynamic Subnav:**
  * **Interactive Module Pool & 2-Zone Board:** Separation into inactive categories ("Module Pool") and active sections ("Active Sections / Layout Board") with visual icons, section types, and descriptions in the admin panel (`tab_texts.php`).
  * **Drag & Drop Ordering:** Intuitive drag-and-drop to move inactive modules into the live page structure and reorder sections with HTML5 Drag API and visual drop indicators.
  * **Dynamic Admin Sub-Navigation:** Sub-navigation tabs under *"Content & Pages"* (`.admin-subnav` for gallery, blog, courses, FAQs, team, pricing, downloads, genealogy) adapt dynamically in real-time, hiding inactive modules.
  * **Tablet & Mobile Optimization (<= 900px):** Responsive 2-phase switcher (*Active Sections* vs. *Module Pool*) for touch screens with 1-tap activation (`+ Activate`), order buttons (`▲` / `▼`), quick settings (`⚙️`), content shortcuts (`📝`), and return-to-pool buttons (`✕`).
  * **Asynchronous AJAX Synchronization:** Instant persistence of sort order and visibility without page reloads via `AdminController::sync_sections_pool` with animated toast feedback.

### v0.9.7

* **Menu Architecture, Design Position Selector & Plugin Hooks (Phase 19):**
  * **Menu Position & Type Selector:** Admin settings under *Design & Appearance* (`tab_settings.php`) with 3 interactive tiles: `Horizontal Minimal (Standard)`, `Horizontal with Icons`, and `Vertical (Sidebar Navigation)`.
  * **Alignment Selector:** Configurable sidebar orientation (fixed `left` or `right` with responsive collapse <= 992px).
  * **Icon & Symbol Assignment:** Automatic Material Icons fallback in `SectionManager::getDefaultIcon` for all section types, plus custom icon picker in section and page editors.
  * **Plugin Hooks for Menus & Layouts:** Filter hooks `theme_register_menu_types` and `theme_register_layouts`, and action hook `frontend_render_navigation`.
  * **100% Test Coverage:** 28/28 automated tests (`test_phase19.php`) passing.

### v0.9.6

* **Flexible Website Architecture & Modular Landing Pages (Phase 18):**
  * **Global Architecture Switcher:** Selectable in *Design & Appearance* between `One-Pager`, `Classic Multi-Page Website` (with separate `?page=slug` URLs), and `Individual / Hybrid Mode`.
  * **Modular Section Landing Page Builder:** Extended page management (`tab_pages.php`) with stacked section pages, custom slugs, individual SEO tags, and dedicated template `page_modular.php`.
  * **Intelligent Routing & Navigation Engine:** Dynamic link generation (anchor `#section` vs subpage `?page=slug`), language-aware logo home navigation, and breadcrumb back buttons.
  * **Plugin System & Accessibility Hardening:** Resolved closure reference warnings in `PluginManager`, added bidirectional section sync, and fixed captcha icon keys.

### v0.9.5

* **Branding & Logo Management (Phase 11):**
  * 4 logo styles: Pure text, custom image logo, icon + text combination, and SVG nature emblems (paw prints).
  * Safe file upload with vector sanitization (`ImageUploader::sanitizeSvg`) against XSS scripts and malicious event handlers.
  * Continuous height scaling (24 px to 100 px) with live preview on dark and light backgrounds.
  * Favicon and Apple Touch icon upload (.ico, .svg, .png) with browser tab mockups.
* **Mobile Responsiveness & WCAG Audit:**
  * WCAG 2.5.5 / 2.5.8 touch targets (>= 44 × 44 px) for mobile hamburger menu, close buttons, and lightboxes.
  * iOS Safari auto-zoom prevention (16px form inputs rule).
  * Horizontal overflow protection (`overflow-x: clip`).
* **Webspace Pre-Flight Health Check & Database Permission Audit:**
  * 5-part pre-flight verification in `install.php` (PHP runtime, permissions, modules, web server, php.ini tips).
  * MySQL live privilege test (`CREATE TABLE`, `INSERT`, `SELECT`, `DROP`) with enforced validation before progressing.

### v0.9.0

* **WeaveCMS Inventory Management Plugin (`plugins/inventory`):**
  * SKU, EAN barcodes, shelf/bin locations, purchasing & retail prices, margin analytics (€ / %), and stock valuation.
  * Reorder alert levels with warning badges (*Low Stock*, *Sold Out*, *Optimal*).
  * Goods receipt booking with delivery note numbers, supplier records, and cost adjustment.
  * Tamper-evident audit trail (`data/inventory_log.json`) recording every stock movement.
  * Physical stocktaking sheet with automated delta adjustments.
  * Accounting-grade CSV export formatted with UTF-8 BOM for Microsoft Excel.
* **WeaveCMS Shop Plugin (`plugins/shop`):**
  * Product catalog with tax rates (§ 19 UStG small business toggle) and digital goods toggle.
  * Interactive slide-out cart drawer (`#shop-cart-drawer`) with floating trigger badge.
  * Multi-step checkout modal with address validation.
  * Payment gateways: Credit Card (Luhn-10), PayPal Express, and WERO (EPC069-12 SEPA QR code).
  * Backend order management with KPI tiles and order detail modals.
* **Webtrees Genealogy Integration:**
  * Strict same-server enforcement preventing cross-origin data leakage.
* **Software Download Portal & Click Analytics:**
  * Download cards with platform tags, changelog drawer, and bot-resistant counter proxy.

### v0.8.5

* **Modular Section & Menu Titles:** Quick-edit bar (`section_nav_bar.php`) for menu label and visibility in all content tabs.
* **Custom Subpages with Native i18n:** Multi-language tabs in page editor drawer (`tab_pages.php`).
* **Sticky Header Clearance:** Proper top clearance on standalone subpages and high-contrast back buttons.
* **Content Hub:** Consolidated admin sub-navigation (`.admin-subnav`) under "Content & Pages".
* **Full Blog & Magazine Module:** Articles with featured images, Markdown, author tags, and reading views.

### v0.8.0

* **Multi-Language Architecture (12 European Languages):** DE, EN, FR, IT, NL, ES, PT, EL, DA, NO, SV, FI with `LanguageManager.php`, cookie persistence, and SEO `hreflang` tags.
* **OOP Refactoring & View Modularization:** Streamlined `admin/index.php` and `index.php` into clean MVC-style partials and dedicated OOP managers.
* **Hardened Security:** Comprehensive `.htaccess` rules blocking access to configs, logs, sqlite files, and template directories.

### v0.7.0

* **1-Click Two-Way Data Migration:** Bidirectional converter between Flat-File JSON and MySQL/MariaDB with pre-migration safety snapshots.

### v0.6.0

* **Modular Sections Engine:** Flexible section order and visibility management via `SectionManager.php` with automatic navigation sync.

### v0.5.0

* **Dual Storage Driver:** Abstracted persistence layer with atomic `JsonStorageDriver` and transaction-safe `PdoSqlStorageDriver`.
* **Installer Expansion:** Database configuration step with live connection test.

### v0.4.0

* **Webspace Installer:** 4-step setup wizard with environment health check and administrative password/PIN initialization.

### v0.3.0

* **SEO & Social Sharing:** Dedicated SEO tab with live SERP preview, OpenGraph tags, and social card graphics.
* **Contact Inquiries Mailbox:** Local storage and management of contact form submissions.

### v0.2.0

* **Backup & Restore System:** 1-click JSON/ZIP export and PIN-protected restoration.
* **GDPR Legal Management:** Built-in privacy policy editor and legally vetted defaults.

### v0.1.0-alpha

* **Design & Theme Engine:** Tokenized CSS styles, flexible gallery layouts (Ambient-Blur, Masonry, Editorial, Classic), and SMTP communication.
