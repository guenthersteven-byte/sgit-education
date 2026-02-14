#!/usr/bin/env python3
"""
Analyze all questions in the sgit-education database for quality issues.
"""

import sqlite3
import json
import re
from pathlib import Path

DB_PATH = Path(__file__).parent.parent / "AI" / "data" / "questions.db"

def main():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()

    # Get all active questions
    cur.execute("""
        SELECT id, module, question, answer as correct_answer,
               options, age_min, age_max, difficulty, explanation
        FROM questions
        WHERE is_active = 1
        ORDER BY module, id
    """)

    questions = cur.fetchall()
    print(f"Total active questions: {len(questions)}\n")

    issues = []

    for q in questions:
        qid = q['id']
        module = q['module']
        question_text = q['question']
        correct = q['correct_answer']
        options_json = q['options']
        age_min = q['age_min']
        age_max = q['age_max']

        # Parse options
        try:
            options = json.loads(options_json) if options_json else []
        except:
            options = []

        # Check 1: Correct answer not in options
        if options and correct not in options:
            issues.append({
                'id': qid,
                'module': module,
                'question': question_text,
                'issue': 'CORRECT_ANSWER_NOT_IN_OPTIONS',
                'detail': f"Correct: '{correct}' | Options: {options}"
            })

        # Check 2: Duplicate options
        if options and len(options) != len(set(options)):
            issues.append({
                'id': qid,
                'module': module,
                'question': question_text,
                'issue': 'DUPLICATE_OPTIONS',
                'detail': f"Options: {options}"
            })

        # Check 3: Math questions with wrong answers
        if module in ['mathematik', 'mathe'] and '=' in question_text:
            # Extract math expression
            match = re.search(r'(\d+\s*[\+\-\*\/]\s*\d+)\s*=\s*\?', question_text)
            if match:
                expr = match.group(1).replace(' ', '')
                try:
                    calculated = eval(expr)
                    if str(calculated) != str(correct).strip():
                        issues.append({
                            'id': qid,
                            'module': module,
                            'question': question_text,
                            'issue': 'WRONG_MATH_ANSWER',
                            'detail': f"Calculated: {calculated} | Stored: {correct}"
                        })
                except:
                    pass

        # Check 4: Empty or too short questions
        if not question_text or len(question_text.strip()) < 5:
            issues.append({
                'id': qid,
                'module': module,
                'question': question_text,
                'issue': 'QUESTION_TOO_SHORT',
                'detail': f"Length: {len(question_text or '')}"
            })

        # Check 5: Missing correct answer
        if not correct or len(str(correct).strip()) == 0:
            issues.append({
                'id': qid,
                'module': module,
                'question': question_text,
                'issue': 'MISSING_CORRECT_ANSWER',
                'detail': 'No correct answer provided'
            })

        # Check 6: Not enough options (should have 4 for multiple choice)
        if options and len(options) < 4:
            issues.append({
                'id': qid,
                'module': module,
                'question': question_text,
                'issue': 'INSUFFICIENT_OPTIONS',
                'detail': f"Only {len(options)} options: {options}"
            })

        # Check 7: Age range issues
        if age_min and age_max and age_min > age_max:
            issues.append({
                'id': qid,
                'module': module,
                'question': question_text,
                'issue': 'INVALID_AGE_RANGE',
                'detail': f"age_min ({age_min}) > age_max ({age_max})"
            })

    conn.close()

    # Output results
    print(f"\n{'='*80}")
    print(f"FOUND {len(issues)} ISSUES")
    print(f"{'='*80}\n")

    # Group by issue type
    by_type = {}
    for issue in issues:
        itype = issue['issue']
        if itype not in by_type:
            by_type[itype] = []
        by_type[itype].append(issue)

    for itype, ilist in sorted(by_type.items()):
        print(f"\n{itype}: {len(ilist)} issues")
        print("-" * 80)
        for issue in ilist[:10]:  # Show first 10 of each type
            print(f"\nID: {issue['id']} | Module: {issue['module']}")
            print(f"Question: {issue['question'][:100]}...")
            print(f"Detail: {issue['detail']}")

        if len(ilist) > 10:
            print(f"\n... and {len(ilist) - 10} more")

    # Save full report
    output_file = Path(__file__).parent.parent / "scripts" / "question_issues_report.json"
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(issues, f, indent=2, ensure_ascii=False)

    print(f"\n\nFull report saved to: {output_file}")

if __name__ == '__main__':
    main()
