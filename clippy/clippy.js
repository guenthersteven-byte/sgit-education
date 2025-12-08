/**
 * ============================================================================
 * sgiT Education - Foxy Widget v2.0
 * ============================================================================
 * 
 * NEU v2.0 (08.12.2025) - Gemma AI Integration:
 * - getExplanation() - Erklärt warum Antwort richtig/falsch
 * - getHint() - Gibt Hinweis ohne Lösung zu verraten
 * - askQuestion() - Beantwortet Wissensfragen kindgerecht
 * - Model-Switch (tinyllama ↔ gemma2:2b)
 * - Status-Badge für AI-Modus
 * - Quiz-Kontext Integration
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 2.0
 * @date 08.12.2025
 * ============================================================================
 */

class ClippyWidget {
    
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || '/Education/clippy/api.php';
        this.age = options.age || 10;
        this.module = options.module || null;
        this.userName = options.userName || null;
        
        // Quiz-Kontext für Gemma-Features
        this.currentQuestion = options.currentQuestion || null;
        this.currentAnswer = options.currentAnswer || null;
        this.currentOptions = options.currentOptions || [];
        this.lastUserAnswer = null;
        this.lastWasCorrect = null;
        
        // Animation Konfiguration
        this.config = {
            earAnimation: true,
            eyeAnimation: true,
            noseAnimation: true,
            idleAnimation: true,
            hoverAnimation: true,
            earSpeed: 4,
            eyeSpeed: 4,
            noseSpeed: 3,
            idleSpeed: 3,
            // NEU v2.0: Gemma AI Einstellungen
            useGemma: true,           // Gemma für intelligente Antworten
            gemmaForHints: true,      // Gemma für Hints nutzen
            gemmaForExplain: true     // Gemma für Erklärungen nutzen
        };
        
        this.loadConfig();
        
        this.isOpen = false;
        this.isTyping = false;
        this.isWaitingForGemma = false;
        this.chatHistory = [];
        this.ollamaOnline = true;
        this.gemmaAvailable = false;
        
        this.button = null;
        this.window = null;
        this.messagesContainer = null;
        this.input = null;
        
