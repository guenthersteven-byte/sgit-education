# 🔐 Gehashte Passwort-System - Vollständige Dokumentation

**Version:** 3.48.0  
**Datum:** 21. Dezember 2025  
**Status:** ✅ Produktionsbereit

---

## 📋 Inhaltsverzeichnis

1. [Schnellstart](#schnellstart)
2. [Was wurde geändert?](#was-wurde-geändert)
3. [Sicherheitsverbesserungen](#sicherheitsverbesserungen)
4. [Migrations-Anleitung](#migrations-anleitung)
5. [Passwort ändern](#passwort-ändern)
6. [Technische Details](#technische-details)
7. [Troubleshooting](#troubleshooting)
8. [Best Practices](#best-practices)

---

## 🚀 Schnellstart

### Migration durchführen (Einmalig!)

1. **Backup prüfen** (automatisch!)
2. **Migration starten:**
   ```
   http://localhost:8080/migrate_to_hashed_passwords.php
   ```
3. **Auf "Migration JETZT starten" klicken**
4. **Warten bis Erfolgsmeldung**
5. **Admin-Login testen:**
   ```
   http://localhost:8080/admin_v4.php
   Passwort: sgit2025 (bleibt gleich!)
   ```

### Passwort ändern (Nach Migration)

1. **Hash-Generator öffnen:**
   ```
   http://localhost:8080/admin_password_hasher.php
   ```
2. **Aktuelles Passwort zur Verifizierung**
3. **Neues Passwort eingeben**
4. **Hash kopieren**
5. **In `/includes/auth_config.php` eintragen**

---

## 🔄 Was wurde geändert?

### Vorher (v3.47.0 - UNSICHER)

```php
// Klartext in 7 Dateien!
define('ADMIN_PASSWORD', 'sgit2025');

if ($_POST['password'] === ADMIN_PASSWORD) {
    $_SESSION['is_admin'] = true;
}
```

❌ **Probleme:**
- Passwort im Klartext
- In Git-Repository sichtbar
- In 7 Dateien verteilt
- Keine Verschlüsselung

### Nachher (v3.48.0 - SICHER)

```php
// Gehashter Wert in zentraler Datei
require_once __DIR__ . '/includes/auth_functions.php';

if (verifyAdminPassword($_POST['password'])) {
    setAdminSession('is_admin');
}
```

✅ **Vorteile:**
- Passwort als Bcrypt-Hash
- Nicht in Git (`.gitignore`)
- Zentral an 1 Stelle
- Moderne Verschlüsselung

---

## 🛡️ Sicherheitsverbesserungen

### 1. Bcrypt-Hashing

**Was ist das?**
- Moderne Einweg-Verschlüsselung
- Kann nicht rückgängig gemacht werden
- Automatischer Salt (Zufallswert)
- CPU-intensiv gegen Brute-Force

**Beispiel:**
```
Passwort:  sgit2025
Hash:      $2y$10$qZ8vR7xK4mL9pN3sT6uVeO.YxWzAbCdEfGhIjKlMnOpQrStUvWxYz
```

### 2. Zentrale Verwaltung

**Struktur:**
```
/includes/
  ├── auth_config.php        → Passwort-Hash (NICHT in Git!)
  └── auth_functions.php     → Verifikations-Logik
```

**Vorteil:**
- 1 Datei ändern statt 7
- Einfacher zu warten
- Weniger Fehlerquellen

### 3. Audit-Logging

**Automatisch geloggt:**
- Erfolgreiche Logins
- Fehlgeschlagene Versuche
- IP-Adresse & Timestamp
- User-Agent

**Log-Datei:**
```
/logs/auth_audit.log
```

### 4. Session-Security

**Verbessert:**
- Token-basierte Sessions
- IP-Tracking
- Login-Zeit-Tracking
- Zentrale Logout-Funktion

---

## 📖 Migrations-Anleitung

### Schritt 1: Vorbereitung

**Prüfen:**
- Docker läuft: `docker ps`
- Zugriff auf Admin: `http://localhost:8080/admin_v4.php`
- Backup-Verzeichnis existiert

### Schritt 2: Migration

**Tool öffnen:**
```
http://localhost:8080/migrate_to_hashed_passwords.php
```

**Was passiert?**
1. ✅ Backup aller 7 Dateien
2. ✅ Klartext-Passwörter entfernt
3. ✅ Auth-Bibliothek eingebunden
4. ✅ Verifikation auf `password_verify()` umgestellt
5. ✅ Lock-File erstellt (verhindert Doppel-Ausführung)

**Backup-Pfad:**
```
/backups/migration_v3.48.0_YYYY-MM-DD_HHmmss/
```

### Schritt 3: Verifizierung

**Alle Admin-Bereiche testen:**

| Bereich | URL | Passwort |
|---------|-----|----------|
| Admin Dashboard | `/admin_v4.php` | sgit2025 |
| Flag Cleanup | `/admin_cleanup_flags.php` | Session-basiert |
| Backup Config | `/backup_config_admin.php` | sgit2025 |
| Bot Summary | `/bots/bot_summary.php` | sgit2025 |
| Bot Scheduler | `/bots/scheduler/scheduler_ui.php` | sgit2025 |
| Debug Users | `/debug_users.php` | sgit2025 |

### Schritt 4: .gitignore

**Prüfen ob vorhanden:**
```bash
# .gitignore sollte enthalten:
includes/auth_config.php
migration_completed.lock
logs/auth_audit.log
```

**Wenn nicht, manuell hinzufügen!**

### Schritt 5: Git Commit

**NUR diese Dateien committen:**
```bash
git add includes/auth_functions.php
git add admin_password_hasher.php
git add migrate_to_hashed_passwords.php
git add .gitignore
git add docs/HASHED_PASSWORD_SYSTEM.md

# NICHT committen:
# includes/auth_config.php ← Passwort-Hash!
# migration_completed.lock
# logs/auth_audit.log

git commit -m "v3.48.0: Gehashtes Passwort-System implementiert"
git push
```

---

## 🔑 Passwort ändern

### Methode 1: Hash-Generator Tool (EMPFOHLEN)

1. **Tool öffnen:**
   ```
   http://localhost:8080/admin_password_hasher.php
   ```

2. **Authentifizieren** mit aktuellem Passwort

3. **Neues Passwort eingeben:**
   - Mindestens 8 Zeichen
   - Zahlen empfohlen
   - Großbuchstaben empfohlen

4. **Hash generieren & kopieren**

5. **In `auth_config.php` eintragen:**
   ```php
   define('ADMIN_PASSWORD_HASH', '$2y$10$...');
   ```

6. **Speichern & testen**

### Methode 2: Command Line (Für Profis)

```php
php -r "echo password_hash('MeinNeuesPasswort', PASSWORD_DEFAULT);"
```

**Ausgabe in `auth_config.php` eintragen!**

---

## 🔧 Technische Details

### Datei-Struktur

```
/includes/
  ├── auth_config.php          # Passwort-Hash Storage
  └── auth_functions.php       # Verifikations-Logik

/
  ├── admin_password_hasher.php          # Hash-Generator UI
  ├── migrate_to_hashed_passwords.php    # Migrations-Tool
  └── migration_completed.lock           # Lock nach Migration

/logs/
  └── auth_audit.log          # Audit-Log

/backups/
  └── migration_v3.48.0_*/    # Backup der Originalversionen
```

### Wichtige Funktionen

#### `verifyAdminPassword($password)`
```php
/**
 * Verifiziert Passwort gegen gespeicherten Hash
 * @param string $password Klartext-Passwort
 * @return bool True wenn korrekt
 */
function verifyAdminPassword($password) {
    return password_verify($password, ADMIN_PASSWORD_HASH);
}
```

#### `generatePasswordHash($password)`
```php
/**
 * Generiert Bcrypt-Hash für neues Passwort
 * @param string $password Klartext-Passwort
 * @return string Bcrypt-Hash
 */
function generatePasswordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
```

#### `validatePasswordStrength($password)`
```php
/**
 * Validiert Passwort-Stärke
 * @return array ['valid' => bool, 'errors' => array]
 */
```

#### `setAdminSession($key)`
```php
/**
 * Setzt Admin-Session sicher
 * - Session-Token
 * - IP-Tracking
 * - Zeitstempel
 */
```

### Konfigurierbare Parameter

**In `auth_config.php`:**
```php
// Mindestlänge
define('PASSWORD_MIN_LENGTH', 8);

// Zahlen erforderlich?
define('PASSWORD_REQUIRE_NUMBERS', true);

// Sonderzeichen erforderlich?
define('PASSWORD_REQUIRE_SPECIAL', false);
```

### Legacy-Fallback

**Für Notfälle:**
```php
// In auth_config.php
define('USE_LEGACY_AUTH', true);  // Aktiviert Klartext-Fallback
```

⚠️ **NUR für Debugging! Sofort wieder deaktivieren!**

---

## 🐛 Troubleshooting

### Problem: "Migration completed.lock" blockiert

**Lösung:**
```bash
# Datei löschen (NUR wenn wirklich nötig!)
rm migration_completed.lock
```

### Problem: Login funktioniert nicht nach Migration

**Diagnose:**
1. **Legacy-Mode aktivieren:**
   ```php
   // auth_config.php
   define('USE_LEGACY_AUTH', true);
   ```

2. **Login testen** (funktioniert jetzt)

3. **Audit-Log prüfen:**
   ```bash
   tail -f logs/auth_audit.log
   ```

4. **Hash neu generieren:**
   - Tool: `admin_password_hasher.php`
   - Aktuelles Passwort eingeben
   - Neuen Hash kopieren
   - In `auth_config.php` eintragen

5. **Legacy-Mode deaktivieren:**
   ```php
   define('USE_LEGACY_AUTH', false);
   ```

### Problem: Hash-Generator zeigt Fehler

**Mögliche Ursachen:**
- PHP-Version < 7.0 (Bcrypt nicht verfügbar)
- Fehlende Berechtigungen für `/logs/`

**Lösung:**
```bash
# PHP-Version prüfen
php -v

# Logs-Verzeichnis erstellen
mkdir -p logs
chmod 755 logs
```

### Problem: Git zeigt auth_config.php als geändert

**Lösung:**
```bash
# .gitignore prüfen
cat .gitignore | grep auth_config

# Falls nicht vorhanden, hinzufügen:
echo "includes/auth_config.php" >> .gitignore

# Git cache clearen
git rm --cached includes/auth_config.php
git commit -m "Remove auth_config from tracking"
```

---

## ✅ Best Practices

### 1. Regelmäßige Passwortänderung

**Empfehlung:** Alle 90 Tage

**Prozess:**
1. Hash-Generator nutzen
2. Starkes Passwort wählen
3. Hash in `auth_config.php` eintragen
4. Metadata aktualisieren:
   ```php
   define('PASSWORD_LAST_CHANGED', '2025-12-21');
   ```

### 2. Backup vor Änderungen

**Immer vor Passwortänderung:**
```bash
cp includes/auth_config.php includes/auth_config.php.backup
```

### 3. Audit-Log regelmäßig prüfen

**Wöchentlich:**
```bash
tail -n 100 logs/auth_audit.log | grep "failed"
```

**Verdächtige Aktivitäten?**
- Viele Failed-Attempts
- Unbekannte IPs
- Ungewöhnliche Zeiten

### 4. Starke Passwörter

**Kriterien:**
- ✅ Mindestens 12 Zeichen
- ✅ Groß- und Kleinbuchstaben
- ✅ Zahlen
- ✅ Sonderzeichen
- ✅ Keine Wörterbuch-Wörter
- ✅ Keine persönlichen Infos

**Beispiel gute Passwörter:**
```
sgiT#Edu2025!Platform
M3in$Super-P@ssw0rt
Educ4tion#2025$Secure!
```

### 5. Multi-User vorbereitet

**Zukünftig mehrere Admins:**

```php
// In auth_config.php
$ADMIN_USERS = [
    'admin' => [
        'hash' => '$2y$10$...',
        'role' => 'superadmin'
    ],
    'steven' => [
        'hash' => '$2y$10$...',
        'role' => 'admin'
    ]
];
```

**Dann Funktionen erweitern:**
```php
function verifyUser($username, $password) {
    global $ADMIN_USERS;
    if (!isset($ADMIN_USERS[$username])) {
        return false;
    }
    return password_verify($password, $ADMIN_USERS[$username]['hash']);
}
```

---

## 📊 Migrations-Checkliste

Nutze diese Checkliste bei der Migration:

- [ ] **Vorbereitung**
  - [ ] Docker läuft
  - [ ] Admin-Zugriff funktioniert
  - [ ] Backup-Verzeichnis existiert

- [ ] **Migration**
  - [ ] Tool geöffnet: `migrate_to_hashed_passwords.php`
  - [ ] "Migration JETZT starten" geklickt
  - [ ] Erfolgsmeldung erhalten
  - [ ] Backup-Pfad notiert

- [ ] **Verifizierung**
  - [ ] Admin Dashboard Login getestet
  - [ ] Backup Config Login getestet
  - [ ] Bot Dashboard Login getestet
  - [ ] Bot Scheduler Login getestet
  - [ ] Debug Users Login getestet

- [ ] **Git**
  - [ ] .gitignore enthält `auth_config.php`
  - [ ] Neue Dateien committed
  - [ ] `auth_config.php` NICHT committed
  - [ ] Gepusht nach GitHub

- [ ] **Dokumentation**
  - [ ] Status-Report auf v3.48.0
  - [ ] Passwort-Änderungs-Prozess verstanden
  - [ ] Hash-Generator-Tool getestet

- [ ] **Optional**
  - [ ] Passwort geändert (empfohlen!)
  - [ ] Audit-Log geprüft
  - [ ] Alte Backups archiviert

---

## 🆘 Support & Hilfe

### Dokumentation

- **Diese Datei:** `/docs/HASHED_PASSWORD_SYSTEM.md`
- **Alte Methode:** `/docs/ADMIN_PASSWORD_MANAGEMENT.md`
- **Status-Report:** `/sgit_education_status_report.md`

### Tools

| Tool | URL | Zweck |
|------|-----|-------|
| Migration | `/migrate_to_hashed_passwords.php` | Einmalige Umstellung |
| Hash Generator | `/admin_password_hasher.php` | Passwort ändern |
| Admin Dashboard | `/admin_v4.php` | Hauptzugriff |

### Bei Problemen

1. **Audit-Log prüfen:** `/logs/auth_audit.log`
2. **Legacy-Mode testen:** `USE_LEGACY_AUTH = true`
3. **Backup wiederherstellen:** `/backups/migration_v3.48.0_*/`
4. **Neue Chat-Session:** Status-Report lesen

---

## 📝 Änderungshistorie

### v3.48.0 (21.12.2025)
- ✅ Gehashtes Passwort-System implementiert
- ✅ Zentrale Auth-Bibliothek erstellt
- ✅ Migrations-Tool entwickelt
- ✅ Hash-Generator Tool erstellt
- ✅ Audit-Logging hinzugefügt
- ✅ .gitignore erweitert
- ✅ Dokumentation erstellt

### v3.47.0 (vorher)
- ❌ Klartext-Passwörter in 7 Dateien
- ❌ Keine zentrale Verwaltung
- ❌ Kein Hashing

---

## 🎯 Zukunftsplanung

### Kurzfristig
- Passwort nach Migration ändern
- Audit-Log Monitoring einrichten
- Regelmäßige Backup-Routine

### Mittelfristig
- Multi-User Admin-System
- 2FA (Two-Factor Authentication)
- Session-Timeout konfigurierbar

### Langfristig
- OAuth2 Integration
- LDAP/Active Directory Support
- Admin-Rollen & Berechtigungen

---

*Dokumentation erstellt am 21.12.2025 für sgiT Education Platform v3.48.0*  
*Bei Fragen: Steven Günther - sgit.space*
