# BUG-013 Fix - Wallet-Registrierung

**Session:** 03.12.2025, 20:30 Uhr  
**Version:** 2.5.5  
**Status:** ✅ GELÖST

---

## 📋 Problembeschreibung

### Problem 1: Kaputter Link zum Eltern-Dashboard
- **Symptom:** Link `wallet_dashboard.php` in login.php führte zu 404
- **Ursache:** Datei existiert nicht, korrekt ist `wallet_admin.php`

### Problem 2: Fehlendes Geburtsdatum
- **Symptom:** Nur Alter-Feld im Registrierungsformular
- **Auswirkung:** Achievement "Geburtstags-Lerner" nicht möglich
- **Zusätzlich:** Altersbereich war auf 5-15 begrenzt (sollte 5-21 sein)

---

## ✅ Implementierte Lösungen

### 1. wallet/login.php
```diff
- <a href="wallet_dashboard.php">💰 Eltern-Dashboard</a>
+ <a href="../admin_v4.php" title="Eltern-Bereich über Admin-Login">👨‍👩‍👧 Eltern-Bereich</a>
```

### 2. wallet/register.php (komplett überarbeitet)
| Feature | Vorher | Nachher |
|---------|--------|---------|
| Alter | Zahlen-Input (5-15) | Datums-Picker (5-21 Jahre) |
| Berechnung | Manuell durch User | Auto aus Geburtsdatum |
| Schwierigkeitsstufen | 3 (Leicht, Mittel, Schwer) | 4 (+Experte für 17-21) |
| Eltern-Link | Fehlte | Hinzugefügt |

**Neue Schwierigkeitsstufen:**
| Alter | Level | Icon |
|-------|-------|------|
| 5-7 | Leicht | 🌱 |
| 8-12 | Mittel | 🌿 |
| 13-16 | Fortgeschritten | 🌳 |
| 17-21 | Experte | 🎓 |

### 3. wallet/WalletManager.php (v1.4)
```php
// Neuer Parameter in createChildWallet()
public function createChildWallet(
    string $name, 
    string $avatar = '👧', 
    ?int $age = null, 
    ?string $pin = null, 
    ?string $birthdate = null  // NEU!
)

// DB-Migration (automatisch)
ALTER TABLE child_wallets ADD COLUMN birthdate DATE
```

**Migrations-Logik:**
- Prüft ob `birthdate` Spalte existiert
- Fügt sie nur hinzu wenn sie fehlt
- Keine manuellen Schritte nötig

---

## 📁 Geänderte Dateien

| Datei | Änderung | Version |
|-------|----------|---------|
| `wallet/login.php` | Link korrigiert | - |
| `wallet/register.php` | Komplett überarbeitet | v1.1 |
| `wallet/WalletManager.php` | birthdate Support | v1.4 |
| `sgit_education_status_report.md` | Bug dokumentiert | v2.5.5 |

---

## 🧪 Test-Anleitung

### Test 1: Registrierung mit Geburtsdatum
1. Öffne http://localhost/Education/wallet/register.php
2. Gib Namen ein
3. Wähle Geburtsdatum (Picker)
4. Prüfe: Alter wird automatisch berechnet
5. Prüfe: Schwierigkeit wird angezeigt
6. Registrierung abschließen

### Test 2: Link zum Eltern-Bereich
1. Öffne http://localhost/Education/wallet/login.php
2. Klicke auf "👨‍👩‍👧 Eltern-Bereich"
3. Sollte zu admin_v4.php führen (Login erforderlich)

### Test 3: Datenbank-Migration
```bash
# In SQLite CLI oder PHP:
SELECT name FROM pragma_table_info('child_wallets') WHERE name='birthdate';
# Sollte: birthdate
```

---

## 🔮 Nächste Schritte

1. **Achievement "Geburtstags-Lerner" implementieren**
   - Prüfen ob `birthdate` = heute
   - Bonus-Sats vergeben
   - In AchievementManager.php

2. **Bestehende User migrieren**
   - Optional: Nachträgliche Geburtsdatum-Eingabe
   - Admin-Funktion zum Bearbeiten

---

## 📊 Zusammenfassung

| Metrik | Wert |
|--------|------|
| Betroffene Dateien | 4 |
| Neue Features | 2 (Geburtsdatum, erweiterter Altersbereich) |
| Bugfixes | 2 (Link, Validierung) |
| DB-Änderungen | 1 (birthdate Spalte) |
| Abwärtskompatibel | ✅ Ja |

**BUG-013: ✅ VOLLSTÄNDIG GELÖST**
