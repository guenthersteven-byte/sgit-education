# 📋 Question Editor - Dokumentation

## Übersicht

Der Question Editor ermöglicht das Korrigieren von fehlerhaften AI-generierten Fragen direkt im Admin-Bereich.

**URL:** http://localhost:8080/admin_cleanup_flags.php  
**Version:** 2.0  
**Datum:** 12.12.2025

---

## Features

### 1. Fragen editieren
- ✏️ Frage-Text bearbeiten
- ✅ Richtige Antwort auswählen (Radio-Buttons)
- 📝 Alle 4 Antwortoptionen bearbeiten

### 2. Hash-Management
Das System verhindert, dass die AI dieselbe fehlerhafte Frage erneut generiert:

1. **Alter Hash bleibt erhalten:** Beim Editieren wird der alte Hash als "blocked" gespeichert
2. **Ghost-Eintrag:** Ein neuer DB-Eintrag mit `is_active=0` wird erstellt
3. **Neuer Hash:** Die korrigierte Frage bekommt einen neuen Hash

### 3. Automatische Cleanup
- Nach erfolgreicher Korrektur werden alle Flags automatisch gelöscht
- Der User bekommt eine Bestätigung ob der Hash geändert wurde

---

## Workflow

```
1. Admin öffnet Cleanup-Seite
2. Klickt auf ✏️ Edit-Button bei einer geflaggten Frage
3. Edit-Modal öffnet sich mit allen Daten
4. Admin korrigiert Frage/Antwort/Optionen
5. Klickt "Speichern"
6. System:
   a) Prüft ob Hash sich geändert hat
   b) Falls ja: Erstellt Ghost-Eintrag für alten Hash
   c) Aktualisiert Frage mit neuem Hash
   d) Löscht alle Flags
7. Seite wird neu geladen
```

---

## API-Endpunkte

### GET: Frage laden
```
GET /api/flag_question.php?action=question&question_id=123

Response:
{
  "success": true,
  "question": {
    "id": 123,
    "module": "physik",
    "question": "Was ist Licht?",
    "answer": "Elektromagnetische Welle",
    "options": "[\"Option A\", \"Option B\", \"Option C\", \"Option D\"]",
    "question_hash": "abc123...",
    "age_min": 8,
    "age_max": 12
  }
}
```

### PUT: Frage speichern
```
PUT /api/flag_question.php
Content-Type: application/json

{
  "action": "edit_question",
  "question_id": 123,
  "old_hash": "abc123...",
  "question": "Korrigierte Frage",
  "answer": "Richtige Antwort",
  "options": ["Option A", "Option B", "Option C", "Option D"]
}

Response:
{
  "success": true,
  "message": "Frage aktualisiert",
  "hash_changed": true,
  "old_hash": "abc123...",
  "new_hash": "def456..."
}
```

---

## Hash-Algorithmus

Der Hash wird identisch zum CSV-Generator berechnet:

```php
function generateQuestionHash($q, $a, $b, $c, $d) {
    return md5(
        strtolower(trim($q)) . '|' . 
        strtolower(trim($a)) . '|' . 
        strtolower(trim($b)) . '|' . 
        strtolower(trim($c)) . '|' . 
        strtolower(trim($d))
    );
}
```

**Komponenten:**
- Frage (lowercase, trimmed)
- Option A (lowercase, trimmed)
- Option B (lowercase, trimmed)
- Option C (lowercase, trimmed)
- Option D (lowercase, trimmed)

Alle mit `|` verbunden, dann MD5.

---

## Datenbank-Änderungen

### Ghost-Eintrag für blockierte Hashes
```sql
INSERT INTO questions (
    module, 
    question, 
    answer, 
    options, 
    question_hash, 
    is_active, 
    source, 
    ai_generated
) VALUES (
    'physik',
    '[BLOCKED - editiert]',
    '[BLOCKED]',
    '[]',
    'alter_hash_hier',
    0,
    'blocked_edit',
    0
);
```

Dieser Eintrag:
- Hat `is_active = 0` → Wird nie angezeigt
- Hat den **alten** Hash → AI generiert diese Frage nicht mehr
- Hat `source = 'blocked_edit'` → Identifizierbar als Edit-Block

---

## Sicherheit

- Nur für eingeloggte Admins (`$_SESSION['is_admin'] === true`)
- CSRF-Schutz durch Session
- Eingabevalidierung auf Server-Seite
- Transaktionen für Datenintegrität

---

## Verwandte Dateien

| Datei | Funktion |
|-------|----------|
| `admin_cleanup_flags.php` | UI für Cleanup |
| `api/flag_question.php` | API-Endpunkte |
| `questions/generate_module_csv.php` | Hash-Funktion Original |

---

*Erstellt: 12.12.2025 | sgiT Education v3.28.0*
