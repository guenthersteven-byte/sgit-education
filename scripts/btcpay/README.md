# BTCPay Server Setup - sgiT Education Platform

**Version:** 1.0  
**Datum:** 03.12.2025  
**Modus:** Regtest (Entwicklung)

---

## 📋 Übersicht

Dieses Setup startet einen kompletten BTCPay Server Stack für die lokale Entwicklung der sgiT Education Platform. **Es werden KEINE echten Bitcoin verwendet!**

### Komponenten

| Service | Container | Port | Beschreibung |
|---------|-----------|------|--------------|
| BTCPay Server | sgit-btcpayserver | 49392 | Web UI & API |
| Bitcoin Core | sgit-bitcoind | 18443, 18444 | Regtest Node |
| LND | sgit-lnd | 8080, 10009 | Lightning Network |
| NBXplorer | sgit-nbxplorer | 32838 | Blockchain Indexer |
| PostgreSQL | sgit-btcpay-postgres | - | Datenbank |

---

## 🚀 Schnellstart

### Voraussetzungen
- ✅ Docker Desktop installiert und gestartet
- ✅ WSL2 Ubuntu (für Docker Backend)
- ✅ PowerShell (als Admin für erste Ausführung)

### 1. BTCPay Server starten

```powershell
cd C:\xampp\htdocs\Education\scripts\btcpay
.\start_btcpay.ps1
```

### 2. Admin Account erstellen

1. Öffne http://localhost:49392
2. Klicke "Register" (erster User wird Admin)
3. E-Mail: `admin@sgit.local`
4. Passwort: Sicheres Passwort wählen

### 3. Store erstellen

1. Nach Login: "Create your first store"
2. Name: `sgiT Education`
3. Default Currency: `EUR` oder `SATS`
4. Preferred Price Source: Default lassen

### 4. API Key generieren

1. Account Settings (oben rechts) → API Keys
2. "Generate new key"
3. Permissions auswählen:
   - `btcpay.store.canviewinvoices`
   - `btcpay.store.cancreateinvoice`
   - `btcpay.store.canviewpaymentrequests`
   - `btcpay.store.canmanagepayouts`
4. Key kopieren und sicher speichern!

### 5. Konfiguration in PHP

Bearbeite `C:\xampp\htdocs\Education\wallet\btcpay_config.php`:

```php
return [
    'btcpay_url' => 'http://localhost:49392',
    'api_key' => 'DEIN_API_KEY_HIER',
    'store_id' => 'DEINE_STORE_ID_HIER',
    // ...
];
```

Die Store ID findest du unter: Store Settings → General → Store ID

---

## 🔧 Befehle

### BTCPay Server verwalten

```powershell
# Starten
.\start_btcpay.ps1

# Stoppen
.\stop_btcpay.ps1

# Logs anzeigen
docker-compose logs -f

# Logs einzelner Service
docker-compose logs -f btcpayserver
docker-compose logs -f bitcoind
docker-compose logs -f lnd
```

### Test-Bitcoin generieren

```powershell
# Standard: 101 Blöcke minen (mind. 100 für nutzbare Coins)
.\generate_test_btc.ps1

# Mehr Blöcke minen
.\generate_test_btc.ps1 -blocks 50

# An bestimmte Adresse minen
.\generate_test_btc.ps1 -address "bcrt1q..."
```

### Bitcoin CLI direkt nutzen

```powershell
# Balance prüfen
docker exec sgit-bitcoind bitcoin-cli -regtest -rpcuser=btcpay -rpcpassword=btcpay2025 getbalance

# Neue Adresse generieren
docker exec sgit-bitcoind bitcoin-cli -regtest -rpcuser=btcpay -rpcpassword=btcpay2025 getnewaddress

# Blockchain Info
docker exec sgit-bitcoind bitcoin-cli -regtest -rpcuser=btcpay -rpcpassword=btcpay2025 getblockchaininfo
```

---

