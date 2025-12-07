# 🔧 Sats-Vergabe Fix für neue Module

**Datum:** 07. Dezember 2025  
**Version:** 3.14.4  
**Autor:** Claude (AI Assistant)

---

## 📋 Zusammenfassung

Alle neuen interaktiven Module (Kochen, Logik, Zeichnen) hatten einen kritischen Bug: **Sats wurden nicht zum Wallet hinzugefügt**, obwohl die Antworten richtig waren.

---

## 🔍 Ursachenanalyse

### Problem 1: Falsche Session-Variable (Kochen & Logik)

Die Module verwendeten:
```php
$childId = $_SESSION['child_id'] ?? $_SESSION['user_id'] ?? 0;
```

Das Wallet-System erwartet jedoch:
```php
$childId = $_SESSION['wallet_child_id'] ?? 0;
```

**Ergebnis:** `childId = 0` → API-Call wurde ignoriert

### Problem 2: Falscher Datenbankzugriff (Zeichnen)

Das Zeichnen-Modul versuchte direkt in die Datenbank zu schreiben:
```php
// FALSCH: user_id Spalte existiert nicht in child_wallets!
$stmt = $db->prepare("SELECT id FROM child_wallets WHERE user_id = ?");
```

Die `child_wallets` Tabelle hat keine `user_id` Spalte.

---

## ✅ Lösung

### Kochen & Logik Module (6 Dateien)

Geändert in allen 6 Dateien:
```php
// ALT (falsch)
$childId = $_SESSION['child_id'] ?? $_SESSION['user_id'] ?? 0;

// NEU (korrekt)
$childId = $_SESSION['wallet_child_id'] ?? 0;
```

**Betroffene Dateien:**
- `/kochen/quiz.php`
- `/kochen/zuordnen.php`
- `/kochen/kuechenwissen.php`
- `/logik/muster.php`
- `/logik/ausreisser.php`
- `/logik/zahlenreihe.php`

### Zeichnen Modul (1 Datei)

Komplette Überarbeitung von `/zeichnen/save_drawing.php`:
- Session-Variable auf `wallet_child_id` geändert
- Direkter DB-Zugriff durch WalletManager ersetzt
- Nutzt jetzt die offizielle `earnSats()` Methode

```php
// NEU: Korrekte Wallet-Integration
$childId = $_SESSION['wallet_child_id'] ?? 0;

if ($childId > 0) {
    require_once __DIR__ . '/../wallet/WalletManager.php';
    $wallet = new WalletManager();
    $result = $wallet->earnSats($childId, 1, 1, 'zeichnen_' . ($tutorial ?: 'free'));
}
```

---

## 📊 Verifizierung

```bash
# Alle Dateien nutzen jetzt wallet_child_id
docker exec sgit_php grep -l "wallet_child_id" \
    /var/www/html/kochen/*.php \
    /var/www/html/logik/*.php \
    /var/www/html/zeichnen/save_drawing.php
```

**Ergebnis:** 7 Dateien korrekt aktualisiert ✅

---

## 🧪 Test-Anleitung

1. Als Kind einloggen (z.B. Colin)
2. Wallet-Balance notieren
3. Ein Quiz im Kochen-Modul spielen
4. Eine Frage richtig beantworten
5. Wallet-Balance erneut prüfen → sollte höher sein

---

## 📝 Changelog

| Version | Änderung |
|---------|----------|
| 3.14.4 | Zeichnen-Modul: WalletManager Integration |
| 3.14.3 | Kochen & Logik: wallet_child_id Fix |
| 3.14.2 | Kochen: Design an CI angepasst |
| 3.14.1 | Kochen: onclick Bug gefixt |
| 3.14.0 | Kochen-Modul erstellt (21/21 Module) |

---

## ⚠️ Wichtige Hinweise für zukünftige Module

Bei neuen interaktiven Modulen **IMMER**:

1. `$childId = $_SESSION['wallet_child_id'] ?? 0;` verwenden
2. Wallet-API nutzen: `fetch('/wallet/api.php?action=earn', ...)`
3. Oder WalletManager: `$wallet->earnSats($childId, $score, $maxScore, $module)`
4. **NIEMALS** direkt in `child_wallets` Tabelle schreiben

---

*Dokument erstellt: 07.12.2025 14:45 Uhr*
