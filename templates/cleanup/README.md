# 🧹 Cleanup Best Practices - sgiT Education

## ⚠️ WICHTIGSTE REGEL

**NIEMALS Dateien direkt löschen!**

Immer erst umbenennen → verschieben → testen → dann erst löschen (optional).

---

## 📋 Cleanup-Workflow (4 Schritte)

### Schritt 1: Umbenennen
```bash
# Prefix _OLD_ oder _DEPRECATED_ hinzufügen
mv datei.php _OLD_datei.php
```

### Schritt 2: Verschieben
```bash
# In _cleanup Ordner verschieben
mv _OLD_datei.php _cleanup/
```

### Schritt 3: Testen
- Browser öffnen und ALLE betroffenen Seiten testen
- Auf PHP-Fehler achten (require_once, include, class not found)
- Funktionalität prüfen

### Schritt 4: Dokumentieren
- Im Status-Report vermerken
- Git Commit mit klarer Beschreibung
- Erst nach erfolgreichen Tests endgültig löschen (optional)

---

## 🗂️ Ordnerstruktur für Cleanup

```
/Education/
├── _cleanup/                    # Temporär verschobene Dateien
│   ├── _OLD_datei1.php
│   ├── _DEPRECATED_modul.php
│   └── 2024-12/                # Optional: nach Monat sortiert
│       └── _OLD_altedatei.php
├── _deprecated/                 # Dauerhaft aufbewahrt (Referenz)
│   └── legacy_code.php
```

---

## 🔍 Vor dem Cleanup prüfen

### 1. Abhängigkeiten finden
```bash
# Suche nach require/include der Datei
grep -r "require.*dateiname" --include="*.php" .
grep -r "include.*dateiname" --include="*.php" .

# Suche nach Klassennutzung
grep -r "new KlassenName" --include="*.php" .
grep -r "extends KlassenName" --include="*.php" .
```

### 2. Links/URLs prüfen
```bash
# Suche nach href/src Verweisen
grep -r "href=.*dateiname" --include="*.php" .
grep -r "href=.*dateiname" --include="*.html" .
```

### 3. JavaScript/AJAX prüfen
```bash
# Suche nach fetch/ajax Aufrufen
grep -r "fetch.*dateiname" --include="*.js" .
grep -r "fetch.*dateiname" --include="*.php" .
```

---

## 📝 Beispiel: Sichere Datei-Entfernung

### ❌ FALSCH (was ich gemacht habe)
```bash
# Direkt löschen - GEFÄHRLICH!
rm windows_ai_generator.php
git commit -m "Alte Datei gelöscht"
# → Seite crasht weil require_once fehlschlägt
```

### ✅ RICHTIG
```bash
# 1. Umbenennen
mv windows_ai_generator.php _OLD_windows_ai_generator.php

# 2. Verschieben
mv _OLD_windows_ai_generator.php _cleanup/

# 3. TESTEN! Browser öffnen, alle Seiten prüfen

# 4. Bei Fehler: Schnell zurück
mv _cleanup/_OLD_windows_ai_generator.php windows_ai_generator.php

# 5. Bei Erfolg: Abhängigkeiten bereinigen, dann committen
git add -A
git commit -m "refactor: windows_ai_generator.php nach _cleanup verschoben"
```

---

## 🛡️ Sicherheits-Checkliste

Vor jedem Cleanup diese Fragen beantworten:

- [ ] Habe ich nach `require_once` / `include` gesucht?
- [ ] Habe ich nach Klassen-Instanziierungen gesucht (`new ClassName`)?
- [ ] Habe ich nach Links/URLs gesucht?
- [ ] Habe ich die Datei umbenannt statt gelöscht?
- [ ] Habe ich die Datei nach `_cleanup/` verschoben?
- [ ] Habe ich ALLE betroffenen Seiten im Browser getestet?
- [ ] Kann ich die Änderung schnell rückgängig machen?

---

## 📚 Verwandte Best Practices

1. **Git Branches**: Für größere Cleanups eigenen Branch nutzen
2. **Backups**: Vor großen Änderungen DB-Backup machen
3. **Stufenweise**: Nicht alles auf einmal, Schritt für Schritt
4. **Dokumentation**: Immer im Status-Report vermerken

---

## 🔧 Nützliche Befehle

```powershell
# PowerShell: Nach Abhängigkeiten suchen
Select-String -Path "*.php" -Pattern "windows_ai_generator" -Recurse

# Docker: PHP-Fehler live sehen
docker logs -f sgit-education-php

# Git: Gelöschte Datei wiederherstellen
git checkout HEAD~1 -- pfad/zur/datei.php
```

---

*Erstellt nach BUG-044 Incident am 12.12.2025*
*Lesson Learned: Immer erst verschieben, dann testen, dann löschen*
