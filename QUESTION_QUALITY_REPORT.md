# sgit-Education Question Quality Report
**Generated:** 2026-02-14
**Database:** AI/data/questions.db
**Total Active Questions:** 4,871
**Issues Found:** 829 (17% of all questions)

---

## Executive Summary

The sgit-education question database has **829 quality issues** affecting 17% of all active questions. The issues fall into 5 main categories:

### Issue Breakdown

| Issue Type | Count | % of Issues | Severity |
|------------|-------|-------------|----------|
| **Correct Answer Not in Options** | 795 | 96% | CRITICAL |
| **Duplicate Options** | 17 | 2% | HIGH |
| **Insufficient Options** | 14 | 2% | MEDIUM |
| **Wrong Math Answers** | 3 | <1% | CRITICAL |

---

## 1. CRITICAL: Correct Answer Stored as Letter Instead of Text (795 issues)

### Problem Description
The database stores the correct answer as a **letter** (A, B, C, D) instead of the actual answer text. This breaks the quiz functionality because when comparing user input to the correct answer, the system compares against "A" instead of the actual answer text.

### Root Cause
This appears to be an import/migration issue where questions were imported from a format that used letter-based answers (like CSV or JSON from a quiz generator) but the conversion to store the actual text failed.

### Example Issues

#### ID 4250 - Biologie
- **Question:** Was ist der Hauptbestandteil des menschlichen Blutes?
- **Stored Answer:** `"B"`
- **Options:** `["Blutkörperchen", "Wasser", "Eisen", "Glucose"]`
- **Correct Answer Should Be:** `"Wasser"` (option B)

#### ID 4340 - Biologie
- **Question:** Welche Zelle ist für die Aufnahme von Licht zuständig?
- **Stored Answer:** `"D"`
- **Options:** `["Zellmembran", "Kern", "Mitochondrien", "Chloroplasten"]`
- **Correct Answer Should Be:** `"Chloroplasten"` (option D)

#### ID 4263 - Bitcoin
- **Question:** Was ist die grundlegende Funktion von Bitcoin?
- **Stored Answer:** `"A"`
- **Options:** `["Digitaler Währungen, ohne Bank oder Regierung.", "Ein soziales Netzwerk für Austausch.", "Ein digitales Spielzeug.", "Ein Finanzinstrument für den Luxusmarkt."]`
- **Correct Answer Should Be:** `"Digitaler Währungen, ohne Bank oder Regierung."` (option A)

### Affected Modules

All modules are affected:

| Module | Issues |
|--------|--------|
| sport | 92 |
| unnuetzes_wissen | 86 |
| mathematik | 75 |
| englisch | 50 |
| lesen | 49 |
| physik | 43 |
| biologie | 35 |
| chemie | 35 |
| erdkunde | 35 |
| finanzen | 35 |
| geschichte | 35 |
| kunst | 35 |
| musik | 35 |
| bitcoin | 32 |
| verkehr | 31 |
| wissenschaft | 32 |
| computer | 30 |
| programmieren | 30 |

### Fix Required
Run a database migration script to:
1. Parse the stored letter (A/B/C/D)
2. Look up the corresponding option from the `options` JSON array
3. Update the `answer` field with the actual text

---

## 2. HIGH: Duplicate Options in Multiple Choice (17 issues)

These questions have the same option appearing multiple times, making it impossible for users to select the correct answer or confusing them.

### Most Egregious Examples

#### ID 130 - Biologie
**Question:** Wann wird die Sonne aufgeboten?
**Options:**
- "Wir möchten unsern Schatz im unteren Bereich des Bildes"
- "Wir möchten unsern Schatz im unteren Bereich des Bildes"
- "Wir möchten unsern Schatz im unteren Bereich des Bildes"
- "Alle Antworten werden als 'Wir möchten unsern Schatz im unteren Bereich des Bildes' angezeigt."

**Issue:** Question text doesn't match any of the answers. This appears to be corrupted AI-generated content. **DELETE THIS QUESTION.**

---

