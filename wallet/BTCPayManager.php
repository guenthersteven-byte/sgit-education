<?php
/**
 * ============================================================================
 * sgiT Education - BTCPayManager v1.1
 * ============================================================================
 * 
 * Zentrale Klasse für BTCPay Server Integration.
 * 
 * Features:
 * - Externer Server im Netzwerk (StartOS, Umbrel, etc.)
 * - Lokale Docker-Installation
 * - Auto-Discovery von Servern im Netzwerk
 * - Invoice-Erstellung (Einzahlungen)
 * - Lightning Payouts (Auszahlungen)
 * - Webhook-Handling mit Polling-Fallback
 * 
 * @author sgiT Solution Engineering
 * @version 1.1
 * @date 02.12.2025
 * ============================================================================
 */

class BTCPayManager {
    
    /** @var string Aktiver Host */
    private string $host = '';
    
    /** @var string API Key */
    private string $apiKey = '';
    
    /** @var string Store ID */
    private string $storeId = '';
    
    /** @var string Webhook Secret */
    private string $webhookSecret = '';
    
    /** @var string Netzwerk (mainnet/testnet/regtest) */
    private string $network = '';
    
    /** @var array Komplette Konfiguration */
    private array $config;
    
    /** @var bool SSL verifizieren */
    private bool $verifySSL = false;
    
    /** @var string Aktiver Modus (external/local) */
    private string $activeMode = '';
    
    /** @var string|null Letzter Fehler */
    private ?string $lastError = null;
    
    /** @var string Cache-Datei für Discovery */
    private string $cacheFile;
    
    // ========================================================================
    // KONSTRUKTOR
    // ========================================================================
    
    /**
     * Konstruktor - lädt Konfiguration und wählt Server
     * 
     * @throws Exception wenn keine Konfiguration gefunden
     */
    public function __construct() {
        $configPath = __DIR__ . '/btcpay_config.php';
        
        if (!file_exists($configPath)) {
            throw new Exception("BTCPay Konfiguration nicht gefunden: $configPath");
        }
        
        $this->config = require $configPath;
        $this->cacheFile = __DIR__ . '/btcpay_discovery_cache.json';
        
        // Server auswählen basierend auf Modus
        $this->selectServer();
    }
    
    /**
     * Wählt den zu verwendenden Server basierend auf Konfiguration
     */
    private function selectServer(): void {
        $mode = $this->config['mode'] ?? 'auto';
        
        switch ($mode) {
            case 'external':
                $this->loadServerConfig('external');
                break;
                
            case 'local':
                $this->loadServerConfig('local');
                break;
                
            case 'auto':
            default:
                // Erst Cache prüfen
                $cached = $this->getCachedDiscovery();
                if ($cached) {
                    $this->loadServerConfig($cached['mode']);
                    return;
                }
                
                // Externe Server versuchen
                if ($this->tryExternalServer()) {
                    $this->cacheDiscovery('external');
                    return;
                }
                
                // Fallback auf lokal
                if ($this->tryLocalServer()) {
                    $this->cacheDiscovery('local');
                    return;
                }
                
                // Kein Server verfügbar
                $this->activeMode = 'none';
                $this->lastError = 'Kein BTCPay Server erreichbar';
                break;
        }
    }
    
    /**
     * Lädt Konfiguration für einen bestimmten Modus
     */
    private function loadServerConfig(string $mode): void {
        $serverConfig = $this->config[$mode] ?? [];
        
        $this->host = rtrim($serverConfig['host'] ?? '', '/');
        $this->apiKey = $serverConfig['api_key'] ?? '';
        $this->storeId = $serverConfig['store_id'] ?? '';
        $this->webhookSecret = $serverConfig['webhook_secret'] ?? '';
        $this->verifySSL = $serverConfig['verify_ssl'] ?? false;
        $this->network = $serverConfig['network'] ?? 'mainnet';
        $this->activeMode = $mode;
    }
    
    /**
     * Versucht Verbindung zu externem Server
     */
    private function tryExternalServer(): bool {
        $external = $this->config['external'] ?? [];
        
        if (empty($external['host']) || empty($external['api_key'])) {
            return false;
        }
        
        $this->loadServerConfig('external');
        
        return $this->testConnection()['success'];
    }
    
    /**
     * Versucht Verbindung zu lokalem Server
     */
    private function tryLocalServer(): bool {
        $local = $this->config['local'] ?? [];
        
        if (empty($local['host']) || empty($local['api_key'])) {
            return false;
        }
        
        $this->loadServerConfig('local');
        
        return $this->testConnection()['success'];
    }
    
    // ========================================================================
    // DISCOVERY & CACHE
    // ========================================================================
    
