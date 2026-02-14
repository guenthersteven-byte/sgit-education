# sgit-Education Question Quality Review - Summary

**Review Date:** 2026-02-14
**Reviewed By:** Claude Code Debug Specialist
**Database:** `AI/data/questions.db` (3.3 MB)
**Active Questions:** 4,871

---

## Quick Stats

- **Total Issues Found:** 829 (17% of all questions)
- **Critical Issues:** 795 (wrong answer format)
- **High Priority:** 17 (duplicate/nonsensical options)
- **Medium Priority:** 14 (missing options)
- **False Positives:** 3 (math questions - actually correct)

---

## Main Problems

### 1. CRITICAL: 795 questions have answers stored as letters (A/B/C/D) instead of text

**Impact:** Quiz completely broken - students can never select the "correct" answer because the system compares their text selection against a letter.

**Example:**
```
Question: "Was ist der Hauptbestandteil des menschlichen Blutes?"
Options: ["Blutkörperchen", "Wasser", "Eisen", "Glucose"]
Stored Answer: "B"   ← WRONG
Should Be: "Wasser" ← CORRECT
```

**Root Cause:** Import/migration issue where letter-based format wasn't converted to text.

**Fix:** Run `scripts/fix_letter_answers.py`

---

### 2. HIGH: 15 questions are completely broken (templates, gibberish, AI failures)

**Examples of Broken Questions:**

#### Template Not Filled In
- **ID 72:** Question literally says "[Your question IN GERMAN]"
- **ID 75:** Options say "Wrong answer, please provide your answer."
- **ID 76:** Options say "Wrong answer: No." and "Correct answer: Yes."

#### Gibberish/Corrupted
- **ID 130:** Question "Wann wird die Sonne aufgeboten?" (makes no sense) with all options being "Wir möchten unsern Schatz im unteren Bereich des Bildes"
- **ID 200:** Gibberish options like "Neuerfarbige Colourant", "Zwarfarben"

#### Wrong Module
- **ID 123:** Weather question in Computer module

#### Factually Wrong Answers Marked as Correct
- **ID 91:** "Why is the sky blue?" → Correct answer given: "Because there are many different kinds of plants and animals that live in the sky, including sunflowers, butterflies, and cats." ← COMPLETELY FALSE

**Fix:** Run `scripts/delete_broken_questions.py`

---

### 3. MEDIUM: 14 questions have only 3 options instead of 4

**Impact:** Easier to guess, reduces quiz quality.

**Fix:** Manually add 4th option to each question (mostly in Bitcoin module).

---

## Detailed Reports

See these files for complete information:

1. **`QUESTION_QUALITY_REPORT.md`** - Human-readable detailed report with examples
2. **`scripts/question_issues_report.json`** - Machine-readable JSON with all 829 issues
3. **`scripts/analyze_questions.py`** - Python script to regenerate analysis

---

## How to Fix

### Step 1: Backup Database
```bash
cp AI/data/questions.db AI/data/questions.db.backup_2026-02-14
```

### Step 2: Test Fix Scripts (Dry Run)
```bash
python scripts/fix_letter_answers.py --dry-run
python scripts/delete_broken_questions.py --dry-run
```

### Step 3: Apply Fixes
```bash
# Fix 795 letter-based answers
python scripts/fix_letter_answers.py

# Delete 15 broken questions
python scripts/delete_broken_questions.py
```

### Step 4: Verify
```bash
# Re-run analysis
python scripts/analyze_questions.py

# Should show: 14 issues remaining (the 3-option questions)
```

### Step 5: Manual Fixes
- Add 4th option to 14 Bitcoin/Englisch/Mathematik questions
- Review modules with most issues (Sport: 92, Unnützes Wissen: 86)

---

## Prevention Measures

### 1. Add Validation to Import Pipeline

**File:** `includes/CSVQuestionImporter.php` or similar

Add checks:
```php
// Validate answer is not a letter
if (in_array($answer, ['A', 'B', 'C', 'D'])) {
    throw new Exception("Answer must be text, not letter: $answer");
}

// Validate unique options
if (count($options) !== count(array_unique($options))) {
    throw new Exception("Duplicate options found");
}

// Validate 4 options
if (count($options) < 4) {
    throw new Exception("Must have 4 options");
}

// Validate no template keywords
$template_keywords = ['[Your question]', 'Wrong answer', 'Correct answer'];
foreach ($template_keywords as $keyword) {
    if (stripos($question, $keyword) !== false) {
        throw new Exception("Template keyword found: $keyword");
    }
}
```

### 2. Add Pre-Deployment Quality Check

