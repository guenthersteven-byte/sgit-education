/**
 * sgiT Education Platform - Math JavaScript
 * 
 * Mathematik-spezifische Funktionen für Übungen und Aufgaben.
 * 
 * @package sgiT_Education
 * @version 1.0.0
 * @author deStevie / sgiT Solution Engineering & IT Services
 */

'use strict';

// ============================================================================
// MATH EXERCISE HANDLER
// ============================================================================

const MathExercise = {
    currentTask: null,
    currentTaskIndex: 0,
    totalTasks: 0,
    correctAnswers: 0,
    attempts: 0,
    startTime: null,
    
    /**
     * Initialisiert eine neue Übung
     */
    init: function(taskData) {
        this.currentTask = taskData;
        this.currentTaskIndex = 0;
        this.totalTasks = taskData.length;
        this.correctAnswers = 0;
        this.attempts = 0;
        this.startTime = Date.now();
        
        this.renderTask();
        this.attachEventListeners();
    },
    
    /**
     * Rendert die aktuelle Aufgabe
     */
    renderTask: function() {
        const task = this.currentTask[this.currentTaskIndex];
        if (!task) return;
        
        const taskDisplay = document.querySelector('.task-question');
        if (taskDisplay) {
            // Aufgabe mit Animation anzeigen
            taskDisplay.innerHTML = this.formatTask(task);
            this.animateTaskElements();
        }
        
        // Fortschritt aktualisieren
        this.updateProgress();
        
        // Input-Feld fokussieren
        const answerInput = document.querySelector('.answer-input');
        if (answerInput) {
            answerInput.value = '';
            answerInput.focus();
            answerInput.classList.remove('correct', 'incorrect');
        }
        
        // Feedback verstecken
        this.hideFeedback();
    },
    
    /**
     * Formatiert die Aufgabe für die Anzeige
     */
    formatTask: function(task) {
        const parts = [];
        
        parts.push(`<span class="task-operand">${task.operand1}</span>`);
        parts.push(`<span class="task-operand">${task.operator}</span>`);
        parts.push(`<span class="task-operand">${task.operand2}</span>`);
        parts.push(`<span class="task-operand">=</span>`);
        parts.push(`<span class="task-operand">?</span>`);
        
        return parts.join(' ');
    },
    
    /**
     * Animiert die Aufgaben-Elemente
     */
    animateTaskElements: function() {
        const operands = document.querySelectorAll('.task-operand');
        operands.forEach((operand, index) => {
            operand.style.animationDelay = `${index * 0.1}s`;
        });
    },
    
    /**
     * Event Listeners registrieren
     */
    attachEventListeners: function() {
        const answerInput = document.querySelector('.answer-input');
        const submitBtn = document.querySelector('.btn-submit');
        const nextBtn = document.querySelector('.btn-next');
        
        if (answerInput) {
            answerInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.checkAnswer();
                }
            });
            
            // Nur Zahlen erlauben
            answerInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
        
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.checkAnswer());
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextTask());
        }
    },
    
    /**
     * Prüft die eingegebene Antwort
     */
    checkAnswer: function() {
        const answerInput = document.querySelector('.answer-input');
        if (!answerInput || !answerInput.value) {
            this.showFeedback('Bitte gib eine Antwort ein! 🤔', 'info');
            return;
        }
        
        this.attempts++;
        const userAnswer = parseInt(answerInput.value);
        const task = this.currentTask[this.currentTaskIndex];
        const isCorrect = userAnswer === task.answer;
        
        if (isCorrect) {
            this.handleCorrectAnswer();
        } else {
            this.handleIncorrectAnswer();
        }
    },
    
    /**
     * Behandelt richtige Antwort
     */
    handleCorrectAnswer: function() {
        this.correctAnswers++;
        
        const answerInput = document.querySelector('.answer-input');
        if (answerInput) {
            answerInput.classList.add('correct');
        }
        
        // Positives Feedback
        const messages = [
            'Super! Das war richtig! 🎉',
            'Klasse gemacht! 👏',
            'Perfekt! Weiter so! ⭐',
            'Richtig! Du bist spitze! 🌟',
            'Toll! Das kannst du gut! 💪'
        ];
        
        this.showFeedback(getRandomElement(messages), 'correct');
        
        // An Server senden
        this.sendAnswerToServer(true, this.attempts);
        
        // Automatisch zur nächsten Aufgabe nach 2 Sekunden
        setTimeout(() => {
            if (this.currentTaskIndex < this.totalTasks - 1) {
                this.nextTask();
            } else {
                this.completeExercise();
            }
        }, 2000);
    },
    
    /**
     * Behandelt falsche Antwort
     */
    handleIncorrectAnswer: function() {
        const answerInput = document.querySelector('.answer-input');
        if (answerInput) {
            answerInput.classList.add('incorrect');
            animateError(answerInput);
        }
        
        // Motivierendes Feedback
        const messages = [
            'Fast! Versuch es nochmal! 💭',
            'Nicht ganz, aber du schaffst das! 💪',
            'Probier es noch einmal! 🤔',
            'Das war knapp! Noch ein Versuch! 🎯'
        ];
        
        this.showFeedback(getRandomElement(messages), 'incorrect');
        
        // Hinweis nach 2 Versuchen
        if (this.attempts >= 2) {
            this.showHint();
        }
    },
    
    /**
     * Zeigt einen Hinweis an
     */
    showHint: function() {
        const task = this.currentTask[this.currentTaskIndex];
        let hint = '';
        
        if (task.operator === '+') {
            hint = `💡 Tipp: Zähle ${task.operand1} und ${task.operand2} zusammen!`;
        } else if (task.operator === '−') {
            hint = `💡 Tipp: Beginne bei ${task.operand1} und zähle ${task.operand2} zurück!`;
        }
        
        const hintBox = document.querySelector('.hint-box');
        if (hintBox) {
            hintBox.querySelector('.hint-text').textContent = hint;
            hintBox.style.display = 'block';
        }
    },
    
    /**
     * Zeigt Feedback an
     */
    showFeedback: function(message, type) {
        let feedbackBox = document.querySelector('.feedback-box');
        
        if (!feedbackBox) {
            feedbackBox = document.createElement('div');
            feedbackBox.className = 'feedback-box';
            const answerSection = document.querySelector('.answer-section');
            if (answerSection) {
                answerSection.appendChild(feedbackBox);
            }
        }
        
        const icons = {
            correct: '✅',
            incorrect: '❌',
            info: '💭'
        };
        
        feedbackBox.className = `feedback-box feedback-${type}`;
        feedbackBox.innerHTML = `
            <span class="feedback-icon">${icons[type] || '💭'}</span>
            ${message}
        `;
        feedbackBox.style.display = 'block';
    },
    
    /**
     * Versteckt Feedback
     */
    hideFeedback: function() {
        const feedbackBox = document.querySelector('.feedback-box');
        if (feedbackBox) {
            feedbackBox.style.display = 'none';
        }
        
        const hintBox = document.querySelector('.hint-box');
        if (hintBox) {
            hintBox.style.display = 'none';
        }
    },
    
    /**
     * Geht zur nächsten Aufgabe
     */
    nextTask: function() {
        this.currentTaskIndex++;
        this.attempts = 0;
        
        if (this.currentTaskIndex < this.totalTasks) {
            this.renderTask();
        } else {
            this.completeExercise();
        }
    },
    
    /**
     * Aktualisiert die Fortschrittsanzeige
     */
    updateProgress: function() {
        // Task Counter
        const taskCounter = document.querySelector('.task-counter');
        if (taskCounter) {
            taskCounter.textContent = `Aufgabe ${this.currentTaskIndex + 1} / ${this.totalTasks}`;
        }
        
        // Progress Dots
        const progressDots = document.querySelectorAll('.progress-dot');
        progressDots.forEach((dot, index) => {
            dot.classList.remove('current', 'completed');
            if (index < this.currentTaskIndex) {
                dot.classList.add('completed');
            } else if (index === this.currentTaskIndex) {
                dot.classList.add('current');
            }
        });
        
        // Progress Bar
        const progressFill = document.querySelector('.progress-fill');
        if (progressFill) {
            const percentage = ((this.currentTaskIndex + 1) / this.totalTasks) * 100;
            progressFill.style.width = percentage + '%';
        }
    },
    
    /**
     * Schließt die Übung ab
     */
    completeExercise: function() {
        const duration = Math.floor((Date.now() - this.startTime) / 1000);
        const accuracy = Math.round((this.correctAnswers / this.totalTasks) * 100);
        
        // Konfetti!
        showConfetti();
        
        // Celebration Overlay
        this.showCelebration({
            totalTasks: this.totalTasks,
            correctAnswers: this.correctAnswers,
            accuracy: accuracy,
            duration: duration
        });
    },
    
    /**
     * Zeigt Celebration Overlay
     */
    showCelebration: function(stats) {
        const overlay = document.createElement('div');
        overlay.className = 'celebration-overlay';
        
        overlay.innerHTML = `
            <div class="celebration-content">
                <div class="celebration-icon">🏆</div>
                <h2 class="celebration-title">Super gemacht!</h2>
                <p>Du hast alle Aufgaben geschafft!</p>
                
                <div class="celebration-stats">
                    <div class="celebration-stat">
                        Aufgaben: <strong>${stats.correctAnswers} / ${stats.totalTasks}</strong>
                    </div>
                    <div class="celebration-stat">
                        Genauigkeit: <strong>${stats.accuracy}%</strong>
                    </div>
                    <div class="celebration-stat">
                        Zeit: <strong>${formatTime(stats.duration)}</strong>
                    </div>
                </div>
                
                <button class="btn btn-primary" onclick="window.location.href='${SgiTEducation.baseUrl}mathe/'">
                    Noch eine Übung! 🎯
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='${SgiTEducation.baseUrl}'">
                    Zurück zum Start 🏠
                </button>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // An Server senden
        this.sendCompletionToServer(stats);
    },
    
    /**
     * Sendet Antwort an Server
     */
    sendAnswerToServer: function(isCorrect, attempts) {
        // Hier würde die AJAX-Anfrage an den Server gehen
        // Für diese Version speichern wir nur lokal in der Session
        console.log('Answer:', { isCorrect, attempts });
    },
    
    /**
     * Sendet Abschluss an Server
     */
    sendCompletionToServer: function(stats) {
        // Hier würde die AJAX-Anfrage an den Server gehen
        console.log('Exercise completed:', stats);
        
        // An PHP-Backend senden
        fetch(SgiTEducation.baseUrl + 'mathe/complete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(stats)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Server response:', data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
};

// ============================================================================
// ZAHLENVERGLEICH HANDLER
// ============================================================================

const ComparisonExercise = {
    /**
     * Initialisiert Zahlenvergleich-Übung
     */
    init: function() {
        this.attachEventListeners();
    },
    
    /**
     * Event Listeners für Vergleichs-Buttons
     */
    attachEventListeners: function() {
        const comparisonBtns = document.querySelectorAll('.comparison-btn');
        
        comparisonBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const operator = this.dataset.operator;
                ComparisonExercise.checkComparison(operator);
            });
        });
    },
    
    /**
     * Prüft Zahlenvergleich
     */
    checkComparison: function(selectedOperator) {
        const task = MathExercise.currentTask[MathExercise.currentTaskIndex];
        const correctOperator = this.getCorrectOperator(task.operand1, task.operand2);
        
        const isCorrect = selectedOperator === correctOperator;
        
        if (isCorrect) {
            MathExercise.handleCorrectAnswer();
        } else {
            MathExercise.handleIncorrectAnswer();
        }
    },
    
    /**
     * Ermittelt den korrekten Vergleichsoperator
     */
    getCorrectOperator: function(num1, num2) {
        if (num1 < num2) return '<';
        if (num1 > num2) return '>';
        return '=';
    }
};

// ============================================================================
// INITIALISIERUNG
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Prüfen ob wir auf einer Mathe-Seite sind
    if (document.querySelector('.exercise-container')) {
        console.log('Mathe-Übung wird initialisiert...');
        
        // Aufgabendaten aus data-Attribut laden
        const exerciseContainer = document.querySelector('.exercise-container');
        if (exerciseContainer && exerciseContainer.dataset.tasks) {
            try {
                const tasks = JSON.parse(exerciseContainer.dataset.tasks);
                MathExercise.init(tasks);
            } catch (e) {
                console.error('Fehler beim Laden der Aufgaben:', e);
            }
        }
        
        // Zahlenvergleich initialisieren falls vorhanden
        if (document.querySelector('.comparison-btn')) {
            ComparisonExercise.init();
        }
    }
});

// ============================================================================
// EXPORT
// ============================================================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { MathExercise, ComparisonExercise };
}
