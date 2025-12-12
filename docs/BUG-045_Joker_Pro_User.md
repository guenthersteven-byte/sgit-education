# BUG-045: Joker Global statt Pro User

| Info | Details |
|------|---------|
| **Priorität** | HOCH |
| **Entdeckt** | 12.12.2025 |
| **Behoben** | 12.12.2025 ✅ |
| **Version** | 3.29.0 → 3.29.1 |
| **Dateien** | `api/joker.php` (NEU), `adaptive_learning.php` |

## Problem

Die 50/50 Joker wurden in `localStorage` gespeichert, was bedeutete:
- **Alle User am gleichen Browser** teilten sich die Joker
- Kind A verbraucht Joker → Kind B hat auch keine mehr
- Geräteübergreifend gingen Joker verloren

## Lösung

### 1. Datenbank-Struktur (bereits vorhanden)
```sql
-- In wallet/wallet.db → child_wallets
joker_count INTEGER DEFAULT 3
joker_last_refill DATE
```

### 2. API-Endpoint: `/api/joker.php`

| Endpoint | Methode | Beschreibung |
|----------|---------|--------------|
| `?action=status` | GET | Joker-Count laden + Auto-Refill |
| `action=use` | POST | Joker verbrauchen (-1) |
| `action=refill` | POST | Manuelles Auffüllen (Admin) |

**Response-Format:**
```json
{
  "success": true,
  "joker_count": 3,
  "wallet_user": true,
  "refilled": false
}
```

### 3. JavaScript-Logik

**Wallet-User:** Joker werden über API in DB gespeichert
```javascript
await fetch('/api/joker.php', { 
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=use'
});
```

**Gast-User (Fallback):** Joker bleiben in localStorage
```javascript
localStorage.setItem('foxyJokerCount', jokerCount);
```

## Features

- ✅ **Pro User:** Jeder Wallet-User hat eigene Joker
- ✅ **Täglich 3 neue:** Automatisches Refill bei erstem Aufruf des Tages
- ✅ **Toast-Benachrichtigung:** "Joker aufgefüllt!" wenn Refill passiert
- ✅ **Offline-Fallback:** Gäste nutzen weiter localStorage
- ✅ **Geräteübergreifend:** Wallet-User haben überall gleichen Stand

## Test-Anleitung

1. Mit Wallet-User einloggen
2. Joker verwenden → Zähler sinkt
3. Browser-Console prüfen: `[DEBUG] Joker loaded: {wallet_user: true, ...}`
4. Mit anderem Wallet-User einloggen → Eigene Joker!
5. Nächsten Tag testen: Joker werden auf 3 aufgefüllt

## Dateien geändert

| Datei | Änderung |
|-------|----------|
| `api/joker.php` | NEU - Joker-API |
| `adaptive_learning.php` | JS: API-Calls statt localStorage |
| `includes/version.php` | 3.29.0 → 3.29.1 |

---
*Dokumentiert am 12.12.2025 - sgiT Education Platform*
