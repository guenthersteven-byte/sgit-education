# sgiT Education - BTCPay Server Integration

**Erstellt:** 02. Dezember 2025  
**Version:** 1.0  
**Status:** In Entwicklung  
**Geschätzter Aufwand:** 3-5 Tage  
**Autor:** sgiT Solution Engineering

---

## 📋 EXECUTIVE SUMMARY

Integration von BTCPay Server in die sgiT Education Platform, um das bestehende Test-Sats System mit echten Bitcoin-Transaktionen zu erweitern. Eltern können echte Sats einzahlen, Kinder verdienen diese durch Lernerfolge und können sie auf echte Lightning Wallets auszahlen.

---

## 🎯 ZIELE

| Ziel | Beschreibung | Priorität |
|------|--------------|-----------|
| **Echte Einzahlung** | Eltern laden Family Wallet mit echten Sats auf | ⭐⭐⭐ |
| **Echte Auszahlung** | Kinder können verdiente Sats auf Lightning Wallet auszahlen | ⭐⭐⭐ |
| **Kurs-Tracking** | Live BTC/EUR Kurs für Anzeige | ⭐⭐ |
| **Self-Custody** | Eigener Node = Eigene Keys | ⭐⭐ |
| **Hybrid-Modus** | Test-Sats UND echte Sats parallel möglich | ⭐⭐ |

---

## 🏗️ ARCHITEKTUR-OPTIONEN

### Option A: Externer BTCPay Server (StartOS/Umbrel) ⭐ EMPFOHLEN

```
┌─────────────────────────────────────────────────────────────┐
│                    LOKALES NETZWERK                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐      ┌─────────────────────┐              │
│  │   XAMPP     │      │   StartOS/Umbrel    │              │
│  │  ┌───────┐  │      │  ┌───────────────┐  │              │
│  │  │ PHP   │  │ API  │  │ BTCPay Server │  │              │
│  │  │ sgiT  │◄─┼──────┼──► (bereits      │  │              │
│  │  │ Edu   │  │      │  │  installiert) │  │              │
│  │  └───────┘  │      │  └───────────────┘  │              │
│  │  ┌───────┐  │      │  ┌───────────────┐  │              │
│  │  │SQLite │  │      │  │ Bitcoin Node  │  │              │
│  │  └───────┘  │      │  │ + Lightning   │  │              │
│  └─────────────┘      │  └───────────────┘  │              │
│                       └─────────────────────┘              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Vorteile:**
- ✅ Self-Custody (eigene Keys auf eigenem Server)
- ✅ Bereits laufender Full Node
- ✅ Lightning Channels bereits konfiguriert
- ✅ Kein zusätzlicher Setup-Aufwand
- ✅ Mainnet-ready

**Nachteile:**
- ⚠️ Erfordert StartOS/Umbrel im Netzwerk
- ⚠️ Server muss erreichbar sein

**Setup:**
1. BTCPay Server URL notieren (z.B. `https://btcpay.local` oder IP)
2. API Key in BTCPay generieren
3. In sgiT Education unter Wallet Admin → BTCPay Setup eintragen

---

### Option B: BTCPay Server Self-Hosted (Docker)

