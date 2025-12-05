# sgiT Education - Script Summary

**Letzte Aktualisierung:** 03. Dezember 2025  
**Version:** 1.1

---

## 📁 Verzeichnis-Struktur

```
scripts/
├── SCRIPT_SUMMARY.md           ← Dieses Dokument
├── powershell/
│   ├── ram_diagnose.ps1        RAM-Analyse
│   └── ollama_manage.ps1       Ollama-Verwaltung
└── php/
    ├── complete_fix_all_modules.php
    ├── db_fix_ai_generated.php
    ├── debug_ollama_deep.php
    ├── final_complete_fix.php
    ├── fix_dropdown_final.php
    ├── fix_generator_dropdown.php
    ├── fix_wrong_question.php
    ├── fix_xss_security.php
    ├── install_v10_fixes.php
    └── ollama_debug.php
```

---

## 🔧 PowerShell Scripts

### ram_diagnose.ps1
**Pfad:** `scripts/powershell/ram_diagnose.ps1`  
**Zweck:** Zeigt RAM-Nutzung des Systems und identifiziert RAM-Fresser

**Verwendung:**
```powershell
cd C:\xampp\htdocs\Education\scripts\powershell
.\ram_diagnose.ps1
```

**Ausgabe:**
- Gesamt/Freier RAM
- Top 10 RAM-Fresser
- Ollama RAM-Nutzung
- Apache/XAMPP RAM-Nutzung

---

### ollama_manage.ps1
**Pfad:** `scripts/powershell/ollama_manage.ps1`  
**Zweck:** Ollama verwalten (Status, Neustart, Modell-Wechsel)

**Verwendung:**
```powershell
cd C:\xampp\htdocs\Education\scripts\powershell
.\ollama_manage.ps1 status    # Status anzeigen
.\ollama_manage.ps1 restart   # Ollama neu starten
.\ollama_manage.ps1 test      # Modell testen
.\ollama_manage.ps1 switch    # Modell wechseln
.\ollama_manage.ps1 help      # Hilfe
```

---

## 🐘 PHP Scripts

### Fix-Scripte (scripts/php/)

| Script | Funktion |
|--------|----------|
| **complete_fix_all_modules.php** | Alle Module auf einmal reparieren |
| **db_fix_ai_generated.php** | ai_generated Flag in DB korrigieren |
| **final_complete_fix.php** | Finaler umfassender Fix |
| **fix_dropdown_final.php** | Dropdown-Menü Reparatur |
| **fix_generator_dropdown.php** | Generator Dropdown-Fix |
| **fix_wrong_question.php** | Falsche Fragen korrigieren |
| **fix_xss_security.php** | XSS-Sicherheitslücken schließen |
| **install_v10_fixes.php** | v10 Fixes installieren |

### Debug-Scripte (scripts/php/)

| Script | Funktion |
|--------|----------|
| **debug_ollama_deep.php** | Tiefgehende Ollama-Diagnose |
| **ollama_debug.php** | Ollama-Verbindung debuggen |

### Aktive Scripte (Hauptverzeichnis)

| Script | Pfad | Funktion |
|--------|------|----------|
| bug005_analyse.php | /Education/ | Fragen-Qualität analysieren |
| batch_import.php | /Education/ | Mehrere CSVs importieren |
| csv_import.php | /Education/ | Einzelne CSV importieren |
| windows_ai_generator.php | /Education/ | AI Fragen-Generator |

---

## 📋 Nützliche PowerShell-Befehle (Quick Reference)

### RAM-Diagnose
```powershell
# Top 10 RAM-Fresser
Get-Process | Sort-Object WorkingSet64 -Descending | Select-Object -First 10 Name, @{Name="RAM (MB)";Expression={[math]::Round($_.WorkingSet64/1MB,0)}}

# Nur Ollama-Prozesse
Get-Process -Name "ollama*" | Select-Object Name, @{Name="RAM (MB)";Expression={[math]::Round($_.WorkingSet64/1MB,0)}}

# Freier RAM anzeigen
[math]::Round((Get-CimInstance Win32_OperatingSystem).FreePhysicalMemory / 1MB / 1024, 1)

# Gesamt RAM
[math]::Round((Get-CimInstance Win32_ComputerSystem).TotalPhysicalMemory / 1GB, 1)
```

### Ollama-Verwaltung
```powershell
# Ollama stoppen
Stop-Process -Name "ollama*" -Force

# Modelle auflisten
ollama list

# Modell testen
ollama run tinyllama:latest "Sage Hallo"

# Modell löschen (bei RAM-Problemen)
ollama rm llama3.2:latest

# Modell neu herunterladen
ollama pull llama3.2:latest

# Modell in Config setzen
Set-Content -Path "C:\xampp\htdocs\Education\AI\config\ollama_model.txt" -Value "tinyllama:latest"
```

### XAMPP/Apache
```powershell
# Apache-Prozesse anzeigen
Get-Process -Name "httpd*" | Select-Object Name, @{Name="RAM (MB)";Expression={[math]::Round($_.WorkingSet64/1MB,0)}}
```

---

## 📝 RAM-Diagnose Ergebnis (03.12.2025, 00:50)

**Problem:** Ollama llama3.2:latest forderte 15.9 GB RAM an (nur 8.8 GB verfügbar)

**Top RAM-Fresser:**
| Prozess | RAM (MB) |
|---------|----------|
| claude | 1474 |
| Memory Compression | 964 |
| ollama | 688 |
| vmmemWSL | 411 |
| claude | 364 |
| Firefox | 341 |
| DeepL | 331 |
| explorer | 298 |
| MsMpEng | 283 |
| claude | 220 |

**Gesamt Claude-Prozesse:** ~2058 MB (~2 GB)  
**Gesamt Ollama-Prozesse:** ~764 MB

**Lösung:** Auf `tinyllama:latest` gewechselt (nur ~2 GB RAM benötigt)

---

## 🔗 Quick Links

| Script | URL |
|--------|-----|
| Bug Analyse | http://localhost/Education/bug005_analyse.php |
| Batch Import | http://localhost/Education/batch_import.php |
| CSV Import | http://localhost/Education/csv_import.php |
| AI Generator | http://localhost/Education/windows_ai_generator.php |

---

**Erstellt:** 03.12.2025 von Claude  
**Zweck:** Zentrale Dokumentation aller Hilfs-Scripte