    /**
     * Holt gecachtes Discovery-Ergebnis
     */
    private function getCachedDiscovery(): ?array {
        if (!file_exists($this->cacheFile)) {
            return null;
        }
        
        $cache = json_decode(file_get_contents($this->cacheFile), true);
        
        if (!$cache) {
            return null;
        }
        
        $cacheDuration = $this->config['discovery']['cache_duration'] ?? 300;
        
        if (time() - ($cache['timestamp'] ?? 0) > $cacheDuration) {
            return null; // Cache abgelaufen
        }
        
        return $cache;
    }
    
    /**
     * Speichert Discovery-Ergebnis im Cache
     */
    private function cacheDiscovery(string $mode): void {
        $cache = [
            'mode' => $mode,
            'host' => $this->host,
            'timestamp' => time(),
        ];
        
        file_put_contents($this->cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
    }
    
    /**
     * Löscht den Discovery-Cache
     */
    public function clearCache(): void {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
    
    /**
     * Scannt Netzwerk nach bekannten BTCPay Servern
     * 
     * @return array Liste gefundener Server
     */
    public function discoverServers(): array {
        $knownServers = $this->config['discovery']['known_servers'] ?? [];
        $timeout = $this->config['discovery']['timeout'] ?? 5;
        $found = [];
        
        foreach ($knownServers as $server) {
            $host = $server['host'];
            $name = $server['name'] ?? $host;
            
            $result = $this->pingServer($host, $timeout);
            
            $found[] = [
                'host' => $host,
                'name' => $name,
                'reachable' => $result['reachable'],
                'response_time' => $result['response_time'] ?? null,
                'error' => $result['error'] ?? null,
            ];
        }
        
        return $found;
    }
    
    /**
     * Pingt einen Server um Erreichbarkeit zu prüfen
     */
    private function pingServer(string $host, int $timeout = 5): array {
        $start = microtime(true);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $host . '/api/v1/health',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_NOBODY => true,
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $responseTime = round((microtime(true) - $start) * 1000);
        
        if ($httpCode >= 200 && $httpCode < 500) {
            return [
                'reachable' => true,
                'response_time' => $responseTime,
            ];
        }
        
        return [
            'reachable' => false,
            'error' => $error ?: "HTTP $httpCode",
        ];
    }
    
    // ========================================================================
    // STATUS & INFO
    // ========================================================================
    
    /**
     * Prüft ob BTCPay aktiviert und konfiguriert ist
     */
    public function isEnabled(): bool {
        return ($this->config['enabled'] ?? false) 
            && !empty($this->apiKey) 
            && !empty($this->storeId)
            && $this->activeMode !== 'none';
    }
    
    /**
     * Gibt das aktive Netzwerk zurück
     */
    public function getNetwork(): string {
        return $this->network;
    }
    
    /**
     * Gibt den aktiven Modus zurück (external/local/none)
     */
    public function getActiveMode(): string {
        return $this->activeMode;
    }
    
    /**
     * Gibt den aktiven Host zurück
     */
    public function getHost(): string {
        return $this->host;
    }
    
    /**
     * Gibt den letzten Fehler zurück
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }
    
    /**
     * Gibt detaillierten Status zurück
     */
    public function getStatus(): array {
        return [
            'enabled' => $this->isEnabled(),
            'mode' => $this->activeMode,
            'host' => $this->host,
            'network' => $this->network,
            'api_key_set' => !empty($this->apiKey),
            'store_id_set' => !empty($this->storeId),
            'last_error' => $this->lastError,
        ];
    }
    
    // ========================================================================
    // API KOMMUNIKATION
    // ========================================================================
    
    /**
     * Führt API-Request aus
     */
    private function apiRequest(string $method, string $endpoint, ?array $data = null): array {
        if (empty($this->host)) {
            return ['success' => false, 'error' => 'Kein BTCPay Server konfiguriert'];
        }
        
        $url = $this->host . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: token ' . $this->apiKey,
        ];
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0,
        ]);
        
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            $this->lastError = "cURL Error: $error";
            return [
                'success' => false,
                'error' => $this->lastError,
                'http_code' => 0
            ];
        }
        
        $decoded = json_decode($response, true) ?? [];
        
        if ($httpCode >= 400) {
            $this->lastError = $decoded['message'] ?? "HTTP Error: $httpCode";
            return [
                'success' => false,
                'error' => $this->lastError,
                'http_code' => $httpCode,
                'response' => $decoded
            ];
        }
        
        return [
            'success' => true,
            'http_code' => $httpCode,
            'data' => $decoded
        ];
    }
    
    // ========================================================================
    // INVOICES (EINZAHLUNGEN)
    // ========================================================================
    
    /**
     * Erstellt eine Einzahlungs-Invoice
     */
    public function createDepositInvoice(int $amountSats, string $description = '', array $metadata = []): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'BTCPay nicht aktiviert'];
        }
        
        // Limits prüfen
        $minDeposit = $this->config['min_deposit_sats'] ?? 1000;
        $maxDeposit = $this->config['max_deposit_sats'] ?? 1000000;
        
        if ($amountSats < $minDeposit) {
            return ['success' => false, 'error' => "Minimum: $minDeposit Sats"];
        }
        
        if ($amountSats > $maxDeposit) {
            return ['success' => false, 'error' => "Maximum: $maxDeposit Sats"];
        }
        
        // Sats zu BTC
        $amountBTC = $amountSats / 100000000;
        
        $invoiceData = [
            'amount' => (string) $amountBTC,
            'currency' => 'BTC',
            'metadata' => array_merge([
                'type' => 'family_deposit',
                'sats' => $amountSats,
                'description' => $description ?: 'Family Wallet Einzahlung',
                'source' => 'sgit_education',
                'created_at' => date('Y-m-d H:i:s'),
            ], $metadata),
            'checkout' => [
                'speedPolicy' => 'MediumSpeed',
                'paymentMethods' => ['BTC', 'BTC-LightningNetwork'],
                'expirationMinutes' => $this->config['invoice_expiry_minutes'] ?? 30,
            ]
        ];
        
        $result = $this->apiRequest('POST', "/api/v1/stores/{$this->storeId}/invoices", $invoiceData);
        
        if (!$result['success']) {
            return $result;
        }
        
        $invoice = $result['data'];
        
        return [
            'success' => true,
            'invoice_id' => $invoice['id'],
            'checkout_link' => $invoice['checkoutLink'],
            'amount_sats' => $amountSats,
            'amount_btc' => $amountBTC,
            'status' => $invoice['status'],
            'expires_at' => $invoice['expirationTime'] ?? null,
            'payment_methods' => $invoice['checkout']['paymentMethods'] ?? [],
            'server_mode' => $this->activeMode,
            'server_host' => $this->host,
        ];
    }
    
    /**
     * Holt Invoice-Status
     */
    public function getInvoiceStatus(string $invoiceId): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'BTCPay nicht aktiviert'];
        }
        
        $result = $this->apiRequest('GET', "/api/v1/stores/{$this->storeId}/invoices/$invoiceId");
        
        if (!$result['success']) {
            return $result;
        }
        
        $invoice = $result['data'];
        
        return [
            'success' => true,
            'invoice_id' => $invoice['id'],
            'status' => $invoice['status'],
            'additional_status' => $invoice['additionalStatus'] ?? null,
            'amount_sats' => isset($invoice['metadata']['sats']) ? (int) $invoice['metadata']['sats'] : 0,
            'paid' => in_array($invoice['status'], ['Settled', 'Processing']),
            'expired' => $invoice['status'] === 'Expired',
        ];
    }
    
    /**
     * Listet alle Invoices
     */
    public function listInvoices(int $limit = 50, int $skip = 0): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'BTCPay nicht aktiviert'];
        }
        
        $result = $this->apiRequest('GET', "/api/v1/stores/{$this->storeId}/invoices?take=$limit&skip=$skip");
        
        if (!$result['success']) {
            return $result;
        }
        
        return [
            'success' => true,
            'invoices' => $result['data'],
            'count' => count($result['data'])
        ];
    }
    
    // ========================================================================
    // PAYOUTS (AUSZAHLUNGEN)
    // ========================================================================
    
    /**
     * Erstellt einen Payout an Lightning Address
     */
    public function createLightningPayout(string $lightningAddress, int $amountSats, string $description = ''): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'BTCPay nicht aktiviert'];
        }
        
        // Lightning Address validieren
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $lightningAddress)) {
            return ['success' => false, 'error' => 'Ungültige Lightning Address'];
        }
        
        // Limits prüfen
        $minWithdraw = $this->config['min_withdraw_sats'] ?? 100;
        
        if ($amountSats < $minWithdraw) {
            return ['success' => false, 'error' => "Minimum: $minWithdraw Sats"];
        }
        
        // Pull Payment erstellen
        $pullPaymentData = [
            'name' => $description ?: 'sgiT Education Auszahlung',
            'description' => "Auszahlung an $lightningAddress",
            'amount' => (string) ($amountSats / 100000000),
            'currency' => 'BTC',
            'paymentMethods' => ['BTC-LightningNetwork'],
            'autoApproveClaims' => true,
        ];
        
        $result = $this->apiRequest('POST', "/api/v1/stores/{$this->storeId}/pull-payments", $pullPaymentData);
        
        if (!$result['success']) {
            return $result;
        }
        
        return [
            'success' => true,
            'pull_payment_id' => $result['data']['id'],
            'amount_sats' => $amountSats,
            'destination' => $lightningAddress,
            'status' => 'created',
            'server_mode' => $this->activeMode,
        ];
    }
    
    // ========================================================================
    // WALLET & BALANCE
    // ========================================================================
    
    /**
     * Holt die Wallet Balance
     */
    public function getWalletBalance(): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'BTCPay nicht aktiviert'];
        }
        
        // On-Chain Balance
        $onChainResult = $this->apiRequest('GET', "/api/v1/stores/{$this->storeId}/payment-methods/onchain/BTC/wallet");
        
        // Lightning Balance
        $lightningResult = $this->apiRequest('GET', "/api/v1/stores/{$this->storeId}/lightning/BTC/balance");
        
        $onChainBalance = 0;
        $lightningBalance = 0;
        
        if ($onChainResult['success'] && isset($onChainResult['data']['balance'])) {
            $onChainBalance = (int) ($onChainResult['data']['balance'] * 100000000);
        }
        
        if ($lightningResult['success'] && isset($lightningResult['data']['balance'])) {
            $lightningBalance = (int) ($lightningResult['data']['balance'] * 100000000);
        }
        
        return [
            'success' => true,
            'on_chain_sats' => $onChainBalance,
            'lightning_sats' => $lightningBalance,
            'total_sats' => $onChainBalance + $lightningBalance,
            'on_chain_btc' => $onChainBalance / 100000000,
            'lightning_btc' => $lightningBalance / 100000000,
            'server_mode' => $this->activeMode,
        ];
    }
    
    // ========================================================================
    // WEBHOOKS
    // ========================================================================
    
    /**
     * Validiert Webhook-Signatur
     */
    public function validateWebhookSignature(string $payload, string $signature): bool {
        if (empty($this->webhookSecret)) {
            error_log("BTCPayManager: Webhook Secret nicht konfiguriert");
            return false;
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        return hash_equals('sha256=' . $expectedSignature, $signature);
    }
    
    /**
     * Verarbeitet Webhook-Payload
     */
    public function processWebhook(array $payload): array {
        $type = $payload['type'] ?? '';
        $invoiceId = $payload['invoiceId'] ?? '';
        
        switch ($type) {
            case 'InvoiceSettled':
            case 'InvoicePaymentSettled':
                return [
                    'action' => 'payment_received',
                    'invoice_id' => $invoiceId,
                    'status' => 'settled',
                    'amount_sats' => $payload['metadata']['sats'] ?? 0,
                ];
                
            case 'InvoiceExpired':
                return [
                    'action' => 'invoice_expired',
                    'invoice_id' => $invoiceId,
                    'status' => 'expired',
                ];
                
            case 'InvoiceProcessing':
                return [
                    'action' => 'payment_processing',
                    'invoice_id' => $invoiceId,
                    'status' => 'processing',
                ];
                
            default:
                return [
                    'action' => 'unknown',
                    'type' => $type,
                    'invoice_id' => $invoiceId,
                ];
        }
    }
    
    // ========================================================================
    // STORE & CONNECTION TEST
    // ========================================================================
    
    /**
     * Holt Store-Informationen
     */
    public function getStoreInfo(): array {
        if (empty($this->host) || empty($this->apiKey)) {
            return ['success' => false, 'error' => 'Nicht konfiguriert'];
        }
        
        $result = $this->apiRequest('GET', "/api/v1/stores/{$this->storeId}");
        
        if (!$result['success']) {
            return $result;
        }
        
        return [
            'success' => true,
            'store_id' => $result['data']['id'],
            'name' => $result['data']['name'],
            'website' => $result['data']['website'] ?? null,
        ];
    }
    
    /**
     * Testet die BTCPay Verbindung
     */
    public function testConnection(): array {
        if (empty($this->host)) {
            return [
                'success' => false,
                'error' => 'Kein Host konfiguriert',
            ];
        }
        
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Kein API Key konfiguriert',
                'host' => $this->host,
            ];
        }
        
        $storeResult = $this->getStoreInfo();
        
        if (!$storeResult['success']) {
            return [
                'success' => false,
                'error' => $storeResult['error'] ?? 'Verbindung fehlgeschlagen',
                'host' => $this->host,
                'mode' => $this->activeMode,
            ];
        }
        
        return [
            'success' => true,
            'message' => 'BTCPay Verbindung erfolgreich!',
            'host' => $this->host,
            'store_name' => $storeResult['name'],
            'network' => $this->network,
            'mode' => $this->activeMode,
        ];
    }
}