## 🌐 URLs & Credentials

### Zugriff

| Service | URL |
|---------|-----|
| BTCPay Server | http://localhost:49392 |
| NBXplorer | http://localhost:32838 |
| LND REST API | https://localhost:8080 |

### Bitcoin RPC

| Parameter | Wert |
|-----------|------|
| Host | localhost |
| Port | 18443 |
| User | btcpay |
| Password | btcpay2025 |
| Network | regtest |

---

## 📁 Dateistruktur

```
C:\xampp\htdocs\Education\scripts\btcpay\
├── docker-compose.yml      # Haupt-Konfiguration
├── start_btcpay.ps1        # Start-Script
├── stop_btcpay.ps1         # Stop-Script
├── generate_test_btc.ps1   # Test-Bitcoin minen
└── README.md               # Diese Dokumentation
```

### Docker Volumes (persistente Daten)

| Volume | Beschreibung |
|--------|--------------|
| sgit-btcpay-postgres | PostgreSQL Daten |
| sgit-btcpay-bitcoin | Bitcoin Blockchain (Regtest) |
| sgit-btcpay-lnd | LND Daten + Macaroons |
| sgit-btcpay-nbxplorer | NBXplorer Index |
| sgit-btcpay-data | BTCPay Server Daten |

### Volumes löschen (kompletter Reset)

```powershell
docker-compose down -v
```

---

## 🔌 Integration mit sgiT Education

### API Endpunkte (BTCPayManager.php)

| Methode | Beschreibung |
|---------|--------------|
| `createDepositInvoice()` | Rechnung für Eltern-Einzahlung |
| `getInvoiceStatus()` | Status einer Rechnung prüfen |
| `createLightningPayout()` | Auszahlung an Kind (Lightning) |
| `getWalletBalance()` | Store Wallet Balance |
| `testConnection()` | Verbindung testen |

### Webhook Setup

1. BTCPay → Store Settings → Webhooks
2. Payload URL: `http://localhost/Education/wallet/btcpay_webhook.php`
3. Events: `InvoiceSettled`, `InvoicePaymentSettled`
4. Secret kopieren → in `btcpay_config.php` eintragen

---

## ⚠️ Wichtige Hinweise

### Regtest vs. Mainnet

| | Regtest | Mainnet |
|-|---------|---------|
| Echte BTC | ❌ Nein | ✅ Ja |
| Kosten | Keine | Real |
| Mining | Sofort möglich | Nicht möglich |
| Zweck | Entwicklung | Produktion |

### Bekannte Einschränkungen

1. **Erste Startup dauert länger** - Docker muss Images laden (~2-5 min)
2. **Nach Neustart PC** - Docker Desktop manuell starten
3. **Port-Konflikte** - Falls 49392 belegt, in docker-compose.yml ändern

---

## 🐛 Troubleshooting

### Container starten nicht

```powershell
# Alle Logs anzeigen
docker-compose logs

# Einzelnen Container neu starten
docker-compose restart btcpayserver
```

### BTCPay zeigt "Synchronizing"

Normal beim ersten Start. NBXplorer muss die (leere) Regtest-Blockchain indexieren.

```powershell
# Beschleunigen: Blöcke minen
.\generate_test_btc.ps1 -blocks 10
```

### Verbindung zu Bitcoin Core fehlgeschlagen

```powershell
# Container Status prüfen
docker ps

# Bitcoin Container Logs
docker logs sgit-bitcoind
```

### Alles zurücksetzen

```powershell
# Stoppen und Volumes löschen
docker-compose down -v

# Neu starten
.\start_btcpay.ps1
```

---

## 📝 Changelog

### v1.0 (03.12.2025)
- Initial Setup
- Docker Compose mit allen Services
- PowerShell Scripts für Start/Stop/Mining
- Dokumentation

---

**Erstellt von:** sgiT Solution Engineering  
**Projekt:** sgiT Education Platform  
**Kontakt:** sgit.space