**Add to CI/CD or deployment script:**
```bash
#!/bin/bash
# Pre-deploy quality check
python scripts/analyze_questions.py > /tmp/quality_report.txt

ISSUE_COUNT=$(grep "FOUND.*ISSUES" /tmp/quality_report.txt | awk '{print $2}')

if [ "$ISSUE_COUNT" -gt 50 ]; then
    echo "ERROR: $ISSUE_COUNT quality issues found!"
    echo "Review and fix before deploying."
    exit 1
fi
```

### 3. Improve AI Generation Prompts

Current issues suggest AI generation needs better constraints:

```
IMPROVED PROMPT STRUCTURE:

"Generate a multiple choice quiz question in German about [topic].

REQUIREMENTS:
- Question must be a clear, grammatically correct German sentence
- Provide exactly 4 distinct answer options
- One correct answer
- Three plausible but incorrect answers
- All options must be factually accurate statements (just 3 are wrong in context)
- DO NOT use placeholder text
- DO NOT use meta-text like 'Correct answer:' or 'Wrong answer:'
- DO NOT repeat options
- Return in this exact JSON format:
{
  'question': 'actual question text',
  'correct_answer': 'the correct answer text (not A/B/C/D)',
  'options': ['option1', 'option2', 'option3', 'option4'],
  'explanation': 'why the correct answer is correct'
}
"
```

---

## Modules Most Affected

| Module | Total Questions | Issues | % Affected | Priority |
|--------|----------------|--------|------------|----------|
| **Sport** | 140 | 92 | **65.7%** | 🔴 CRITICAL - Regenerate |
| **Unnützes Wissen** | 132 | 86 | **65.2%** | 🔴 CRITICAL - Regenerate |
| **Mathematik** | 372 | 83 | **22.3%** | 🟡 HIGH - Review |
| Englisch | 324 | 55 | 17.0% | 🟠 MEDIUM - Fix |
| Lesen | 300 | 50 | 16.7% | 🟠 MEDIUM - Fix |
| Verkehr | 191 | 31 | 16.2% | 🟠 MEDIUM - Fix |
| Physik | 304 | 44 | 14.5% | 🟠 MEDIUM - Fix |
| Geschichte | 283 | 41 | 14.5% | 🟠 MEDIUM - Fix |
| Kunst | 254 | 37 | 14.6% | 🟠 MEDIUM - Fix |
| Musik | 243 | 35 | 14.4% | 🟠 MEDIUM - Fix |
| Finanzen | 245 | 35 | 14.3% | 🟠 MEDIUM - Fix |
| Bitcoin | 293 | 38 | 13.0% | 🟠 MEDIUM - Fix |
| Erdkunde | 284 | 36 | 12.7% | 🟠 MEDIUM - Fix |
| Chemie | 285 | 35 | 12.3% | 🟠 MEDIUM - Fix |
| Wissenschaft | 271 | 33 | 12.2% | 🟠 MEDIUM - Fix |
| Biologie | 317 | 36 | 11.4% | 🟠 MEDIUM - Fix |
| Programmieren | 280 | 31 | 11.1% | 🟠 MEDIUM - Fix |
| Computer | 282 | 31 | 11.0% | 🟠 MEDIUM - Fix |
| **Steuern** | 71 | 0 | **0.0%** | ✅ CLEAN |

**Critical Findings:**
- **Sport and Unnützes Wissen modules have 65%+ defect rates** - these should be regenerated from scratch
- **Mathematik has 22.3% issues** - needs thorough review before automated fixes
- **Steuern module is CLEAN** - use as reference for quality standard
- All other modules have 11-17% issues, mostly the letter-based answer problem

**Recommendation:**
1. **Regenerate:** Sport, Unnützes Wissen (too broken to fix)
2. **Manual review:** Mathematik (high issue rate)
3. **Automated fix:** All others (run fix_letter_answers.py)

---

## Files Created

```
sgit-education/
├── QUESTION_QUALITY_REPORT.md          ← Detailed human-readable report
├── FINDINGS_SUMMARY.md                  ← This file
├── scripts/
│   ├── analyze_questions.py             ← Analysis script (run anytime)
│   ├── fix_letter_answers.py            ← Fix 795 letter-based answers
│   ├── delete_broken_questions.py       ← Delete 15 broken questions
│   └── question_issues_report.json      ← Full JSON report (829 issues)
```

---

## Questions?

The analysis is thorough and automated. You can:

1. **Review the detailed report:** `QUESTION_QUALITY_REPORT.md`
2. **See all 829 issues in JSON:** `scripts/question_issues_report.json`
3. **Re-run analysis anytime:** `python scripts/analyze_questions.py`
4. **Apply fixes safely:** Both fix scripts support `--dry-run` mode

---

**Next Steps:**
1. Review this summary and the detailed report
2. Decide: Fix or regenerate affected modules?
3. Run fix scripts
4. Add validation to import pipeline
5. Improve AI generation prompts

---

*Prepared by Claude Code Debug Specialist for sgit.space infrastructure.*
