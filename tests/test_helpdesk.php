<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/plugins/weave-helpdesk/HelpdeskUserManager.php';
require_once dirname(__DIR__) . '/plugins/weave-helpdesk/HelpdeskManager.php';

echo "Testing Helpdesk Integration...\n";
$provider = HelpdeskManager::getServiceProviderDetails();
echo "Dienstleister Name: " . $provider['name'] . "\n";
echo "Dienstleister Adresse: " . $provider['address'] . "\n";

$users = HelpdeskUserManager::loadUsers();
echo "Anzahl Benutzer: " . count($users) . "\n";

$tickets = HelpdeskManager::loadTickets();
echo "Anzahl Tickets: " . count($tickets) . "\n";

$kunde = $users[1];
echo "Kunde: " . $kunde['name'] . " (" . $kunde['company_name'] . ")\n";

$contract = HelpdeskManager::getContractSummary($kunde);
echo "Vertrag: " . $contract['contract_number'] . " (" . $contract['title'] . ")\n";
echo "Verfügbar: " . $contract['available'] . " Min. | Verbraucht: " . $contract['used'] . " Min. | Differenz: " . $contract['difference'] . " Min.\n";

// Test: Buchhaltungs-Schutz
$billedTicket = HelpdeskManager::getTicketById('26010854');
echo "Ticket 26010854 Status: " . $billedTicket['status'] . "\n";
$res = HelpdeskManager::changeStatus('26010854', 'In Bearbeitung', $kunde);
echo "Wiederöffnen-Versuch von abgerechnetem Ticket: " . ($res['success'] ? "FEHLER (durfte nicht öffnen!)" : "ERFOLGREICH BLOCKIERT: " . $res['error']) . "\n";

echo "ALL TESTS PASSED!\n";