```
┌─────────────────────────────────────────────────────────────┐
│                    LOKALES NETZWERK                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐      ┌─────────────────────┐              │
│  │   XAMPP     │      │   Docker            │              │
│  │  ┌───────┐  │      │  ┌───────────────┐  │              │
│  │  │ PHP   │  │ API  │  │ BTCPay Server │  │              │
│  │  │ sgiT  │◄─┼──────┼──► (+ LND/CLN)   │  │              │
│  │  │ Edu   │  │      │  │               │  │              │
│  │  └───────┘  │      │  └───────────────┘  │              │
│  │  ┌───────┐  │      │  ┌───────────────┐  │              │
│  │  │SQLite │  │      │  │ Bitcoin Core  │  │              │
│  │  └───────┘  │      │  │  (Pruned)     │  │              │
│  └─────────────┘      │  └───────────────┘  │              │
│                       └─────────────────────┘              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Vorteile:**
- ✅ Self-Custody (eigene Keys)
- ✅ Keine laufenden Kosten
- ✅ Volle Kontrolle
- ✅ Offline-fähig (für interne Transaktionen)
- ✅ Greenfield API (moderne REST API)

**Nachteile:**
- ⚠️ Initial-Setup aufwändiger (Docker, ~400GB für Full Node)
- ⚠️ Lightning Channel Management erforderlich
- ⚠️ Wartung & Updates

**Ressourcen-Anforderungen:**
- CPU: 2+ Cores
- RAM: 4+ GB
- Storage: ~15GB (Pruned Node) oder ~400GB (Full Node)
- Ports: 443 (HTTPS), 9735 (Lightning P2P)

---

### Option B: BTCPay mit Blink Plugin (Custodial)

```
┌─────────────────┐         ┌─────────────────┐
│   XAMPP         │   API   │  Blink.sv       │
│   sgiT Edu      │◄───────►│  (Custodial)    │
│   PHP           │         │  Lightning      │
└─────────────────┘         └─────────────────┘
```

**Vorteile:**
- ✅ Schnelles Setup (5 Minuten)
- ✅ Kein Node-Management
- ✅ Stablesats Option (USD-pegged)

**Nachteile:**
- ❌ Custodial (nicht deine Keys)
- ❌ Abhängigkeit von Drittanbieter
- ❌ Mögliche Gebühren

---

### Option C: LNbits (Leichtgewicht)

```
┌─────────────────┐         ┌─────────────────┐
│   XAMPP         │   API   │  LNbits         │
│   sgiT Edu      │◄───────►│  (Docker)       │
│   PHP           │         │  + Funding      │
└─────────────────┘         └─────────────────┘
```

**Vorteile:**
- ✅ Leichtgewichtig
- ✅ Viele Extensions (Cards, LNURL, etc.)
- ✅ Kann mit externem Node verbunden werden

**Nachteile:**
- ⚠️ Weniger Features als BTCPay
- ⚠️ Eigener Funding-Source nötig

---

## 🔧 EMPFOHLENE LÖSUNG: Option A (BTCPay Self-Hosted)

### Phase 1: BTCPay Server Setup (Tag 1)

#### 1.1 Docker Installation (Windows/WSL2)

```bash
# WSL2 mit Ubuntu installieren (falls nicht vorhanden)
wsl --install

# Docker Desktop installieren
# https://www.docker.com/products/docker-desktop/

# BTCPay Server Docker Repo klonen
git clone https://github.com/btcpayserver/btcpayserver-docker
cd btcpayserver-docker

# Konfiguration für lokale Entwicklung
export BTCPAY_HOST="btcpay.local"
export NBITCOIN_NETWORK="mainnet"  # oder "testnet" für Tests
export BTCPAYGEN_CRYPTO1="btc"
export BTCPAYGEN_LIGHTNING="lnd"
export BTCPAYGEN_REVERSEPROXY="nginx"

# Installation starten
./btcpay-setup.sh -i
```

#### 1.2 Alternative: Regtest für Entwicklung

```bash
# Regtest = Lokales Testnetz (keine echten BTC)
export NBITCOIN_NETWORK="regtest"
export BTCPAYGEN_ADDITIONAL_FRAGMENTS="opt-add-regtest"

# Ideal für Entwicklung!
```

#### 1.3 hosts-Datei anpassen (Windows)

```
# C:\Windows\System32\drivers\etc\hosts
127.0.0.1 btcpay.local
```

---

### Phase 2: Greenfield API Integration (Tag 2-3)

#### 2.1 PHP Client Installation

```bash
# Im Education-Verzeichnis
cd C:\xampp\htdocs\Education
composer require btcpayserver/btcpayserver-greenfield-php
```

#### 2.2 Neue Dateien

| Datei | Beschreibung |
|-------|--------------|
| `wallet/BTCPayManager.php` | Zentrale Klasse für BTCPay API |
| `wallet/btcpay_config.php` | Konfiguration (API Keys, Host) |
| `wallet/btcpay_webhook.php` | Webhook Handler für Zahlungen |
| `wallet/deposit.php` | Einzahlungs-Seite (Invoice erstellen) |
| `wallet/withdraw.php` | Auszahlungs-Seite (Lightning Pay) |

#### 2.3 BTCPayManager.php Struktur

```php
<?php
/**
 * sgiT Education - BTCPayManager
 * 
 * Zentrale Klasse für BTCPay Server Integration
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BTCPayServer\Client\Invoice;
use BTCPayServer\Client\Store;
use BTCPayServer\Client\StorePaymentMethod;
use BTCPayServer\Client\Webhook;

class BTCPayManager {
    
    private string $host;
    private string $apiKey;
    private string $storeId;
    private ?Invoice $invoiceClient = null;
    
    public function __construct() {
        $config = require __DIR__ . '/btcpay_config.php';
        $this->host = $config['host'];
        $this->apiKey = $config['api_key'];
        $this->storeId = $config['store_id'];
    }
    
    /**
     * Erstellt eine Einzahlungs-Invoice für das Family Wallet
     */
    public function createDepositInvoice(int $amountSats, string $description = ''): array {
        // TODO: Implementation
    }
    
    /**
     * Zahlt Sats an eine Lightning Address aus
     */
    public function payoutToLightning(string $lightningAddress, int $amountSats): array {
        // TODO: Implementation
    }
    
    /**
     * Prüft Invoice-Status
     */
    public function getInvoiceStatus(string $invoiceId): array {
        // TODO: Implementation
    }
    
    /**
     * Holt aktuelle Wallet-Balance
     */
    public function getWalletBalance(): array {
        // TODO: Implementation
    }
}
```

---

### Phase 3: UI Integration (Tag 3-4)

#### 3.1 Wallet Admin Erweiterung

```
wallet_admin.php v1.4
├── [BESTEHENDES BLEIBT]
├── NEU: "₿ Echte Sats" Toggle
├── NEU: BTCPay Status Anzeige
├── NEU: "Aufladen" Button → deposit.php
└── NEU: Auszahlungs-Genehmigung für Kinder
```

#### 3.2 Deposit Flow (Eltern)

```
┌─────────────────────────────────────────────────────────────┐
│  ₿ Family Wallet Aufladen                                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Aktueller Stand: 5.000 Test-Sats                          │
│  Echte Sats:      0 Sats                                   │
│                                                             │
│  ┌─────────────────────────────────────────┐               │
│  │  Betrag eingeben:                       │               │
│  │  [10.000] Sats                          │               │
│  │                                         │               │
│  │  ≈ 8,50 EUR (bei BTC = 85.000€)        │               │
│  │                                         │               │
│  │  [⚡ Mit Lightning bezahlen]            │               │
│  │  [🔗 Mit On-Chain bezahlen]             │               │
│  └─────────────────────────────────────────┘               │
│                                                             │
│  ⚠️ Minimum: 1.000 Sats | Maximum: 1.000.000 Sats          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### 3.3 Withdraw Flow (Kinder)

