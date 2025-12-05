/**
 * sgiT Education Platform - Main JavaScript
 * 
 * Haupt-JavaScript für Interaktivität und User Experience.
 * 
 * @package sgiT_Education
 * @version 1.0.0
 * @author deStevie / sgiT Solution Engineering & IT Services
 */

'use strict';

// ============================================================================
// GLOBALE VARIABLEN
// ============================================================================

const SgiTEducation = {
    baseUrl: '/education/',
    currentUser: null,
    currentExercise: null,
    sounds: {
        correct: null,
        incorrect: null,
        complete: null
    }
};

// ============================================================================
// INITIALISIERUNG
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('sgiT Education Platform initialisiert');
    
    // Event Listeners registrieren
    initEventListeners();
    
    // Level-Auswahl initialisieren
    initLevelSelection();
    
    // Formular-Validierung
    initFormValidation();
});

// ============================================================================
// EVENT LISTENERS
// ============================================================================

function initEventListeners() {
    // Globale Tastatur-Shortcuts
    document.addEventListener('keydown', handleKeyboard);
    
    // Form Submissions verhindern Standard-Reload
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Nur verhindern wenn Ajax-Handling gewünscht
            if (form.classList.contains('ajax-form')) {
                e.preventDefault();
                handleAjaxForm(form);
            }
        });
    });
}

// ============================================================================
// LEVEL-AUSWAHL
// ============================================================================

function initLevelSelection() {
    const levelButtons = document.querySelectorAll('.level-btn');
    
    levelButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const level = this.dataset.level;
            selectLevel(level);
        });
    });
}

function selectLevel(level) {
    // Level-Auswahl visuell hervorheben
    const levelButtons = document.querySelectorAll('.level-btn');
    levelButtons.forEach(btn => {
        btn.classList.remove('selected');
    });
    
    const selectedBtn = document.querySelector(`[data-level="${level}"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('selected');
        
        // Hidden input updaten falls vorhanden
        const levelInput = document.getElementById('user_level');
        if (levelInput) {
            levelInput.value = level;
        }
    }
    
    // Animation
    animateSuccess(selectedBtn);
}

// ============================================================================
// FORMULAR-VALIDIERUNG
// ============================================================================

function initFormValidation() {
    const nameInput = document.getElementById('user_name');
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            validateName(this);
        });
    }
}

function validateName(input) {
    const value = input.value.trim();
    const regex = /^[a-zA-ZäöüÄÖÜß\s-]+$/;
    
    if (value.length === 0) {
        setInputState(input, 'neutral');
        return false;
    }
    
    if (value.length > 20) {
        setInputState(input, 'error', 'Name ist zu lang (max. 20 Zeichen)');
        return false;
    }
    
    if (!regex.test(value)) {
        setInputState(input, 'error', 'Nur Buchstaben erlaubt');
        return false;
    }
    
    setInputState(input, 'success');
    return true;
}

function setInputState(input, state, message = '') {
    // Alte Klassen entfernen
    input.classList.remove('input-error', 'input-success', 'input-neutral');
    
    // Neue Klasse setzen
    input.classList.add(`input-${state}`);
    
    // Fehlermeldung anzeigen/verstecken
    const errorMsg = input.parentElement.querySelector('.error-message');
    if (errorMsg) {
        if (state === 'error' && message) {
            errorMsg.textContent = message;
            errorMsg.style.display = 'block';
        } else {
            errorMsg.style.display = 'none';
        }
    }
}

// ============================================================================
// AJAX FORM HANDLING
// ============================================================================

function handleAjaxForm(form) {
    const formData = new FormData(form);
    const url = form.action || window.location.href;
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else if (data.message) {
                showMessage(data.message, 'success');
            }
        } else {
            showMessage(data.message || 'Ein Fehler ist aufgetreten', 'error');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showMessage('Ein Fehler ist aufgetreten', 'error');
    });
}

// ============================================================================
// NACHRICHTEN ANZEIGEN
// ============================================================================

function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type} fade-in`;
    
    const icons = {
        success: '✅',
        error: '❌',
        info: 'ℹ️',
        warning: '⚠️'
    };
    
    messageDiv.innerHTML = `
        <span class="message-icon">${icons[type] || icons.info}</span>
        <span class="message-text">${message}</span>
    `;
    
    // In Container einfügen
    const container = document.querySelector('.main-content .container');
    if (container) {
        container.insertBefore(messageDiv, container.firstChild);
        
        // Nach 5 Sekunden ausblenden
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 300);
        }, 5000);
    }
}

// ============================================================================
// ANIMATIONEN
// ============================================================================

function animateSuccess(element) {
    if (!element) return;
    
    element.style.transform = 'scale(1.1)';
    setTimeout(() => {
        element.style.transform = 'scale(1)';
    }, 300);
}

function animateError(element) {
    if (!element) return;
    
    element.classList.add('shake');
    setTimeout(() => {
        element.classList.remove('shake');
    }, 500);
}

function showConfetti() {
    // Einfache Konfetti-Animation
    const colors = ['#43D240', '#1A3503', '#4ecdc4', '#ffd93d'];
    const confettiCount = 50;
    
    for (let i = 0; i < confettiCount; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.animationDelay = Math.random() * 3 + 's';
        confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
        
        document.body.appendChild(confetti);
        
        // Nach Animation entfernen
        setTimeout(() => {
            confetti.remove();
        }, 5000);
    }
}

// ============================================================================
// TASTATUR-HANDLING
// ============================================================================

function handleKeyboard(e) {
    // ESC zum Schließen von Overlays
    if (e.key === 'Escape') {
        const overlays = document.querySelectorAll('.celebration-overlay, .modal-overlay');
        overlays.forEach(overlay => overlay.remove());
    }
    
    // Enter zum Absenden in Eingabefeldern
    if (e.key === 'Enter' && e.target.classList.contains('answer-input')) {
        const submitBtn = document.querySelector('.btn-submit');
        if (submitBtn) {
            submitBtn.click();
        }
    }
}

// ============================================================================
// UTILITY FUNKTIONEN
// ============================================================================

function formatNumber(num) {
    return new Intl.NumberFormat('de-DE').format(num);
}

function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return mins > 0 ? `${mins} Min ${secs} Sek` : `${secs} Sekunden`;
}

function getRandomElement(array) {
    return array[Math.floor(Math.random() * array.length)];
}

function shuffle(array) {
    const newArray = [...array];
    for (let i = newArray.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
    }
    return newArray;
}

// ============================================================================
// DEBUG HELPERS (nur wenn DEBUG_MODE aktiv)
// ============================================================================

if (window.location.search.includes('debug=1')) {
    console.log('Debug-Modus aktiv');
    
    window.sgiTDebug = {
        showMessage: showMessage,
        animateSuccess: animateSuccess,
        animateError: animateError,
        showConfetti: showConfetti
    };
}

// ============================================================================
// POLYFILLS FÜR ÄLTERE BROWSER
// ============================================================================

// CustomEvent Polyfill
if (typeof window.CustomEvent !== 'function') {
    function CustomEvent(event, params) {
        params = params || { bubbles: false, cancelable: false, detail: null };
        const evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }
    window.CustomEvent = CustomEvent;
}

// ============================================================================
// EXPORTS (für Modul-System falls verwendet)
// ============================================================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = SgiTEducation;
}

console.log('✅ sgiT Education Platform bereit!');