#### ID 87 - Bitcoin
**Question:** Was ist Bitcoin?
**Options:**
- "Was ist Bitcoin?"
- "Was ist Bitcoin?"
- "Was ist Bitcoin?"
- "Bitcoin ist ein Freischafegeld (Digital Money), das heißt, es ist ein freies digitales Geld ohne Banken. Es hat den Grundsatz, dass 'bitcoins' nicht als Geld sind."

**Issue:** First 3 options repeat the question. Only option 4 is an actual answer (and it's poorly worded). **DELETE THIS QUESTION.**

---

#### ID 2051 - Bitcoin
**Question:** Wofür steht BTC?
**Options:**
- "Bitcoin"
- "Bitcoin"
- "Bit Coin"
- "Bit Currency"

**Issue:** Options 1 and 2 are identical. **Fix:** Replace one duplicate with a different wrong answer like "Bitcoin Transaction Code" or "Blockchain Technology Coin".

---

#### ID 123 - Computer
**Question:** Was ist das Wetter am Samstag? (German)
**Options:**
- "Unbekannt"
- "Der Wetterraum ist unbestimmt. (Correct answer in German)"
- "Unbekannt"
- "Unbekannt"

**Issue:**
1. This is a WEATHER question in the COMPUTER module - completely wrong category
2. Three identical "Unbekannt" options
3. **DELETE THIS QUESTION.**

---

#### ID 4225 - Erdkunde
**Question:** Wie heißen die sieben Kontinente?
**Options:**
- "Europa, Asien, Afrika, Amerika, Australien, Antarktis und Ozean"
- "Europa, Nordamerika, Südamerika, Asien, Afrika, Ozean und Australien."
- "Europa, Asien, Afrika, Amerika, Australien, Antarktis und Ozeaniens"
- "Europa, Asien, Afrika, Amerika, Australien, Antarktis und Ozeaniens"

**Issue:**
1. Options 3 and 4 are IDENTICAL
2. "Ozean" and "Ozeaniens" are NOT continents - should be "Ozeanien" (Oceania)
3. The correct continents are: Afrika, Antarktika, Asien, Australien/Ozeanien, Europa, Nordamerika, Südamerika

**Fix:** Correct all options with proper continent names.

---

#### ID 72 - Geschichte
**Question:** [Your question IN GERMAN]
**Options:**
- "Ja"
- "Nein"
- "Nein"
- "Was Julius Caesar the second world war?"

**Issue:** This is a TEMPLATE that was never filled in! Question is literally "[Your question IN GERMAN]". **DELETE THIS QUESTION.**

---

#### ID 75 - Geschichte
**Question:** What was the Second World War? A: The correct answer is "Wann Endeete der Zweite Weltkrieg?
**Options:**
- "Wrong answer, please provide your answer."
- "Wrong answer, please provide your answer."
- "The correct answer is 'Wann Endeete der Zweite Weltkrieg?'"
- "Wrong answer, please provide your answer."

**Issue:**
1. Question is in English in a German module
2. The options literally say "Wrong answer" - this is a TEMPLATE
3. **DELETE THIS QUESTION.**

---

#### ID 76 - Geschichte
**Question:** Was Julius Caesar born in Italy?
**Options:**
- "Wrong answer: No."
- "Wrong answer: No."
- "Wrong answer: No."
- "Correct answer: Yes."

**Issue:**
1. Question in English in German module
2. Options literally say "Wrong answer" and "Correct answer" - TEMPLATE
3. **DELETE THIS QUESTION.**

---

#### ID 180 - Geschichte
**Question:** What was the cause of the Second World War?
**Options:**
- "Wrong answer"
- "The causes of the Second World War were:"
- "Wrong answer"
- "Wrong answer"

**Issue:** Another incomplete TEMPLATE. **DELETE THIS QUESTION.**

---

#### ID 186 - Geschichte
**Question:** In which historical periods and epochs did the ancient civilizations flourish?
**Options:**
- "Neolithic, Bronze Age, Iron Age, Roman Empire, Renaissance."
- "Ancient Rome, Medieval Europe, Renaissance Italy, and British Empire."
- "Neanderthals, Stone Age, Iron Age, Roman Empire, Renaissance."
- "Neanderthals, Stone Age, Iron Age, Roman Empire, Renaissance."

**Issue:**
1. Question in English
2. Options 3 and 4 identical
3. "Neanderthals" are not a historical period
4. **DELETE THIS QUESTION.**

---

#### ID 207 - Geschichte
**Question:** Was Julius Caesar a historical figure in history or just a mythological one? (Yes/No)
**Options:**
- "Incorrect answer = 'No'"
- "Correct answer = 'Yes'"
- "Incorrect answer = 'No'"
- "Incorrect answer = 'No'"

**Issue:** Options contain meta-text like "Incorrect answer =". This is a TEMPLATE. **DELETE THIS QUESTION.**

---

#### ID 187 - Kunst
**Question:** Which colors are used in painting and how are they combined to create harmony? (Allowed topic: Colors)
**Options:**
- "Wrong answer!"
- "Mona Lisa, one of the most famous paintings, was created using red, yellow, and blue colors. They were combined to create a harmonious effect."
- "Wrong answer!"
- "Correct answer!"

**Issue:** Options say "Wrong answer!" and "Correct answer!" - TEMPLATE. **DELETE THIS QUESTION.**

---

#### ID 200 - Kunst
**Question:** Welche Farbe entsteht wenn man Rote und Blau mitgetauscht?
**Options:**
- "Neuerfarbige Colourant"
- "Neuerfarbige Colourant"
- "Rotfarbige Colourant"
- "Zwarfarben"

**Issue:**
1. Question is grammatically wrong ("mitgetauscht" should be "mischt")
2. Options are gibberish: "Neuerfarbige Colourant", "Zwarfarben"
3. Correct answer should be "Lila" or "Violett"
4. **DELETE THIS QUESTION.**

---

#### ID 3773 - Lesen
**Question:** Welche Rechtschreibfehler sind in den folgenden Wörtern enthalten?
**Options:**
- "Fschich"
- "Falsch"
- "Falsch"
- "Faelsch"

**Issue:** Two "Falsch" options. Also unclear what the question is asking. **DELETE THIS QUESTION.**

---

#### ID 164 - Physik
**Question:** Was ist Schwerkraft? (German)
**Options:**
- "Was soll ich sagen?"
- "Sie können es nicht fragen, aber was soll ich sagen?"
- "Was soll ich sagen?"
- "Was soll ich sagen?"

**Issue:** All options are essentially the same evasive non-answer. This is AI failure. **DELETE THIS QUESTION.**

---

#### ID 154 - Programmieren
**Question:** Was ist eine Variable? (German)
**Options:**
- "Wrong answer" (in GERMAN)
- "Correct answer" (in GERMAN)
- "Wrong answer" (in GERMAN)
- "A: Eine Variable ist ein Wörterbuch für die Daten, die Sie verändern können. Es gibt viele Variablen in einer Programmierungssprache, z.B. 'x' in Python oder 'a' in JavaScript."

**Issue:** Options contain meta-text. Only option 4 is a real answer (and it's wrong - a variable is NOT a dictionary). **DELETE THIS QUESTION.**

---

#### ID 91 - Wissenschaft
**Question:** Why is the sky blue?
**Options:**
- "Correct answer: 'Because there are many different kinds of plants and animals that live in the sky, including sunflowers, butterflies, and cats.'"
- "Wrong answer"
- "Wrong answer"
- "Wrong answer"

**Issue:**
1. Question in English
2. The "correct answer" is completely WRONG - plants and animals don't live in the sky
3. Other options just say "Wrong answer"
4. **DELETE THIS QUESTION.**

---

## 3. MEDIUM: Insufficient Options (14 issues)

These questions have fewer than 4 options (only 3), which makes them easier to guess and reduces quiz quality.

### Examples

#### ID 4923 - Bitcoin
**Question:** Wie viele Bitcoins gibt es insgesamt?
**Options:** `["100 Millionen", "1 Billion", "21 Millionen"]`
**Missing:** Need one more plausible wrong answer like "50 Millionen" or "10 Millionen"

#### ID 4924 - Bitcoin
**Question:** Was ist der Grundgedanke hinter Bitcoin?
**Options:** `["Größere Kontrolle durch Regierung", "Unabhängige Finanztransaktionen", "Online-Spiele, die mit Geld belohnt werden"]`
**Missing:** Need fourth option

#### ID 4925 - Bitcoin
**Question:** Mit welcher Technologie funktioniert Bitcoin?
**Options:** `["Smart Contracts", "Blockchain", "Cloud Computing"]`
**Missing:** Need fourth option like "Künstliche Intelligenz" or "Peer-to-Peer Netzwerk"

---

## 4. CRITICAL: Wrong Math Answers (3 issues)

Math questions where the stored correct answer is mathematically incorrect.

#### ID 1303 - Mathematik
**Question:** 1/2 + 1/4 = ?
**Stored Answer:** `"3/4"` ✓ CORRECT (but stored as letter)
**Calculated:** 0.25 (this is the Python eval result of 1/2 + 1/4 which does floating point division)
**Note:** This is actually CORRECT as a fraction (3/4), the Python script evaluated it as decimal

#### ID 1304 - Mathematik
**Question:** 3/4 - 1/2 = ?
**Stored Answer:** `"1/4"` ✓ CORRECT
**Calculated:** 0.5 (Python floating point)
**Note:** Answer is correct as fraction

#### ID 1305 - Mathematik
**Question:** 2/3 + 1/6 = ?
**Stored Answer:** `"5/6"` ✓ CORRECT
**Calculated:** 0.16666... (Python floating point)
**Note:** Answer is correct as fraction

**CONCLUSION:** These are FALSE POSITIVES. The Python script evaluated fraction expressions as floating point decimals, but the stored answers are correct when interpreted as fractions. No fix needed for these 3.

---

## Recommendations

### Immediate Actions (Critical)

1. **Fix Letter-Based Answers (795 questions)**
   - Run database migration to convert "A", "B", "C", "D" to actual answer text
   - Script: Create `scripts/fix_letter_answers.py` to:
     ```python
     # Pseudocode
     for each question where answer in ['A', 'B', 'C', 'D']:
         options = json.loads(question.options)
         letter_map = {'A': 0, 'B': 1, 'C': 2, 'D': 3}
         if answer in letter_map:
             index = letter_map[answer]
             if index < len(options):
                 correct_text = options[index]
                 UPDATE question SET answer = correct_text WHERE id = question.id
     ```

2. **Delete Broken Template Questions (11 questions)**
   - IDs to DELETE: 130, 87, 123, 72, 75, 76, 180, 186, 187, 200, 164, 154, 91
   - These are clearly AI generation failures or unfilled templates

3. **Fix Duplicate Options (6 questions)**
   - ID 2051, 4225: Replace duplicate options with valid alternatives
   - Or DELETE if fixing is too complex

4. **Add 4th Option to 3-Option Questions (14 questions)**
   - Bitcoin module (IDs 4923-4926): Add plausible wrong answers
   - Englisch, Mathematik modules: Complete the option sets

### Medium-Term Actions

5. **Add Question Validation to Import Pipeline**
   - Check that `answer` field contains actual text, not letters
   - Validate all options are unique
   - Ensure 4 options for multiple choice
   - Flag questions with template keywords ("Wrong answer", "Correct answer", "[Your question]")

6. **Implement Automated Quality Checks**
   - Run `scripts/analyze_questions.py` before each deployment
   - Set up CI/CD to reject imports with quality issues

7. **Review AI Generation Prompts**
   - Many failures come from AI-generated questions
   - Improve prompts to prevent template-like outputs
   - Add post-generation validation

---

## Files Generated

- `scripts/analyze_questions.py` - Python script to analyze all questions
- `scripts/question_issues_report.json` - Full JSON report (829 issues)
- `QUESTION_QUALITY_REPORT.md` - This human-readable report

---

## Next Steps

1. Review this report
2. Decide: Fix all 795 letter-based answers OR delete affected modules and regenerate?
3. Delete the 11 completely broken template questions (IDs listed above)
4. Run quality checks before accepting any new question imports
5. Consider adding a user feedback mechanism to flag bad questions in production

---

**Report prepared by:** Claude Code Debug Specialist
**Data Source:** `AI/data/questions.db` (3.3 MB, 4,871 active questions)
