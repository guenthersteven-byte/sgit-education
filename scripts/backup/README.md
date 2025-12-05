# sgiT Education - Backup & Restore System

**Version:** 1.0  
**Datum:** 03. Dezember 2025

---

## 🚀 Quick Start

### Backup erstellen (One-Click)

```powershell
# In PowerShell als Administrator:
cd C:\xampp\htdocs\Education\scripts\backup
.\backup_sgit_education.ps1
```

Das Backup wird standardmäßig in `D:\Backups\sgiT-Education\` gespeichert.

### Backup wiederherstellen (One-Click)

```powershell
# In PowerShell als Administrator:
cd C:\xampp\htdocs\Education\scripts\backup
.\restore_sgit_education.ps1 -BackupFile "D:\Backups\sgiT-Education\sgit-edu-backup-2025-12-03.zip"
```

---

## 📋 Backup-Script Optionen

```powershell
# Standard-Backup (empfohlen)
.\backup_sgit_education.ps1

# Backup an anderem Ort
.\backup_sgit_education.ps1 -BackupPath "E:\MyBackups"

# Mit Logs
.\backup_sgit_education.ps1 -IncludeLogs

# Ohne Komprimierung (schneller, aber größer)
.\backup_sgit_education.ps1 -NoCompress
```

---

## 📋 Restore-Script Optionen

```powershell
# Standard-Restore
.\restore_sgit_education.ps1 -BackupFile "path\to\backup.zip"

# In anderes Verzeichnis
.\restore_sgit_education.ps1 -BackupFile "..." -TargetPath "C:\xampp\htdocs\Education2"

# Nur Code, keine Datenbanken (für Updates)
.\restore_sgit_education.ps1 -BackupFile "..." -SkipDatabases

# Ohne Bestätigung überschreiben
.\restore_sgit_education.ps1 -BackupFile "..." -Force
```

---

## 📦 Was wird gesichert?

| Komponente | Pfad | Beschreibung |
|------------|------|--------------|
| **Datenbanken** | | |
| questions.db | AI/data/ | Alle Fragen |
| wallet.db | wallet/ | Wallet-Daten |
| bot_results.db | bots/logs/ | Bot-Ergebnisse |
| **Konfiguration** | | |
| config.php | / | Hauptkonfiguration |
| db_config.php | / | Datenbank-Einstellungen |
| ollama_*.* | AI/config/ | AI-Konfiguration |
| btcpay_config.php | wallet/ | Bitcoin-Konfiguration |
| **Benutzerdaten** | | |
| user_*.json | AI/users/ | User-Sessions |
| **Content** | | |
| *.csv | docs/ | Fragen-Templates |
| module_definitions*.json | AI/ | Modul-Definitionen |
| **Quellcode** | | |
| *.php, *.js, *.css | / | Gesamte Anwendung |

---

## 🔄 Empfohlene Backup-Strategie

1. **Täglich:** Automatisches Backup per Aufgabenplanung
2. **Vor Updates:** Manuelles Backup vor größeren Änderungen
3. **Extern:** Backup-ZIP auf externen Speicher kopieren (NAS, Cloud, USB)

### Windows Aufgabenplanung einrichten

```powershell
# Tägliches Backup um 3:00 Uhr
$Action = New-ScheduledTaskAction -Execute "PowerShell.exe" `
    -Argument "-ExecutionPolicy Bypass -File C:\xampp\htdocs\Education\scripts\backup\backup_sgit_education.ps1"

$Trigger = New-ScheduledTaskTrigger -Daily -At 3:00AM

Register-ScheduledTask -TaskName "sgiT Education Backup" `
    -Action $Action -Trigger $Trigger -Description "Tägliches Backup der sgiT Education Platform"
```

---

## 📖 Vollständige Dokumentation

Siehe: `docs/BACKUP_DOCKER_MIGRATION_CONCEPT.md`

Enthält:
- Docker-Migrationskonzept
- Technologie-Bewertung (PHP vs Python)
- Sicherheitsempfehlungen
- Roadmap für v3.0

---

## 🆘 Troubleshooting

### "Zugriff verweigert"
→ PowerShell als Administrator starten

### "Backup-Verzeichnis nicht gefunden"
→ Anderen Pfad angeben: `-BackupPath "C:\Backups"`

### "SQLite locked"
→ XAMPP/Apache stoppen vor dem Backup

### "Restore fehlgeschlagen"
→ Prüfen ob ZIP-Datei vollständig heruntergeladen wurde
