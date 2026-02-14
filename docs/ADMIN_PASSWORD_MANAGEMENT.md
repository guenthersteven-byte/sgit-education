# 🔐 Admin-Passwort Verwaltung - sgiT Education Platform

**Version:** 1.0  
**Datum:** 21. Dezember 2025  
**Aktuelles Passwort:** `sgit2025`

---

## 📍 Wo ist das Passwort gespeichert?

Das Admin-Passwort ist aktuell in **7 verschiedenen PHP-Dateien** als Konstante `ADMIN_PASSWORD` hardcoded:

### 1️⃣ Haupt-Admin-Dashboard
**Datei:** `admin_v4.php`  
**Zeile:** 26  
```php
define('ADMIN_PASSWORD', 'sgit2025');
```
**Funktion:** Zentrale Admin-Authentifizierung für das Haupt-Dashboard

---

### 2️⃣ Flag Cleanup Admin
**Datei:** `admin_cleanup_flags.php`  
**Zeile:** 27  
```php
define('ADMIN_PASSWORD', 'sgit2025');
```
**Funktion:** Verwaltung von gemeldeten Fragen  
**Hinweis:** Nutzt Session-Check von admin_v4.php, Konstante nicht aktiv verwendet

---

### 3️⃣ Backup Configuration Admin
**Datei:** `backup_config_admin.php`  
**Zeile:** 13  
```php
define('ADMIN_PASSWORD', 'sgit2025');
```
**Funktion:** Eigenes Login für Backup-Konfiguration

---

### 4️⃣ Backup Manager
**Datei:** `backup_manager.php`  
**Zeile:** 28  
```php
define('ADMIN_PASSWORD', 'sgit2025');
```
**Funktion:** Backup-Ausführung mit Admin-Authentifizierung

---

### 5️⃣ Debug Users Interface
**Datei:** `debug_users.php`  
**Zeile:** 26  
```php
$adminPassword = 'sgit2025';
```
**Funktion:** Debug-Interface für User-Verwaltung  
**Hinweis:** Nutzt Variable statt Konstante

---

### 6️⃣ Bot Summary Dashboard
**Datei:** `bots/bot_summary.php`  
**Zeile:** 25  
```php
$adminPassword = 'sgit2025';
```
**Funktion:** Eigenes Login für Bot-Dashboard  
**Hinweis:** Nutzt Variable statt Konstante

---

### 7️⃣ Bot Scheduler UI
**Datei:** `bots/scheduler/scheduler_ui.php`  
**Zeile:** 25  
```php
$adminPassword = 'sgit2025';
```
**Funktion:** Eigenes Login für Bot-Scheduler  
**Hinweis:** Nutzt Variable statt Konstante

---

## 🔧 Wie ändere ich das Passwort?

### Methode 1: Manuelles Ändern (Aktuell)

**WICHTIG:** Du musst das Passwort in **ALLEN 7 Dateien** ändern!

#### Schritt-für-Schritt Anleitung:

1. **Neues Passwort festlegen** (z.B. `MeinSicheresPasswort2025!`)

2. **Desktop Commander nutzen für schnelles Editieren:**
   ```
   Für jede Datei edit_block verwenden mit:
   - old_string: 'sgit2025'
   - new_string: 'MeinSicheresPasswort2025!'
   ```

3. **Dateien in dieser Reihenfolge ändern:**
   - ✅ `admin_v4.php` (Zeile 26)
   - ✅ `admin_cleanup_flags.php` (Zeile 27)
   - ✅ `backup_config_admin.php` (Zeile 13)
   - ✅ `backup_manager.php` (Zeile 28)
   - ✅ `debug_users.php` (Zeile 26)
   - ✅ `bots/bot_summary.php` (Zeile 25)
   - ✅ `bots/scheduler/scheduler_ui.php` (Zeile 25)

4. **Test:** Nach jeder Änderung die entsprechende Seite aufrufen und Login testen

---

## ⚠️ WICHTIGE SICHERHEITSHINWEISE

