<?php
/**
 * LEVEL-SYSTEM PATCH für adaptive_learning.php
 * 
 * Füge diese Funktionen in adaptive_learning.php ein nach den Session-Initialisierungen
 */

// === NACH ZEILE: if (!isset($_SESSION['adaptive_scores'])) ... ===

if (!isset($_SESSION['total_score'])) {
    $_SESSION['total_score'] = 0;
}

if (!isset($_SESSION['user_level'])) {
    $_SESSION['user_level'] = [
        'level' => 1,
        'name' => 'Baby',
        'icon' => '👶',
        'points' => 3
    ];
}

/**
 * Berechne User-Level basierend auf Gesamt-Score
 */
function calculateUserLevel($totalScore) {
    if ($totalScore >= 5000) {
        return ['level' => 5, 'name' => 'Opa', 'icon' => '👴', 'points' => 15];
    } elseif ($totalScore >= 1000) {
        return ['level' => 4, 'name' => 'Erwachsen', 'icon' => '👨', 'points' => 10];
    } elseif ($totalScore >= 500) {
        return ['level' => 3, 'name' => 'Jugend', 'icon' => '👦', 'points' => 7];
    } elseif ($totalScore >= 100) {
        return ['level' => 2, 'name' => 'Kind', 'icon' => '🧒', 'points' => 5];
    } else {
        return ['level' => 1, 'name' => 'Baby', 'icon' => '👶', 'points' => 3];
    }
}

/**
 * Update User-Level
 */
function updateUserLevel() {
    $newLevel = calculateUserLevel($_SESSION['total_score']);
    $oldLevel = $_SESSION['user_level']['level'];
    $_SESSION['user_level'] = $newLevel;
    return $newLevel['level'] > $oldLevel; // true wenn Level-Up
}

// === ERSETZE IM check_answer AJAX-Handler ===
/*
ALT:
    if ($isCorrect) {
        $_SESSION['adaptive_scores'][$module]['correct']++;
        $_SESSION['adaptive_scores'][$module]['score'] += POINTS_PER_ANSWER;
    }

NEU:
*/
    if ($isCorrect) {
        $_SESSION['adaptive_scores'][$module]['correct']++;
        $pointsEarned = $_SESSION['user_level']['points'];
        $_SESSION['adaptive_scores'][$module]['score'] += $pointsEarned;
        $_SESSION['total_score'] += $pointsEarned;
        $leveledUp = updateUserLevel();
    } else {
        $pointsEarned = 0;
        $leveledUp = false;
    }
    
    echo json_encode([
        'success' => true,
        'correct' => $isCorrect,
        'points_earned' => $pointsEarned,
        'total_score' => $_SESSION['adaptive_scores'][$module]['score'],
        'global_score' => $_SESSION['total_score'],
        'questions_done' => $_SESSION['adaptive_scores'][$module]['questions'],
        'correct_count' => $_SESSION['adaptive_scores'][$module]['correct'],
        'session_complete' => $_SESSION['adaptive_scores'][$module]['questions'] >= 10,
        'level' => $_SESSION['user_level'],
        'leveled_up' => $leveledUp
    ]);

// === UPDATE HEADER HTML ===
/*
ERSETZE in <div class="header">:

        <div class="score-display">
            <div class="user-level">
                <span id="levelIcon"><?php echo $_SESSION['user_level']['icon']; ?></span>
                <span id="levelName"><?php echo $_SESSION['user_level']['name']; ?></span>
                <span class="level-points">(<?php echo $_SESSION['user_level']['points']; ?> Punkte/Frage)</span>
            </div>
            <div class="score-label">Gesamt-Score</div>
            <div class="score-number" id="totalScore"><?php echo $_SESSION['total_score']; ?></div>
        </div>
*/

// === UPDATE JavaScript checkAnswer Funktion ===
/*
ERSETZE:
                    if (data.correct) {
                        btn.classList.add('correct');
                        document.getElementById('feedback').innerHTML = '<span style="color: green;">✅ Richtig! +10 Punkte</span>';
                    }

MIT:
                    if (data.correct) {
                        btn.classList.add('correct');
                        document.getElementById('feedback').innerHTML = `<span style="color: green;">✅ Richtig! +${data.points_earned} Punkte</span>`;
                    }

FÜGE HINZU vor "document.getElementById('sessionScore').textContent":
                    // Update globaler Score
                    document.getElementById('totalScore').textContent = data.global_score;
                    
                    // Level-Up Benachrichtigung
                    if (data.leveled_up) {
                        setTimeout(() => {
                            alert(`🎉 LEVEL UP!\n\n${data.level.icon} ${data.level.name}\n\nJetzt ${data.level.points} Punkte pro Frage!`);
                        }, 1000);
                    }
*/
?>
