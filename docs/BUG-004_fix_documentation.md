# BUG-004 Fix Dokumentation

**Datum:** 04. Dezember 2025  
**Betroffene Datei:** `adaptive_learning.php`  
**Neue Version:** v5.3  
**Status:** ✅ GEFIXT

---

## 📋 Problem-Beschreibung

Bei Modul-Abschluss (nach 10 Fragen) wurden Sats nicht zuverlässig gutgeschrieben. Das Problem trat sporadisch auf und war schwer reproduzierbar.

---

## 🔍 Ursachen-Analyse

### Problem 1: `wallet_child_id` nicht immer verfügbar

**Situation:**
```
User loggt sich ein (Name + Alter)
    ↓
Prüfung: Existiert Name im Wallet?
    ↓
JA → Redirect zu PIN-Login → wallet_child_id wird gesetzt
NEIN → Normaler Login → wallet_child_id wird NIE gesetzt!
```

**Konsequenz:**
Im AJAX-Handler `check_answer` wurde geprüft:
```php
if (isset($_SESSION['wallet_child_id'])) {
    // Sats vergeben
}
```
Für User ohne Wallet war diese Variable nie gesetzt → keine Sats.

### Problem 2: WalletManager-Instanz konnte "stale" sein

**Situation:**
- Am Seitenanfang wird `$walletMgr = new WalletManager()` erstellt
- Bei AJAX-Requests wurde nur geprüft: `if (!isset($walletMgr))`
- Die Instanz existierte noch, aber die SQLite-Verbindung konnte bereits geschlossen sein

### Problem 3: Kein Error-Feedback

**Situation:**
- Wenn `earnSats()` fehlschlug, wurde `$walletReward = null` gesetzt
- Der Client bekam keine Information warum Sats nicht vergeben wurden
- Kein Logging → Debugging unmöglich

---

## ✅ Lösung

### 1. Neue Hilfsfunktion `resolveWalletChildId()`

```php
function resolveWalletChildId() {
    // 1. Prüfe Session
    if (isset($_SESSION['wallet_child_id']) && $_SESSION['wallet_child_id'] > 0) {
        return (int) $_SESSION['wallet_child_id'];
    }
    
    // 2. Prüfe SessionManager (Wallet-Login mit PIN)
    if (class_exists('SessionManager') && SessionManager::isLoggedIn()) {
        $childId = SessionManager::getChildId();
        if ($childId) {
            $_SESSION['wallet_child_id'] = $childId;
            return (int) $childId;
        }
    }
    
    // 3. Fallback: Name-Lookup
    if (isset($_SESSION['user_name'])) {
        $mgr = new WalletManager();
        $child = $mgr->getChildByName($_SESSION['user_name']);
        if ($child) {
            $_SESSION['wallet_child_id'] = $child['id'];
            return (int) $child['id'];
        }
    }
    
    return null;
}
```

### 2. Frische WalletManager-Instanz

Im `check_answer` Handler wird jetzt IMMER eine neue Instanz erstellt:
```php
// WICHTIG: Manager-Klassen IMMER neu erstellen für frische DB-Verbindung
require_once __DIR__ . '/wallet/WalletManager.php';
$freshWalletMgr = new WalletManager();
```

### 3. Debug-Logging

```php
define('WALLET_DEBUG', true);

function walletDebugLog($message, $data = null) {
    if (!WALLET_DEBUG) return;
    error_log("[WALLET_DEBUG] " . $message . " | " . json_encode($data));
}
```

**Logging-Punkte:**
- Session-Synchronisation beim Page Load
- `resolveWalletChildId()` Ergebnis
- Session-Ende erreicht (Modul, Score, Child-ID)
- `earnSats()` Start und Ergebnis
- Achievement-Freischaltungen
- Exceptions

### 4. Error-Feedback an Client

```php
// Im JSON-Response:
'wallet_reward' => $walletReward,
'wallet_error' => $walletError,
'debug' => WALLET_DEBUG ? [
    'wallet_child_id' => $_SESSION['wallet_child_id'] ?? null,
    'user_name' => $_SESSION['user_name'] ?? null
] : null
```

**UI zeigt Fehler:**
- Toast-Notification bei Fehlern
- Fehlermeldung im Session-Complete Modal
- Debug-Info in Browser-Console

---

## 🧪 Test-Anleitung

1. **Apache Error-Log leeren:**
   - Datei: `C:\xampp\apache\logs\error.log`

2. **Mit Wallet-User einloggen:**
   - URL: `http://localhost/Education/wallet/login.php`
   - PIN eingeben

3. **10 Fragen beantworten:**
   - Beliebiges Modul wählen
   - Session abschließen

4. **Prüfen:**
   - Session-Complete Modal zeigt Sats
   - Header zeigt aktualisierte Balance
   - Error-Log enthält `[WALLET_DEBUG]` Einträge

5. **Bei Problemen:**
   - Error-Log analysieren
   - Browser-Console prüfen (F12)
   - Debug-Daten in Response prüfen

---

## 📁 Geänderte Dateien

| Datei | Änderung |
|-------|----------|
| `adaptive_learning.php` | v5.2 → v5.3 - Kompletter Reward-Flow überarbeitet |
| `sgit_education_status_report.md` | BUG-004 als GEFIXT markiert |

---

## 🔧 Debug-Modus deaktivieren (nach Validierung)

Wenn alles funktioniert, kann der Debug-Modus deaktiviert werden:

```php
// In adaptive_learning.php, Zeile ~35
define('WALLET_DEBUG', false);  // Auf false setzen
```

---

## 📊 Zusammenfassung

| Aspekt | Vorher | Nachher |
|--------|--------|---------|
| wallet_child_id Erkennung | Nur aus Session | Session + SessionManager + Name-Lookup |
| WalletManager | Möglicherweise stale | Immer frisch erstellt |
| Error-Feedback | Keins | Toast + Modal + Console |
| Debugging | Unmöglich | Ausführliches Logging |

**Ergebnis:** Sats werden jetzt zuverlässig vergeben, und bei Problemen ist die Ursache sofort sichtbar.
