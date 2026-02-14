#!/usr/bin/env python3
"""
Delete completely broken template/AI-generated questions.

These questions have:
- Template text like "[Your question]", "Wrong answer", "Correct answer"
- Nonsensical or corrupted content
- Wrong module categorization

IDs to delete: 130, 87, 123, 72, 75, 76, 180, 186, 187, 200, 164, 154, 91, 3773, 207

Usage:
    python scripts/delete_broken_questions.py [--dry-run]
"""

import sqlite3
import sys
from pathlib import Path

DB_PATH = Path(__file__).parent.parent / "AI" / "data" / "questions.db"

# IDs of questions to delete (from quality report)
BROKEN_QUESTION_IDS = [
    130,   # Biologie - "Wann wird die Sonne aufgeboten?" - corrupted
    87,    # Bitcoin - "Was ist Bitcoin?" - options repeat question
    123,   # Computer - Weather question in wrong module
    72,    # Geschichte - Literal template "[Your question IN GERMAN]"
    75,    # Geschichte - Template with "Wrong answer, please provide..."
    76,    # Geschichte - Template with "Wrong answer:" prefix
    180,   # Geschichte - Template with "Wrong answer" options
    186,   # Geschichte - Duplicate options
    187,   # Kunst - Template with "Wrong answer!" / "Correct answer!"
    200,   # Kunst - Gibberish options
    164,   # Physik - "Was soll ich sagen?" evasive AI failure
    154,   # Programmieren - Template with meta-text
    91,    # Wissenschaft - Completely wrong answer about sky
    3773,  # Lesen - Duplicate options, unclear question
    207,   # Geschichte - Template with "Incorrect answer =" meta-text
]

def main():
    dry_run = '--dry-run' in sys.argv

    if dry_run:
        print("DRY RUN MODE - No questions will be deleted\n")

    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()

    print(f"Questions to delete: {len(BROKEN_QUESTION_IDS)}\n")

    for qid in BROKEN_QUESTION_IDS:
        # Fetch question details
        cur.execute("""
            SELECT id, module, question, answer, options
            FROM questions
            WHERE id = ?
        """, (qid,))

        q = cur.fetchone()

        if not q:
            print(f"⚠ ID {qid}: Not found in database")
            continue

        print(f"{'[DRY RUN] ' if dry_run else ''}ID {qid} ({q['module']})")
        print(f"  Q: {q['question'][:80]}...")
        print(f"  A: {q['answer'][:50]}...")

        if not dry_run:
            # SOFT DELETE: Set is_active = 0 (keeps hash to prevent AI regeneration)
            cur.execute("""
                UPDATE questions
                SET is_active = 0
                WHERE id = ?
            """, (qid,))
            print(f"  ✓ Deactivated")

        print()

    if not dry_run:
        conn.commit()
        print(f"{'='*80}")
        print(f"Successfully deactivated {len(BROKEN_QUESTION_IDS)} questions")
        print(f"(Using soft delete - questions still in DB with is_active=0)")
    else:
        print(f"{'='*80}")
        print(f"DRY RUN - Would deactivate {len(BROKEN_QUESTION_IDS)} questions")
        print(f"Run without --dry-run to apply changes")

    conn.close()

if __name__ == '__main__':
    main()