```
┌─────────────────────────────────────────────────────────────┐
│  ₿ Meine Sats auszahlen                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Dein Guthaben: 2.345 Sats                                 │
│                                                             │
│  ┌─────────────────────────────────────────┐               │
│  │  Auszahlen an:                          │               │
│  │  [emma@walletofsatoshi.com           ]  │               │
│  │  (Lightning Address)                    │               │
│  │                                         │               │
│  │  Betrag: [1.000] Sats                   │               │
│  │                                         │               │
│  │  [📤 Auszahlung beantragen]             │               │
│  └─────────────────────────────────────────┘               │
│                                                             │
│  ℹ️ Deine Eltern müssen die Auszahlung genehmigen         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### 3.4 Eltern-Genehmigung

```
┌─────────────────────────────────────────────────────────────┐
│  ⏳ Ausstehende Auszahlungen                                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  👧 Emma möchte 1.000 Sats auszahlen                       │
│  An: emma@walletofsatoshi.com                              │
│  Datum: 02.12.2025, 15:30                                  │
│                                                             │
│  [✅ Genehmigen]  [❌ Ablehnen]                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### Phase 4: Webhook & Automatisierung (Tag 4-5)

#### 4.1 Webhook Handler

```php
// wallet/btcpay_webhook.php
<?php
/**
 * BTCPay Webhook Handler
 * 
 * Empfängt Zahlungsbenachrichtigungen von BTCPay Server
 */

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_BTCPAY_SIG'] ?? '';

// Signatur verifizieren
// Invoice Status updaten
// Family Wallet Balance erhöhen
// Transaktion loggen
```

#### 4.2 Automatische Balance-Sync

```php
// Cron-Job oder bei jedem Login
public function syncBalance(): void {
    // BTCPay Wallet Balance holen
    // Mit SQLite Family Wallet abgleichen
    // Differenzen als Transaktionen loggen
}
```

---

## 📊 DATENBANK-ERWEITERUNGEN

### Neue Tabellen