        this.init();
    }

    /**
     * Lädt Konfiguration aus localStorage
     */
    loadConfig() {
        try {
            const saved = localStorage.getItem('foxyConfig');
            if (saved) {
                this.config = { ...this.config, ...JSON.parse(saved) };
            }
        } catch (e) {
            console.log('🦊 Keine gespeicherte Konfig gefunden');
        }
    }
    
    /**
     * Speichert Konfiguration
     */
    saveConfig() {
        try {
            localStorage.setItem('foxyConfig', JSON.stringify(this.config));
            console.log('🦊 Konfig gespeichert:', this.config);
        } catch (e) {
            console.log('🦊 Konfig konnte nicht gespeichert werden');
        }
    }
    
    /**
     * Aktualisiert eine Konfig-Option
     */
    setConfig(key, value) {
        this.config[key] = value;
        this.saveConfig();
        this.updateAnimations();
        
        // Update AI-Badge wenn useGemma geändert wird
        if (key === 'useGemma') {
            this.updateAIBadge();
        }
    }
    
    getConfig() {
        return { ...this.config };
    }
    
    /**
     * Aktualisiert CSS-Variablen für Animationen
     */
    updateAnimations() {
        const root = document.documentElement;
        root.style.setProperty('--foxy-ear-speed', `${this.config.earSpeed}s`);
        root.style.setProperty('--foxy-eye-speed', `${this.config.eyeSpeed}s`);
        root.style.setProperty('--foxy-nose-speed', `${this.config.noseSpeed}s`);
        root.style.setProperty('--foxy-idle-speed', `${this.config.idleSpeed}s`);
        root.style.setProperty('--foxy-ear-animation', this.config.earAnimation ? 'running' : 'paused');
        root.style.setProperty('--foxy-eye-animation', this.config.eyeAnimation ? 'running' : 'paused');
        root.style.setProperty('--foxy-nose-animation', this.config.noseAnimation ? 'running' : 'paused');
        root.style.setProperty('--foxy-idle-animation', this.config.idleAnimation ? 'running' : 'paused');
    }

    /**
     * Foxy SVG - Minimalistisches Design
     */
    getFoxySVG(size = 'large') {
        const cssClass = size === 'large' ? 'foxy-svg' : 'foxy-svg-small';
        return `
        <svg class="${cssClass}" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <g class="fox-ear fox-ear-left">
                <path d="M20 45 L30 10 L45 40 Z" fill="#E86F2C"/>
                <path d="M25 42 L32 18 L42 40 Z" fill="#1E3A5F"/>
            </g>
            <g class="fox-ear fox-ear-right">
                <path d="M80 45 L70 10 L55 40 Z" fill="#E86F2C"/>
                <path d="M75 42 L68 18 L58 40 Z" fill="#1E3A5F"/>
            </g>
            <ellipse cx="50" cy="55" rx="35" ry="32" fill="#E86F2C"/>
            <path d="M50 42 Q28 58 35 75 Q42 88 50 85 Q58 88 65 75 Q72 58 50 42" fill="#FFFFFF"/>
            <g class="fox-eye fox-eye-left">
                <ellipse cx="38" cy="52" rx="4" ry="5" fill="#1E3A5F"/>
                <ellipse cx="37" cy="51" rx="1.5" ry="2" fill="#FFFFFF"/>
            </g>
            <g class="fox-eye fox-eye-right">
                <ellipse cx="62" cy="52" rx="4" ry="5" fill="#1E3A5F"/>
                <ellipse cx="61" cy="51" rx="1.5" ry="2" fill="#FFFFFF"/>
            </g>
            <g class="fox-nose">
                <ellipse cx="50" cy="68" rx="5" ry="4" fill="#1E3A5F"/>
                <ellipse cx="49" cy="67" rx="1.5" ry="1" fill="#3A5A7F"/>
            </g>
            <path d="M44 74 Q50 80 56 74" stroke="#1E3A5F" stroke-width="2" fill="none" stroke-linecap="round"/>
        </svg>`;
    }
    
    getFoxyMini() {
        return `
        <svg class="foxy-svg-mini" viewBox="0 0 100 100" width="28" height="28" xmlns="http://www.w3.org/2000/svg">
            <g class="fox-ear fox-ear-left"><path d="M20 45 L30 10 L45 40 Z" fill="#E86F2C"/></g>
            <g class="fox-ear fox-ear-right"><path d="M80 45 L70 10 L55 40 Z" fill="#E86F2C"/></g>
            <ellipse cx="50" cy="55" rx="35" ry="32" fill="#E86F2C"/>
            <path d="M50 42 Q28 58 35 75 Q42 88 50 85 Q58 88 65 75 Q72 58 50 42" fill="#FFFFFF"/>
            <ellipse cx="38" cy="52" rx="4" ry="5" fill="#1E3A5F"/>
            <ellipse cx="62" cy="52" rx="4" ry="5" fill="#1E3A5F"/>
            <ellipse cx="50" cy="68" rx="5" ry="4" fill="#1E3A5F"/>
        </svg>`;
    }
    
    init() {
        this.createWidget();
        this.attachEventListeners();
        this.checkStatus();
        this.updateAnimations();
        console.log('🦊 Foxy v2.0 (Gemma AI) initialized!', {
            age: this.age, module: this.module, userName: this.userName,
            useGemma: this.config.useGemma
        });
    }

    createWidget() {
        const container = document.createElement('div');
        container.id = 'clippy-container';
        container.innerHTML = this.getWidgetHTML();
        document.body.appendChild(container);
        
        this.button = document.getElementById('clippy-button');
        this.window = document.getElementById('clippy-window');
        this.messagesContainer = document.getElementById('clippy-messages');
        this.input = document.getElementById('clippy-input');
    }
    
    getWidgetHTML() {
        return `
            <!-- Floating Foxy Button -->
            <button id="clippy-button" class="clippy-button" aria-label="Foxy öffnen">
                ${this.getFoxySVG('large')}
            </button>
            
            <!-- Chat Window -->
            <div id="clippy-window" class="clippy-window">
                <div class="clippy-header">
                    <div class="clippy-avatar">${this.getFoxySVG('small')}</div>
                    <div class="clippy-info">
                        <h3 class="clippy-name">Foxy 🦊</h3>
                        <p class="clippy-status">
                            <span class="clippy-status-dot" id="clippy-status-dot"></span>
                            <span id="clippy-status-text">Bereit zu helfen!</span>
                        </p>
                    </div>
                    <!-- NEU: AI-Mode Badge -->
                    <div class="clippy-ai-badge" id="clippy-ai-badge" title="Klicken zum Umschalten">
                        <span class="ai-badge-icon">🧠</span>
                        <span class="ai-badge-text" id="ai-badge-text">AI</span>
                    </div>
                    <button class="clippy-close" id="clippy-close" aria-label="Schließen">×</button>
                </div>
                
                <div class="clippy-messages" id="clippy-messages"></div>
                
                <div class="clippy-quick-actions" id="clippy-quick-actions">
                    <button class="clippy-quick-btn hi" data-action="greeting">Hallo</button>
                    <button class="clippy-quick-btn joke" data-action="joke">Witz</button>
                    <button class="clippy-quick-btn cheer" data-action="cheer">Aufmuntern</button>
                    <button class="clippy-quick-btn tip" data-action="tip">Tipp</button>
                    <button class="clippy-quick-btn hint gemma-btn" data-action="hint" id="btn-hint" style="display:none;">💡 Hinweis</button>
                    <button class="clippy-quick-btn explain gemma-btn" data-action="explain" id="btn-explain" style="display:none;">❓ Warum?</button>
                </div>
                
                <div class="clippy-input-area">
                    <input type="text" id="clippy-input" class="clippy-input" 
                           placeholder="Schreib mir... 🦊" maxlength="300" autocomplete="off">
                    <button id="clippy-send" class="clippy-send" aria-label="Senden">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                        </svg>
                    </button>
                </div>
            </div>`;
    }

    attachEventListeners() {
        this.button.addEventListener('click', () => this.toggle());
        document.getElementById('clippy-close').addEventListener('click', () => this.close());
        document.getElementById('clippy-send').addEventListener('click', () => this.sendMessage());
        
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Quick-Action Buttons mit erweiterten Actions
        document.querySelectorAll('.clippy-quick-btn').forEach(btn => {
            btn.addEventListener('click', () => this.handleQuickAction(btn.dataset.action));
        });
        
        // AI-Badge Toggle
        const aiBadge = document.getElementById('clippy-ai-badge');
        if (aiBadge) {
            aiBadge.addEventListener('click', () => this.toggleAIMode());
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) this.close();
        });
    }
    
    /**
     * Behandelt Quick-Action Buttons
     */
    handleQuickAction(action) {
        const messages = {
            greeting: 'Hallo Foxy!',
            joke: 'Erzähl mir einen Witz!',
            cheer: 'Ich brauche Aufmunterung!',
            tip: 'Gib mir einen Tipp!'
        };
        
        if (messages[action]) {
            this.input.value = messages[action];
            this.sendMessage();
        } else if (action === 'hint') {
            this.requestHint();
        } else if (action === 'explain') {
            this.requestExplanation();
        }
    }
    
    /**
     * NEU: Toggled AI-Modus (Gemma on/off)
     */
    toggleAIMode() {
        this.config.useGemma = !this.config.useGemma;
        this.saveConfig();
        this.updateAIBadge();
        
        const status = this.config.useGemma ? '🧠 AI-Modus aktiviert' : '⚡ Schnellmodus aktiviert';
        this.addMessage(status, 'system');
    }
    
    /**
     * NEU: Aktualisiert AI-Badge Anzeige
     */
    updateAIBadge() {
        const badge = document.getElementById('clippy-ai-badge');
        const badgeText = document.getElementById('ai-badge-text');
        
        if (badge && badgeText) {
            if (this.config.useGemma) {
                badge.classList.add('ai-active');
                badge.classList.remove('ai-inactive');
                badgeText.textContent = 'AI';
            } else {
                badge.classList.remove('ai-active');
                badge.classList.add('ai-inactive');
                badgeText.textContent = '⚡';
            }
        }
    }

    // ========================================================================
    // NEU v2.0: GEMMA AI METHODEN
    // ========================================================================
    
    /**
     * 🎓 Erklärt warum eine Antwort richtig/falsch ist
     */
    async getExplanation(question, correctAnswer, userAnswer) {
        if (!this.config.useGemma || !this.config.gemmaForExplain) {
            return this.getFallbackExplanation(question, correctAnswer, userAnswer);
        }
        
        this.showTyping('🧠 Foxy überlegt...');
        this.isWaitingForGemma = true;
        
        try {
            const response = await fetch(`${this.apiUrl}?action=explain`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    question: question,
                    correct_answer: correctAnswer,
                    user_answer: userAnswer,
                    age: this.age,
                    user_name: this.userName
                })
            });
            
            const data = await response.json();
            this.hideTyping();
            this.isWaitingForGemma = false;
            
            if (data.success) {
                return { success: true, message: data.message, source: data.source };
            }
        } catch (error) {
            console.error('🦊 Explain Error:', error);
            this.hideTyping();
            this.isWaitingForGemma = false;
        }
        
        return this.getFallbackExplanation(question, correctAnswer, userAnswer);
    }
    
    /**
     * 💡 Holt einen Hinweis ohne die Lösung zu verraten
     */
    async getHint(question, correctAnswer, options) {
        if (!this.config.useGemma || !this.config.gemmaForHints) {
            return this.getFallbackHint();
        }
        
        this.showTyping('💡 Foxy denkt nach...');
        this.isWaitingForGemma = true;
        
        try {
            const response = await fetch(`${this.apiUrl}?action=hint`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    question: question,
                    correct_answer: correctAnswer,
                    options: options,
                    age: this.age,
                    user_name: this.userName
                })
            });
            
            const data = await response.json();
            this.hideTyping();
            this.isWaitingForGemma = false;
            
            if (data.success) {
                return { success: true, message: data.message, source: data.source };
            }
        } catch (error) {
            console.error('🦊 Hint Error:', error);
            this.hideTyping();
            this.isWaitingForGemma = false;
        }
        
        return this.getFallbackHint();
    }
    
    /**
     * ❓ Beantwortet Wissensfragen
     */
    async askQuestion(question) {
        if (!this.config.useGemma) {
            return { success: true, message: this.getLocalResponse(question), source: 'local' };
        }
        
        this.showTyping('🧠 Foxy überlegt...');
        
        try {
            const response = await fetch(`${this.apiUrl}?action=ask`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    question: question,
                    age: this.age,
                    user_name: this.userName,
                    module: this.module
                })
            });
            
            const data = await response.json();
            this.hideTyping();
            
            if (data.success) {
                return { success: true, message: data.message, source: data.source };
            }
        } catch (error) {
            console.error('🦊 Ask Error:', error);
            this.hideTyping();
        }
        
        return { success: true, message: this.getLocalResponse(question), source: 'fallback' };
    }

    /**
     * Fordert Hinweis zur aktuellen Frage an
     */
    async requestHint() {
        if (!this.currentQuestion || !this.currentAnswer) {
            this.addMessage('🦊 Ich kann dir nur während einer Frage einen Hinweis geben!', 'bot');
            return;
        }
        
        const result = await this.getHint(this.currentQuestion, this.currentAnswer, this.currentOptions);
        this.addMessage(result.message, 'bot');
    }
    
    /**
     * Fordert Erklärung zur letzten Antwort an
     */
    async requestExplanation() {
        if (!this.currentQuestion || !this.currentAnswer) {
            this.addMessage('🦊 Ich kann dir eine Erklärung geben, wenn du eine Frage beantwortet hast!', 'bot');
            return;
        }
        
        const userAnswer = this.lastUserAnswer || this.currentAnswer;
        const result = await this.getExplanation(this.currentQuestion, this.currentAnswer, userAnswer);
        this.addMessage(result.message, 'bot');
    }
    
    /**
     * Fallback für Erklärungen
     */
    getFallbackExplanation(question, correctAnswer, userAnswer) {
        const name = this.userName ? `${this.userName}, ` : '';
        const isCorrect = userAnswer === correctAnswer;
        
        const explanations = isCorrect ? [
            `${name}Super! Die Antwort "${correctAnswer}" ist richtig! 🦊🌟`,
            `${name}Genau! "${correctAnswer}" stimmt! Gut gemacht! 💪🦊`,
            `${name}Richtig! Du bist schlau! 🧠✨`
        ] : [
            `${name}Die richtige Antwort war "${correctAnswer}". Beim nächsten Mal klappt's! 🦊💪`,
            `${name}Fast! Es war "${correctAnswer}". Weiter üben! 📚🦊`,
            `${name}Die Antwort war "${correctAnswer}". Du lernst schnell! 🌟🦊`
        ];
        
        return { success: true, message: explanations[Math.floor(Math.random() * explanations.length)], source: 'fallback' };
    }
    
    /**
     * Fallback für Hinweise
     */
    getFallbackHint() {
        const name = this.userName ? `${this.userName}, ` : '';
        const hints = [
            `${name}Lies die Frage nochmal genau durch! 📖🦊`,
            `${name}Schließe erst die Antworten aus, die sicher falsch sind! 🎯🦊`,
            `${name}Denk nach - die Antwort versteckt sich in der Frage! 💡🦊`,
            `${name}Vertrau deinem Bauchgefühl! 🦊✨`
        ];
        return { success: true, message: hints[Math.floor(Math.random() * hints.length)], source: 'fallback' };
    }
    
    // ========================================================================
    // QUIZ-KONTEXT MANAGEMENT
    // ========================================================================
    
    /**
     * Setzt den Quiz-Kontext (wird von adaptive_learning.php aufgerufen)
     */
    setQuizContext(question, correctAnswer, options) {
        this.currentQuestion = question;
        this.currentAnswer = correctAnswer;
        this.currentOptions = options || [];
        this.lastUserAnswer = null;
        this.lastWasCorrect = null;
        
        // Zeige Hint-Button wenn Quiz aktiv
        this.updateQuizButtons(true);
    }
    
    /**
     * Speichert die Antwort des Users (für Erklärungen)
     */
    setUserAnswer(userAnswer, wasCorrect) {
        this.lastUserAnswer = userAnswer;
        this.lastWasCorrect = wasCorrect;
        
        // Zeige Explain-Button nach Antwort
        this.updateQuizButtons(true, true);
    }
    
    /**
     * Löscht Quiz-Kontext
     */
    clearQuizContext() {
        this.currentQuestion = null;
        this.currentAnswer = null;
        this.currentOptions = [];
        this.lastUserAnswer = null;
        this.lastWasCorrect = null;
        this.updateQuizButtons(false);
    }
    
    /**
     * Aktualisiert Sichtbarkeit der Quiz-Buttons
     */
    updateQuizButtons(quizActive, showExplain = false) {
        const hintBtn = document.getElementById('btn-hint');
        const explainBtn = document.getElementById('btn-explain');
        
        if (hintBtn) {
            hintBtn.style.display = (quizActive && this.config.useGemma && !showExplain) ? 'inline-block' : 'none';
        }
        if (explainBtn) {
            explainBtn.style.display = (showExplain && this.config.useGemma) ? 'inline-block' : 'none';
        }
    }

    // ========================================================================
    // CHAT FUNKTIONEN
    // ========================================================================
    
    toggle() { this.isOpen ? this.close() : this.open(); }
    
    open() {
        this.isOpen = true;
        this.window.classList.add('active');
        setTimeout(() => this.input.focus(), 300);
        if (this.chatHistory.length === 0) this.showGreeting();
        this.updateAIBadge();
    }
    
    close() {
        this.isOpen = false;
        this.window.classList.remove('active');
    }
    
    async showGreeting() {
        this.showTyping();
        const greeting = this.generateGreeting();
        setTimeout(() => {
            this.hideTyping();
            this.addMessage(greeting, 'bot');
        }, 600);
    }
    
    generateGreeting() {
        const name = this.userName || null;
        const nameGreeting = name ? `Hey ${name}!` : 'Hey!';
        
        if (!this.module) {
            const motivations = [
                `${nameGreeting} 🦊 Bereit zum Lernen? Wähl oben ein Fach aus! 💪`,
                `${nameGreeting} 🌟 Schön, dass du da bist! Such dir ein Fach aus! 🎯`,
                `${nameGreeting} 🦊 Lust auf ein Quiz? Klick auf ein Fach! 🚀`
            ];
            return motivations[Math.floor(Math.random() * motivations.length)];
        }
        
        const moduleNames = {
            'mathematik': 'Mathe', 'physik': 'Physik', 'chemie': 'Chemie',
            'biologie': 'Bio', 'erdkunde': 'Erdkunde', 'geschichte': 'Geschichte',
            'kunst': 'Kunst', 'musik': 'Musik', 'computer': 'Computer',
            'programmieren': 'Programmieren', 'bitcoin': 'Bitcoin', 'finanzen': 'Finanzen',
            'englisch': 'Englisch', 'lesen': 'Lesen', 'wissenschaft': 'Wissenschaft',
            'verkehr': 'Verkehr', 'sport': 'Sport', 'unnuetzes_wissen': 'Unnützes Wissen'
        };
        
        const moduleName = moduleNames[this.module?.toLowerCase()] || this.module;
        const greetings = [
            `${nameGreeting} 🦊 Du lernst ${moduleName}! Brauchst du Hilfe? 💡`,
            `${nameGreeting} 🌟 ${moduleName} ist super! Frag mich! 🦊`,
            `${nameGreeting} 🦊 Cool, ${moduleName}! Ich bin hier für dich! 💪`
        ];
        
        return greetings[Math.floor(Math.random() * greetings.length)];
    }
    
    async sendMessage() {
        const message = this.input.value.trim();
        if (!message || this.isTyping) return;
        
        this.addMessage(message, 'user');
        this.input.value = '';
        this.showTyping();
        
        try {
            const response = await fetch(`${this.apiUrl}?action=chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: message,
                    age: this.age,
                    module: this.module,
                    user_name: this.userName,
                    current_question: this.currentQuestion,
                    history: this.chatHistory.slice(-6)
                })
            });
            
            const data = await response.json();
            this.hideTyping();
            
            if (data.success) {
                this.addMessage(data.message, 'bot');
            } else {
                this.addMessage(this.getLocalResponse(message), 'bot');
            }
        } catch (error) {
            console.error('Foxy Error:', error);
            this.hideTyping();
            this.addMessage(this.getLocalResponse(message), 'bot');
        }
    }

    getLocalResponse(message) {
        const msg = message.toLowerCase();
        const name = this.userName;
        const namePrefix = name ? `${name}, ` : '';
        
        if (msg.includes('witz') || msg.includes('lach') || msg.includes('lustig')) {
            const jokes = [
                "Warum können Füchse so gut in der Schule? Weil sie schlau sind! 🦊😄",
                "Was macht ein Fuchs am Computer? Surft im Fuchsbook! 💻🦊",
                "Was ist orange und kann rechnen? Ein Mathe-Fuchs! 🧮🦊",
                "Was sagt ein Fuchs wenn er fertig ist? FUCHSTASTISCH! 🎉🦊",
                "Warum sind Füchse so gute Detektive? Sie haben einen Riecher! 🔍🦊"
            ];
            return jokes[Math.floor(Math.random() * jokes.length)];
        }
        
        if (msg.includes('aufmunter') || msg.includes('traurig') || msg.includes('schwer') || msg.includes('hilf')) {
            const cheers = [
                `${namePrefix}Kopf hoch! 💪 Du schaffst das! 🦊🌟`,
                `${namePrefix}Du bist toll! 🌈 Ich glaube an dich! 🦊❤️`,
                `${namePrefix}Füchse geben nie auf! 🦊💪 Weiter so!`,
                `${namePrefix}Du bist schlauer als du denkst! 🧠✨`
            ];
            return cheers[Math.floor(Math.random() * cheers.length)];
        }
        
        if (msg.includes('tipp') || msg.includes('rat')) {
            const tips = [
                "💡 Du bekommst Sats für richtige Antworten! 🦊₿",
                "💡 Lies die Frage immer zweimal! 📖🦊",
                "💡 Nutze den Hint-Joker wenn du unsicher bist! 🦊",
                "💡 Jeden Tag 10 Fragen = Super Fortschritt! 📈🦊"
            ];
            return tips[Math.floor(Math.random() * tips.length)];
        }
        
        if (msg.includes('danke') || msg.includes('super') || msg.includes('cool')) {
            return ["Gern geschehen! 🌟🦊", "Immer für dich da! 🦊❤️", "Füchse helfen gern! 🦊✨"][Math.floor(Math.random() * 3)];
        }
        
        if (msg.includes('bitcoin') || msg.includes('sats')) {
            return "₿ Bitcoin ist digitales Geld! Lerne mehr im Bitcoin-Modul! 🦊💰";
        }
        
        if (msg.includes('hallo') || msg.includes('hi') || msg.includes('hey')) {
            return this.generateGreeting();
        }
        
        const fallbacks = [`Frag mich nach einem Witz! 🎭🦊`, `Brauchst du einen Tipp? 💡🦊`, `Ich kann dich aufmuntern! 🌈🦊`];
        return fallbacks[Math.floor(Math.random() * fallbacks.length)];
    }
    
    addMessage(text, role) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `clippy-message ${role}`;
        messageDiv.textContent = text;
        this.messagesContainer.appendChild(messageDiv);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        if (role !== 'system') this.chatHistory.push({ role, content: text });
    }
    
    showTyping(customText = null) {
        this.isTyping = true;
        const typingDiv = document.createElement('div');
        typingDiv.className = 'clippy-typing';
        typingDiv.id = 'clippy-typing-indicator';
        typingDiv.innerHTML = `
            <div class="clippy-typing-avatar">${this.getFoxyMini()}</div>
            <div class="clippy-typing-dots"><span></span><span></span><span></span></div>
            ${customText ? `<span class="clippy-typing-text">${customText}</span>` : ''}
        `;
        this.messagesContainer.appendChild(typingDiv);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
    
    hideTyping() {
        this.isTyping = false;
        const indicator = document.getElementById('clippy-typing-indicator');
        if (indicator) indicator.remove();
    }

    async checkStatus() {
        try {
            const response = await fetch(`${this.apiUrl}?action=status`);
            const data = await response.json();
            this.updateStatus(data.online);
            
            // Prüfe ob Gemma verfügbar ist
            if (data.available_models) {
                this.gemmaAvailable = data.available_models.some(m => m.includes('gemma'));
            }
        } catch (error) {
            this.updateStatus(false);
        }
    }
    
    updateStatus(online) {
        this.ollamaOnline = online;
        const dot = document.getElementById('clippy-status-dot');
        const text = document.getElementById('clippy-status-text');
        
        if (dot && text) {
            if (online) {
                dot.classList.remove('offline');
                text.textContent = this.config.useGemma ? '🧠 AI bereit' : '⚡ Schnellmodus';
            } else {
                dot.classList.add('offline');
                text.textContent = 'Lokal-Modus';
            }
        }
    }
    
    setContext(options = {}) {
        if (options.module !== undefined) this.module = options.module;
        if (options.age !== undefined) this.age = options.age;
        if (options.userName !== undefined) this.userName = options.userName;
        if (options.currentQuestion !== undefined) this.currentQuestion = options.currentQuestion;
        if (options.currentAnswer !== undefined) this.currentAnswer = options.currentAnswer;
        if (options.currentOptions !== undefined) this.currentOptions = options.currentOptions;
    }
    
    clearHistory() {
        this.chatHistory = [];
        if (this.messagesContainer) this.messagesContainer.innerHTML = '';
    }
}

// ============================================================================
// AUTO-INIT & GLOBALE FUNKTIONEN
// ============================================================================

window.Foxy = null;

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('clippy-container')) {
        window.Foxy = new ClippyWidget({
            age: window.userAge || 10,
            module: window.currentModule || null,
            userName: window.userName || null
        });
    }
});

// Globale Hilfsfunktionen für Integration
function updateFoxyModule(module) {
    if (window.Foxy) window.Foxy.setContext({ module: module });
}

function setFoxyQuizContext(question, correctAnswer, options) {
    if (window.Foxy) window.Foxy.setQuizContext(question, correctAnswer, options);
}

function setFoxyUserAnswer(userAnswer, wasCorrect) {
    if (window.Foxy) window.Foxy.setUserAnswer(userAnswer, wasCorrect);
}

function clearFoxyQuizContext() {
    if (window.Foxy) window.Foxy.clearQuizContext();
}

// Für direkte AI-Anfragen
async function askFoxy(question) {
    if (window.Foxy) {
        const result = await window.Foxy.askQuestion(question);
        window.Foxy.addMessage(result.message, 'bot');
        return result;
    }
    return { success: false, message: 'Foxy nicht initialisiert' };
}
