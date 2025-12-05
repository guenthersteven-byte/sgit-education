<?php
/**
 * ============================================================================
 * sgiT Education - BTCPay Konfiguration v1.1
 * ============================================================================
 * 
 * Unterstützt:
 * - Externen BTCPay Server im Netzwerk (z.B. StartOS)
 * - Lokale BTCPay Installation (Docker/Regtest)
 * - Automatische Erkennung
 * 
 * WICHTIG: Diese Datei enthält sensible Daten!
 * -> NIEMALS in Git committen!
 * -> .gitignore Eintrag: wallet/btcpay_config.php
 * 
 * @author sgiT Solution Engineering
 * @version 1.1
 * @date 02.12.2025
 * ============================================================================
 */

return [
    // ========================================================================
    // MODUS: external | local | auto
    // ========================================================================
    
    /**
     * Verbindungsmodus:
     * - 'external': Nutze externen BTCPay Server im Netzwerk (StartOS, Umbrel, etc.)
     * - 'local': Nutze lokale Docker-Installation
     * - 'auto': Versuche external, fallback auf local
     */
    'mode' => 'external',  // Wechsel zu StartOS BTCPay - TODO: Credentials eintragen
    
    // ========================================================================
    // EXTERNER SERVER (StartOS, Umbrel, eigener Server)
    // ========================================================================
    
    'external' => [
        /**
         * BTCPay Server URL im Netzwerk
         * Beispiele:
         * - StartOS: https://btcpay.local oder https://192.168.x.x:3003
         * - Umbrel: https://umbrel.local:3003
         * - Eigener Server: https://btcpay.meinedomain.de
         */
        'host' => 'https://btcpay.local',
        
        /**
         * API Key für externen Server
         * Generieren im BTCPay: Settings -> Access Tokens -> Generate
         */
        'api_key' => '',
        
        /**
         * Store ID
         * Zu finden: Settings -> General -> Store ID
         */
        'store_id' => '',
        
        /**
         * Webhook Secret (optional)
         * Für Signatur-Validierung bei Webhook-Calls
         */
        'webhook_secret' => '',
        
        /**
         * SSL Zertifikat verifizieren?
         * false für Self-Signed Certs (StartOS, lokales Netzwerk)
         * true für öffentliche Server mit gültigem Cert
         */
        'verify_ssl' => false,
        
        /**
         * Netzwerk des externen Servers
         * mainnet | testnet | signet
         */
        'network' => 'mainnet',
    ],
    
    // ========================================================================
    // LOKALER SERVER (Docker auf diesem PC)
    // ========================================================================
    
    'local' => [
        /**
         * DEAKTIVIERT - Wechsel zu StartOS
         * Docker-Setup war erfolgreich, aber wegen Speicherplatz
         * und vorhandener StartOS Instanz nicht weiter verfolgt.
         * 
         * Alte Credentials (Docker Regtest - 02.12.2025):
         * - Host: http://localhost:49392
         * - API Key: 8f90b0990d9186abb909e8ab181a1802b309f2dd
         * - Store ID: ADM6aowzMNQCEQ8DMWgcfRJQZDjxGVdqdLmVNVuEsosz
         * - Admin: admin@sgit.space / sgitbtcpayserver!1
         */
        'host' => '',
        'api_key' => '',
        'store_id' => '',
        'webhook_secret' => '',
        'verify_ssl' => false,
        'network' => 'regtest',
    ],
    
    // ========================================================================
    // NETZWERK-DISCOVERY (für 'auto' Modus)
    // ========================================================================
    
    'discovery' => [
        /**
         * Bekannte BTCPay Server im Netzwerk zum Testen
         * Format: ['host' => 'url', 'name' => 'Anzeigename']
         */
        'known_servers' => [
            ['host' => 'https://btcpay.local', 'name' => 'BTCPay Local'],
            ['host' => 'https://192.168.178.100:3003', 'name' => 'StartOS BTCPay'],
            // Weitere Server hier eintragen
        ],
        
        /**
         * Timeout für Server-Erkennung (Sekunden)
         */
        'timeout' => 5,
        
        /**
         * Cache-Dauer für Discovery-Ergebnis (Sekunden)
         */
        'cache_duration' => 300, // 5 Minuten
    ],
    
    // ========================================================================
    // WEBHOOK KONFIGURATION
    // ========================================================================
    
    /**
     * Webhook URL (für BTCPay Konfiguration)
     * Diese URL in BTCPay unter Settings -> Webhooks eintragen
     * 
     * Für externen Server muss diese URL von außen erreichbar sein!
     * Optionen:
     * - ngrok Tunnel
     * - Port-Forwarding
     * - Polling statt Webhook
     */
    'webhook_url' => 'http://localhost/Education/wallet/btcpay_webhook.php',
    
    /**
     * Polling als Fallback wenn Webhook nicht erreichbar
     */
    'use_polling_fallback' => true,
    
    /**
     * Polling-Intervall (Sekunden)
     */
    'polling_interval' => 30,
    
    // ========================================================================
    // SYSTEM-EINSTELLUNGEN
    // ========================================================================
    
    /**
     * BTCPay Integration aktiv?
     * false = Nur Test-Sats (SQLite)
     * true = Echte Sats via BTCPay
     */
    'enabled' => false,
    
    // ========================================================================
    // LIMITS & SICHERHEIT
    // ========================================================================
    
    /**
     * Minimum Einzahlung in Sats
     */
    'min_deposit_sats' => 1000,
    
    /**
     * Maximum Einzahlung in Sats
     */
    'max_deposit_sats' => 1000000,
    
    /**
     * Minimum Auszahlung in Sats
     */
    'min_withdraw_sats' => 100,
    
    /**
     * Maximum Auszahlung pro Tag in Sats
     */
    'max_withdraw_per_day_sats' => 10000,
    
    /**
     * Eltern-Genehmigung für Auszahlungen erforderlich?
     */
    'require_parent_approval' => true,
    
    /**
     * Invoice Ablaufzeit in Minuten
     */
    'invoice_expiry_minutes' => 30,
];
