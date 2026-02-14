#!/usr/bin/env python3
"""
Fix questions where the answer is stored as a letter (A, B, C, D) instead of the actual text.

This script:
1. Finds all questions where answer is 'A', 'B', 'C', or 'D'
2. Looks up the corresponding option from the options JSON array
3. Updates the answer field with the actual text

Usage:
    python scripts/fix_letter_answers.py [--dry-run]
"""

import sqlite3
import json
import sys
from pathlib import Path

DB_PATH = Path(__file__).parent.parent / "AI" / "data" / "questions.db"

def main():
    dry_run = '--dry-run' in sys.argv

    if dry_run:
        print("DRY RUN MODE - No changes will be made\n")

    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()

    # Find all questions with letter-based answers
    cur.execute("""
        SELECT id, module, question, answer, options
        FROM questions
        WHERE answer IN ('A', 'B', 'C', 'D')
        AND is_active = 1
    """)

    questions = cur.fetchall()
    print(f"Found {len(questions)} questions with letter-based answers\n")

    letter_map = {'A': 0, 'B': 1, 'C': 2, 'D': 3}
    fixed = 0
    errors = []

    for q in questions:
        qid = q['id']
        answer_letter = q['answer']
        options_json = q['options']

        try:
            options = json.loads(options_json) if options_json else []
        except json.JSONDecodeError as e:
            errors.append(f"ID {qid}: Invalid JSON in options - {e}")
            continue

        if not options:
            errors.append(f"ID {qid}: No options available")
            continue

        index = letter_map.get(answer_letter)
        if index is None:
            errors.append(f"ID {qid}: Unknown letter '{answer_letter}'")
            continue

        if index >= len(options):
            errors.append(f"ID {qid}: Letter '{answer_letter}' (index {index}) out of range, only {len(options)} options")
            continue

        correct_text = options[index]

        if dry_run:
            print(f"[DRY RUN] ID {qid} ({q['module']})")
            print(f"  Would change: '{answer_letter}' → '{correct_text[:50]}...'")
        else:
            cur.execute("""
                UPDATE questions
                SET answer = ?
                WHERE id = ?
            """, (correct_text, qid))
            print(f"✓ Fixed ID {qid} ({q['module']}): '{answer_letter}' → '{correct_text[:50]}...'")

        fixed += 1

    if not dry_run:
        conn.commit()

    print(f"\n{'='*80}")
    print(f"SUMMARY")
    print(f"{'='*80}")
    print(f"Total questions processed: {len(questions)}")
    print(f"Successfully fixed: {fixed}")
    print(f"Errors: {len(errors)}")

    if errors:
        print(f"\nERRORS:")
        for err in errors[:10]:
            print(f"  - {err}")
        if len(errors) > 10:
            print(f"  ... and {len(errors) - 10} more")

    if dry_run:
        print(f"\nThis was a DRY RUN. Run without --dry-run to apply changes.")
    else:
        print(f"\nChanges committed to database.")

    conn.close()

if __name__ == '__main__':
    main()
