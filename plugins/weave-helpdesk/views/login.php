<?php
/**
 * Weave Helpdesk – Login & Registrierung View
 */
$error = $_SESSION['hd_login_error'] ?? null;
$success = $_SESSION['hd_login_success'] ?? null;
unset($_SESSION['hd_login_error'], $_SESSION['hd_login_success']);

$allowRegistration = HelpdeskUserManager::isSelfRegistrationAllowed();
$mode = $_GET['mode'] ?? 'login';
$provider = HelpdeskManager::getServiceProviderDetails();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – <?= htmlspecialchars($provider['name']) ?> Helpdesk</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(PluginManager::getPluginUrl('weave-helpdesk')) ?>/assets/css/helpdesk.css">
    <style>
        .hd-auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #257985 0%, #1b5b64 100%);
            padding: 20px;
            box-sizing: border-box;
        }
        .hd-auth-card {
            background: #fff;
            width: 100%;
            max-width: 440px;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .hd-auth-header {
            background: #257985;
            color: #fff;
            padding: 24px;
            text-align: center;
        }
        .hd-auth-header h2 {
            margin: 0 0 6px 0;
            font-size: 20px;
        }
        .hd-auth-header p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .hd-auth-body {
            padding: 24px;
        }
        .hd-alert-error {
            background: #fdf2f2;
            color: #c0392b;
            border-left: 4px solid #e74c3c;
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .hd-alert-success {
            background: #f0fdf4;
            color: #1b5e20;
            border-left: 4px solid #2ecc71;
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .hd-auth-footer {
            padding: 14px 24px;
            background: #f8fafc;
            border-top: 1px solid #edf2f7;
            text-align: center;
            font-size: 12px;
        }
        .hd-auth-footer a {
            color: #257985;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body class="helpdesk-app-body">
<div class="hd-auth-wrapper">
    <div class="hd-auth-card">
        <div class="hd-auth-header">
            <h2><?= htmlspecialchars($provider['name']) ?></h2>
            <p>Service- & Aufgaben-Helpdesk</p>
        </div>
        <div class="hd-auth-body">
            <?php if (!empty($error)): ?>
                <div class="hd-alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="hd-alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($mode === 'register' && $allowRegistration): ?>
                <!-- Registrierungsformular -->
                <form action="?page=helpdesk&action=register" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    
                    <div class="hd-form-group">
                        <label class="hd-form-label">Vollständiger Name / Ansprechpartner *</label>
                        <input type="text" name="name" class="hd-input" required placeholder="z. B. Max Mustermann">
                    </div>

                    <div class="hd-form-group">
                        <label class="hd-form-label">E-Mail-Adresse (Login) *</label>
                        <input type="email" name="email" class="hd-input" required placeholder="name@firma.de">
                    </div>

                    <div class="hd-form-group">
                        <label class="hd-form-label">Passwort *</label>
                        <input type="password" name="password" class="hd-input" required placeholder="Mindestens 6 Zeichen">
                    </div>

                    <div class="hd-form-group">
                        <label class="hd-form-label">Kundentyp</label>
                        <select name="customer_type" class="hd-select">
                            <option value="company">Geschäftskunde (Firma)</option>
                            <option value="private">Privatkunde</option>
                        </select>
                    </div>

                    <div class="hd-form-group">
                        <label class="hd-form-label">Firmenname (optional)</label>
                        <input type="text" name="company_name" class="hd-input" placeholder="z. B. Nordwind Logistik & Handel GmbH">
                    </div>

                    <div class="hd-form-group">
                        <label class="hd-form-label">Adresse (optional)</label>
                        <input type="text" name="address" class="hd-input" placeholder="Straße, Hausnr., PLZ, Ort">
                    </div>

                    <div class="hd-form-group">
                        <label class="hd-form-label">Telefonnummer (optional)</label>
                        <input type="text" name="phone" class="hd-input" placeholder="z. B. +49 123 456789">
                    </div>

                    <button type="submit" class="hd-btn hd-btn-green" style="width: 100%; justify-content: center; padding: 10px; font-size: 14px;">
                        Konto erstellen & Anmelden
                    </button>
                </form>
            <?php else: ?>
                <!-- Login-Formular -->
                <form action="?page=helpdesk&action=login" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    
                    <div class="hd-form-group">
                        <label class="hd-form-label">E-Mail-Adresse</label>
                        <input type="email" name="email" class="hd-input" required placeholder="name@domain.de" autofocus>
                    </div>

                    <div class="hd-form-group">
                        <label class="hd-form-label">Passwort</label>
                        <input type="password" name="password" class="hd-input" required placeholder="Dein Passwort">
                    </div>

                    <button type="submit" class="hd-btn hd-btn-dark" style="width: 100%; justify-content: center; padding: 10px; font-size: 14px; background: #257985;">
                        Anmelden
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="hd-auth-footer">
            <?php if ($mode === 'register'): ?>
                Bereits registriert? <a href="?page=helpdesk&mode=login">Hier anmelden</a>
            <?php else: ?>
                <?php if ($allowRegistration): ?>
                    Noch kein Account? <a href="?page=helpdesk&mode=register">Jetzt registrieren</a>
                <?php else: ?>
                    <span style="color: #718096;">Zugang nur mit freigeschaltetem Kundenkonto.</span>
                <?php endif; ?>
            <?php endif; ?>
            <div style="margin-top: 8px;">
                <a href="<?= htmlspecialchars(dirname($_SERVER['SCRIPT_NAME'] ?? '/')) ?>" style="color: #718096; font-size: 11px;">← Zurück zur Startseite</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>