```sql
-- BTCPay Invoices (Einzahlungen)
CREATE TABLE btcpay_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id TEXT UNIQUE NOT NULL,
    amount_sats INTEGER NOT NULL,
    status TEXT DEFAULT 'new',
    payment_method TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME,
    metadata TEXT
);

-- Withdrawal Requests (Auszahlungen)
CREATE TABLE withdrawal_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    child_id INTEGER NOT NULL,
    amount_sats INTEGER NOT NULL,
    lightning_address TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    approved_by TEXT,
    approved_at DATETIME,
    paid_at DATETIME,
    payment_hash TEXT,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES child_wallets(id)
);

-- BTCPay Config
CREATE TABLE btcpay_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    config_key TEXT UNIQUE NOT NULL,
    config_value TEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Erweiterte child_wallets Tabelle

```sql
ALTER TABLE child_wallets ADD COLUMN real_sats INTEGER DEFAULT 0;
ALTER TABLE child_wallets ADD COLUMN test_sats INTEGER DEFAULT 0;
-- balance_sats = real_sats + test_sats (View oder calculated)
```

---

## 🔒 SICHERHEIT

### Kritische Punkte

| Aspekt | Maßnahme |
|--------|----------|
| **API Key** | Nur in `btcpay_config.php`, nie in Git |
| **Webhook** | Signatur-Validierung PFLICHT |
| **Auszahlung** | Eltern-Genehmigung erforderlich |
| **Limits** | Max. Einzahlung/Auszahlung pro Tag |
| **Logging** | Alle Transaktionen protokollieren |

### API Key Scopes (Minimal)

```
- btcpay.store.canviewinvoices
- btcpay.store.cancreateinvoice
- btcpay.store.canviewstoresettings
- btcpay.store.cancreatepullpayments (für Auszahlungen)
```

---

## 💰 KOSTEN-KALKULATION

### Self-Hosted (Option A)

| Posten | Kosten |
|--------|--------|
| Docker/WSL | 0 € |
| Domain (optional) | 0-15 €/Jahr |
| SSL (Let's Encrypt) | 0 € |
| Strom | ~5-10 €/Monat |
| **Gesamt** | **0-15 €/Jahr** |

### Custodial (Option B/C)

| Posten | Kosten |
|--------|--------|
| Blink | 0 € (aber custodial) |
| Strike | Evtl. Gebühren |

---

## 📅 IMPLEMENTATION ROADMAP

### Tag 1: Setup & Grundlagen
- [ ] Docker/BTCPay Server installieren (oder Regtest)
- [ ] API Key generieren
- [ ] Composer Dependencies installieren
- [ ] `btcpay_config.php` erstellen

### Tag 2: Core Integration
- [ ] `BTCPayManager.php` implementieren
- [ ] Invoice-Erstellung testen
- [ ] Datenbank-Schema erweitern

### Tag 3: Deposit Flow
- [ ] `deposit.php` UI erstellen
- [ ] Webhook Handler implementieren
- [ ] Balance-Sync implementieren

### Tag 4: Withdraw Flow
- [ ] `withdraw.php` für Kinder
- [ ] Approval-System für Eltern
- [ ] Lightning Payout implementieren

### Tag 5: Polish & Testing
- [ ] UI-Verbesserungen
- [ ] Error Handling
- [ ] Dokumentation
- [ ] End-to-End Tests

---

## 🧪 TESTPLAN

### Regtest (Empfohlen für Entwicklung)

```bash
# Regtest-Sats generieren
bitcoin-cli -regtest generatetoaddress 101 <address>

# Kostenlose Test-Transaktionen!
```

### Testnet (Optional)

- Testnet-Sats von Faucets
- Echte Lightning-Channels (Testnet)

### Mainnet (Produktion)

- Mit kleinen Beträgen starten (100-1000 Sats)
- Monitoring einrichten

---

## 🔄 MIGRATION BESTEHENDER DATEN

### Test-Sats bleiben erhalten!

```
Bestehendes System:
- test_sats (SQLite) → Bleibt unverändert

Neues System:
- real_sats (BTCPay + SQLite) → Zusätzlich

Anzeige:
- "Test-Sats: 5.000"
- "Echte Sats: 0"
- "Gesamt: 5.000"
```

---

## ⚠️ RISIKEN & MITIGATION

| Risiko | Wahrscheinlichkeit | Impact | Mitigation |
|--------|-------------------|--------|------------|
| BTCPay Setup scheitert | Mittel | Hoch | Fallback auf Blink |
| Lightning Channel Issues | Mittel | Mittel | Gute Dokumentation |
| Webhook nicht erreichbar | Niedrig | Hoch | Polling als Backup |
| Kinder verlieren Sats | Niedrig | Hoch | Approval-System |

---

## 📚 RESSOURCEN

- **BTCPay Docs:** https://docs.btcpayserver.org/
- **Greenfield API:** https://docs.btcpayserver.org/API/Greenfield/v1/
- **PHP Client:** https://github.com/btcpayserver/btcpayserver-greenfield-php
- **Docker Setup:** https://docs.btcpayserver.org/Docker/

---

## ✅ ENTSCHEIDUNG

**Empfehlung:** Option A (BTCPay Self-Hosted) mit Regtest für Entwicklung

**Begründung:**
1. Self-Custody = Eigene Keys = Maximale Sicherheit
2. Keine laufenden Kosten
3. Lerneffekt für Kinder (echtes Bitcoin-Setup)
4. Regtest ermöglicht risiko-freies Entwickeln

**Nächster Schritt:** Docker/BTCPay Setup starten

---

*Dokument erstellt für sgiT Education Platform*  
*sgiT Solution Engineering & IT Services*