### Probleme der aktuellen Lösung:
- ❌ Passwort in **7 Dateien** verteilt → Fehleranfällig
- ❌ Hardcoded im Quellcode → Git-Repository enthält Passwort
- ❌ Bei Passwortänderung alle Dateien anpassen
- ❌ Kein Hashing → Passwort im Klartext

### Aktuelle Sicherheitsmaßnahmen:
- ✅ Rate-Limiting in admin_v4.php (5 Versuche/Minute)
- ✅ Session-basierte Authentifizierung
- ✅ HTTPS wird empfohlen (Production)

---

## 🚀 EMPFEHLUNG: Zentrale Passwort-Verwaltung

### Best Practice Implementation

Erstelle eine zentrale Konfigurationsdatei:

**Neue Datei:** `/includes/auth_config.php`
```php
<?php
/**
 * Zentrale Admin-Authentifizierung
 * NICHT IN GIT COMMITTEN!
 */

// Passwort-Hash (verwende password_hash() für Produktion)
define('ADMIN_PASSWORD_HASH', password_hash('MeinSicheresPasswort2025!', PASSWORD_DEFAULT));

// Oder für aktuelle Simple-Lösung:
define('ADMIN_PASSWORD', 'MeinSicheresPasswort2025!');
```

**Dann in allen 7 Dateien:**
```php
require_once __DIR__ . '/includes/auth_config.php';
// Entferne define('ADMIN_PASSWORD', '...');
```

**In `.gitignore` hinzufügen:**
```
/includes/auth_config.php
```

### Vorteile:
- ✅ **Einmalige Änderung** statt 7 Dateien
- ✅ **Nicht im Git** → Kein Passwort in Repository
- ✅ **Einfach zu warten**
- ✅ **Bereit für Hashing** → Mehr Sicherheit

---

## 📋 CHECKLISTE: Passwort ändern

Wenn du das Passwort änderst, arbeite diese Liste ab:

- [ ] Neues Passwort festgelegt
- [ ] `admin_v4.php` geändert
- [ ] `admin_cleanup_flags.php` geändert
- [ ] `backup_config_admin.php` geändert
- [ ] `backup_manager.php` geändert
- [ ] `debug_users.php` geändert
- [ ] `bots/bot_summary.php` geändert
- [ ] `bots/scheduler/scheduler_ui.php` geändert
- [ ] Admin-Dashboard Login getestet
- [ ] Backup Config Login getestet
- [ ] Bot Dashboard Login getestet
- [ ] Bot Scheduler Login getestet
- [ ] Debug Users Login getestet
- [ ] Status-Report aktualisiert (Quick Start Sektion)
- [ ] Dokumentation aktualisiert

---

## 🔍 Schnellsuche für Passwort-Vorkommen

Falls du prüfen willst, ob du alle erwischt hast:

**Desktop Commander Search:**
```
searchType: "content"
pattern: "sgit2025" (oder dein altes Passwort)
literalSearch: true
filePattern: "*.php"
```

**Erwartetes Ergebnis:** 0 Treffer (nach erfolgreicher Änderung)

---

## 📝 Nächste Schritte (Empfohlen)

### Kurzfristig:
1. Passwort in allen 7 Dateien auf ein sicheres Passwort ändern
2. Status-Report aktualisieren (Quick Start Sektion)

### Mittelfristig:
3. Zentrale `/includes/auth_config.php` implementieren
4. Alle 7 Dateien auf zentrale Config umstellen
5. `.gitignore` erweitern

### Langfristig:
6. Password-Hashing implementieren (password_hash/password_verify)
7. Multi-User Admin-System erwägen
8. 2FA für kritische Bereiche überlegen

---

## 🆘 Support

Bei Fragen oder Problemen:
- 📧 Support: Steven Günther (sgit.space)
- 📖 Dokumentation: `/docs/` Verzeichnis
- 🐛 Bugs: Status-Report oder neue Chat-Session

---

*Dokumentation erstellt am 21.12.2025 für sgiT Education Platform v3.47.0*
*Für produktive Nutzung wird eine zentrale Passwort-Verwaltung empfohlen!*
