<?php
/**
 * ============================================================================
 * sgiT Education - BTCPay Webhook Handler
 * ============================================================================
 * 
 * Empfängt Zahlungsbenachrichtigungen von BTCPay Server.
 * Aktualisiert Family Wallet bei eingehenden Zahlungen.
 * 
 * Webhook URL für BTCPay:
 * http://localhost/Education/wallet/btcpay_webhook.php
 * 
 * @author sgiT Solution Engineering
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

// Logging aktivieren
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/btcpay_webhook.log');

// Header setzen
header('Content-Type: application/json');

// Nur POST akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Klassen laden
require_once __DIR__ . '/BTCPayManager.php';
require_once __DIR__ . '/WalletManager.php';

// Raw Payload lesen
$payload = file_get_contents('php://input');

if (empty($payload)) {
    error_log("BTCPay Webhook: Leerer Payload");
    http_response_code(400);
    echo json_encode(['error' => 'Empty payload']);
    exit;
}

// Signatur aus Header
$signature = $_SERVER['HTTP_BTCPAY_SIG'] ?? '';

// BTCPayManager initialisieren
try {
    $btcpay = new BTCPayManager();
} catch (Exception $e) {
    error_log("BTCPay Webhook: Manager Error - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error']);
    exit;
}

// Signatur validieren (wenn Secret konfiguriert)
$config = require __DIR__ . '/btcpay_config.php';
if (!empty($config['webhook_secret']) && $config['webhook_secret'] !== 'HIER_WEBHOOK_SECRET_EINTRAGEN') {
    if (!$btcpay->validateWebhookSignature($payload, $signature)) {
        error_log("BTCPay Webhook: Ungültige Signatur");
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

// Payload dekodieren
$data = json_decode($payload, true);

if (!$data) {
    error_log("BTCPay Webhook: Ungültiger JSON");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Webhook verarbeiten
$result = $btcpay->processWebhook($data);

// Log
error_log("BTCPay Webhook: " . json_encode([
    'type' => $data['type'] ?? 'unknown',
    'invoice_id' => $data['invoiceId'] ?? '',
    'action' => $result['action'] ?? 'unknown',
]));

// Bei Zahlung: Family Wallet updaten
if ($result['action'] === 'payment_received') {
    try {
        $walletManager = new WalletManager();
        
        $invoiceId = $result['invoice_id'];
        $amountSats = $result['amount_sats'];
        
        // Prüfen ob Invoice bereits verarbeitet
        $db = new SQLite3(__DIR__ . '/wallet.db');
        $stmt = $db->prepare("SELECT id FROM btcpay_invoices WHERE invoice_id = :id AND status = 'paid'");
        $stmt->bindValue(':id', $invoiceId);
        $existing = $stmt->execute()->fetchArray();
        
        if ($existing) {
            error_log("BTCPay Webhook: Invoice bereits verarbeitet - $invoiceId");
            echo json_encode(['status' => 'already_processed']);
            exit;
        }
        
        // Invoice in DB speichern/updaten
        $stmt = $db->prepare("
            INSERT INTO btcpay_invoices (invoice_id, amount_sats, status, paid_at)
            VALUES (:id, :amount, 'paid', datetime('now'))
            ON CONFLICT(invoice_id) DO UPDATE SET
                status = 'paid',
                paid_at = datetime('now')
        ");
        $stmt->bindValue(':id', $invoiceId);
        $stmt->bindValue(':amount', $amountSats, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Family Wallet erhöhen
        // Wir nutzen die real_sats Spalte (falls vorhanden) oder balance_sats
        $stmt = $db->prepare("
            UPDATE family_wallet 
            SET balance_sats = balance_sats + :amount,
                total_deposited = total_deposited + :amount,
                updated_at = datetime('now')
        ");
        $stmt->bindValue(':amount', $amountSats, SQLITE3_INTEGER);
        $stmt->execute();
        
        $db->close();
        
        error_log("BTCPay Webhook: $amountSats Sats zum Family Wallet hinzugefügt");
        
        echo json_encode([
            'status' => 'success',
            'invoice_id' => $invoiceId,
            'amount_sats' => $amountSats,
            'action' => 'family_wallet_updated'
        ]);
        
    } catch (Exception $e) {
        error_log("BTCPay Webhook: Wallet Update Error - " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Wallet update failed']);
        exit;
    }
} else {
    // Andere Events einfach bestätigen
    echo json_encode([
        'status' => 'received',
        'action' => $result['action']
    ]);
}
