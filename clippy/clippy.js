/**
 * ============================================================================
 * sgiT Education - Foxy Widget v1.4
 * ============================================================================
 * 
 * SIMPLER FOXY-KOPF mit konfigurierbaren Animationen
 * Basiert auf dem minimalistischen Fuchs-Design
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.4
 * @date 04.12.2025
 * ============================================================================
 */

class ClippyWidget {
    
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || '/Education/clippy/api.php';
        this.age = options.age || 10;
        this.module = options.module || null;
        this.currentQuestion = options.currentQuestion || null;
        this.userName = options.userName || null;
        
        // Animation Konfiguration
        this.config = {
            earAnimation: true,
            eyeAnimation: true,
            noseAnimation: true,
            idleAnimation: true,
            hoverAnimation: true,
            earSpeed: 4,      // Sekunden
            eyeSpeed: 4,      // Sekunden
            noseSpeed: 3,     // Sekunden
            idleSpeed: 3      // Sekunden
        };
        
        // Lade gespeicherte Konfig
        this.loadConfig();
        
        this.isOpen = false;
        this.isTyping = false;
        this.chatHistory = [];
        this.ollamaOnline = true;
        
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
    }
    
    /**
     * Gibt aktuelle Konfig zurück
     */
    getConfig() {
        return { ...this.config };
    }
    
    /**
     * Aktualisiert CSS-Variablen für Animationen
     */
    updateAnimations() {
        const root = document.documentElement;
        
        // Animation Speeds
        root.style.setProperty('--foxy-ear-speed', `${this.config.earSpeed}s`);
        root.style.setProperty('--foxy-eye-speed', `${this.config.eyeSpeed}s`);
        root.style.setProperty('--foxy-nose-speed', `${this.config.noseSpeed}s`);
        root.style.setProperty('--foxy-idle-speed', `${this.config.idleSpeed}s`);
        
        // Animation On/Off
        root.style.setProperty('--foxy-ear-animation', this.config.earAnimation ? 'running' : 'paused');
        root.style.setProperty('--foxy-eye-animation', this.config.eyeAnimation ? 'running' : 'paused');
        root.style.setProperty('--foxy-nose-animation', this.config.noseAnimation ? 'running' : 'paused');
        root.style.setProperty('--foxy-idle-animation', this.config.idleAnimation ? 'running' : 'paused');
        
        console.log('🦊 Animationen aktualisiert');
    }
    
    /**
     * Simpler Foxy-Kopf SVG - basierend auf dem minimalistischen Design
     */
    getFoxySVG(size = 'large') {
        const isLarge = size === 'large';
        const cssClass = isLarge ? 'foxy-svg' : 'foxy-svg-small';
        
        return `
        <svg class="${cssClass}" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <!-- Linkes Ohr -->
            <g class="fox-ear fox-ear-left">
                <path d="M20 45 L30 10 L45 40 Z" fill="#E86F2C"/>
                <path d="M25 42 L32 18 L42 40 Z" fill="#1E3A5F"/>
            </g>
            
            <!-- Rechtes Ohr -->
            <g class="fox-ear fox-ear-right">
                <path d="M80 45 L70 10 L55 40 Z" fill="#E86F2C"/>
                <path d="M75 42 L68 18 L58 40 Z" fill="#1E3A5F"/>
            </g>
            
            <!-- Kopf (Hauptform) -->
            <ellipse cx="50" cy="55" rx="35" ry="32" fill="#E86F2C"/>
            
            <!-- Weiße Gesichtspartie -->
            <path d="M50 42 Q28 58 35 75 Q42 88 50 85 Q58 88 65 75 Q72 58 50 42" fill="#FFFFFF"/>
            
            <!-- Linkes Auge -->
            <g class="fox-eye fox-eye-left">
                <ellipse cx="38" cy="52" rx="4" ry="5" fill="#1E3A5F"/>
                <ellipse cx="37" cy="51" rx="1.5" ry="2" fill="#FFFFFF"/>
            </g>
            
            <!-- Rechtes Auge -->
            <g class="fox-eye fox-eye-right">
                <ellipse cx="62" cy="52" rx="4" ry="5" fill="#1E3A5F"/>
                <ellipse cx="61" cy="51" rx="1.5" ry="2" fill="#FFFFFF"/>
            </g>
            
            <!-- Nase -->
            <g class="fox-nose">
                <ellipse cx="50" cy="68" rx="5" ry="4" fill="#1E3A5F"/>
                <ellipse cx="49" cy="67" rx="1.5" ry="1" fill="#3A5A7F"/>
            </g>
            
            <!-- Mund (lächelnd) -->
            <path d="M44 74 Q50 80 56 74" stroke="#1E3A5F" stroke-width="2" fill="none" stroke-linecap="round"/>
        </svg>
        `;
    }
    
    /**
     * Mini Foxy für Typing Indicator
     */
    getFoxyMini() {
        return `
        <svg class="foxy-svg-mini" viewBox="0 0 100 100" width="28" height="28" xmlns="http://www.w3.org/2000/svg">
            <g class="fox-ear fox-ear-left">
                <path d="M20 45 L30 10 L45 40 Z" fill="#E86F2C"/>
            </g>
            <g class="fox-ear fox-ear-right">
                <path d="M80 45 L70 10 L55 40 Z" fill="#E86F2C"/>
            </g>
            <ellipse cx="50" cy="55" rx="35" ry="32" fill="#E86F2C"/>
            <path d="M50 42 Q28 58 35 75 Q42 88 50 85 Q58 88 65 75 Q72 58 50 42" fill="#FFFFFF"/>
            <ellipse cx="38" cy="52" rx="4" ry="5" fill="#1E3A5F"/>
            <ellipse cx="62" cy="52" rx="4" ry="5" fill="#1E3A5F"/>
            <ellipse cx="50" cy="68" rx="5" ry="4" fill="#1E3A5F"/>
        </svg>
        `;
    }
    
    init() {
        this.createWidget();
        this.attachEventListeners();
        this.checkStatus();
        this.updateAnimations();
        
        console.log('🦊 Foxy v1.4 (Configurable) initialized!', {
            age: this.age,
            module: this.module,
            userName: this.userName,
            config: this.config
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
                    <div class="clippy-avatar">
                        ${this.getFoxySVG('small')}
                    </div>
                    <div class="clippy-info">
                        <h3 class="clippy-name">Foxy 🦊</h3>
                        <p class="clippy-status">
                            <span class="clippy-status-dot" id="clippy-status-dot"></span>
                            <span id="clippy-status-text">Bereit zu helfen!</span>
                        </p>
                    </div>
                    <button class="clippy-close" id="clippy-close" aria-label="Schließen">×</button>
                </div>
                
                <div class="clippy-messages" id="clippy-messages"></div>
                
                <div class="clippy-quick-actions">
                    <button class="clippy-quick-btn hi" data-message="Hallo Foxy!">Hallo</button>
                    <button class="clippy-quick-btn joke" data-message="Erzähl mir einen Witz!">Witz</button>
                    <button class="clippy-quick-btn cheer" data-message="Ich brauche Aufmunterung!">Aufmuntern</button>
                    <button class="clippy-quick-btn tip" data-message="Gib mir einen Tipp!">Tipp</button>
                </div>
                
                <div class="clippy-input-area">
                    <input type="text" 
                           id="clippy-input" 
                           class="clippy-input" 
                           placeholder="Schreib mir... 🦊"
                           maxlength="300"
                           autocomplete="off">
                    <button id="clippy-send" class="clippy-send" aria-label="Senden">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
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
        
        document.querySelectorAll('.clippy-quick-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const message = btn.dataset.message;
                if (message) {
                    this.input.value = message;
                    this.sendMessage();
                }
            });
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
    
    toggle() {
        this.isOpen ? this.close() : this.open();
    }
    
    open() {
        this.isOpen = true;
        this.window.classList.add('active');
        
        setTimeout(() => this.input.focus(), 300);
        
        if (this.chatHistory.length === 0) {
            this.showGreeting();
        }
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
                `${nameGreeting} 🦊 Bereit zum Lernen? Wähl oben ein Fach aus und leg los! 💪`,
                `${nameGreeting} 🌟 Schön, dass du da bist! Such dir ein Fach aus! 🎯`,
                `${nameGreeting} 🦊 Lust auf ein Quiz? Klick auf ein Fach! 🚀`
            ];
            return motivations[Math.floor(Math.random() * motivations.length)];
        }
        
        const moduleNames = {
            'mathematik': 'Mathe', 'physik': 'Physik', 'chemie': 'Chemie',
            'biologie': 'Bio', 'erdkunde': 'Erdkunde', 'geschichte': 'Geschichte',
            'kunst': 'Kunst', 'musik': 'Musik', 'computer': 'Computer',
            'programmieren': 'Programmieren', 'bitcoin': 'Bitcoin', 'steuern': 'Finanzen',
            'englisch': 'Englisch', 'lesen': 'Lesen', 'wissenschaft': 'Wissenschaft',
            'verkehr': 'Verkehr'
        };
        
        const moduleName = moduleNames[this.module.toLowerCase()] || this.module;
        
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
                "Warum ging der Fuchs zur Schule? Um schlauer als die anderen zu werden! 📚🦊",
                "Was sagt ein Fuchs wenn er fertig ist? FUCHSTASTISCH! 🎉🦊",
                "Warum sind Füchse so gute Detektive? Sie haben einen Riecher! 🔍🦊",
                "Was ist das Lieblingsfach vom Fuchs? Fuchs-ik! ⚛️🦊",
                "Warum tanzt der Fuchs so gern? Er hat den Fox-Trott erfunden! 💃🦊",
                "Was macht ein Fuchs im Matheunterricht? Er multipliFUCHSt! 🔢🦊",
                "Warum ist der Fuchs nie müde? Er schläft wie ein Fuchs! 😴🦊"
            ];
            return jokes[Math.floor(Math.random() * jokes.length)];
        }
        
        if (msg.includes('aufmunter') || msg.includes('traurig') || msg.includes('schwer') || msg.includes('hilf') || msg.includes('schaff')) {
            const cheers = [
                `${namePrefix}Kopf hoch! 💪 Du schaffst das! 🦊🌟`,
                `${namePrefix}Du bist toll! 🌈 Ich glaube an dich! 🦊❤️`,
                `${namePrefix}Füchse geben nie auf! 🦊💪 Weiter so!`,
                `${namePrefix}Jeder macht mal Fehler - so lernt man! 📚🦊`,
                `${namePrefix}Du bist schlauer als du denkst! 🧠✨`,
                `${namePrefix}Ein Schritt nach dem anderen! Du rockst das! 🎸🦊`,
                `${namePrefix}Füchse fallen 7 mal hin und stehen 8 mal auf! 💪🦊`,
                `${namePrefix}Ich bin stolz auf dich! Mach weiter! 🌟🦊`,
                `${namePrefix}Übung macht den Meister-Fuchs! 🏆🦊`,
                `${namePrefix}Du hast das Zeug zum Champion! 🥇🦊`
            ];
            return cheers[Math.floor(Math.random() * cheers.length)];
        }
        
        if (msg.includes('tipp') || msg.includes('rat')) {
            const tips = [
                "💡 Du bekommst Sats für richtige Antworten! 🦊₿",
                "💡 Lies die Frage immer zweimal! 📖🦊",
                "💡 Nutze den 50/50 Joker wenn du unsicher bist! 🦊",
                "💡 Jeden Tag 10 Fragen = Super Fortschritt! 📈🦊",
                "💡 Mach Pausen - dein Gehirn braucht sie! 🧠🦊",
                "💡 Falsche Antworten zeigen dir was du noch lernen kannst! 📚🦊"
            ];
            return tips[Math.floor(Math.random() * tips.length)];
        }
        
        if (msg.includes('danke') || msg.includes('super') || msg.includes('cool')) {
            const thanks = [
                "Gern geschehen! Du bist super! 🌟🦊",
                "Immer für dich da! 🦊❤️",
                "Das freut mich! Weiter so! 💪🦊",
                "Füchse helfen gern! 🦊✨"
            ];
            return thanks[Math.floor(Math.random() * thanks.length)];
        }
        
        if (msg.includes('bitcoin') || msg.includes('sats') || msg.includes('geld')) {
            return "₿ Bitcoin ist digitales Geld! Lerne mehr im Bitcoin-Modul! 🦊💰";
        }
        
        if (msg.includes('hallo') || msg.includes('hi') || msg.includes('hey') || msg.includes('moin')) {
            return this.generateGreeting();
        }
        
        if (msg.includes('langweilig') || msg.includes('keine lust')) {
            const motivation = [
                "Komm, nur noch eine Frage! Du schaffst das! 💪🦊",
                "Was hältst du von einem Witz zur Auflockerung? 😄🦊",
                "Mach 5 Minuten Pause und dann gehts weiter! ☕🦊"
            ];
            return motivation[Math.floor(Math.random() * motivation.length)];
        }
        
        // Fallback mit mehr Varianz
        const fallbacks = [
            `Frag mich nach einem Witz! 🎭🦊`,
            `Brauchst du einen Tipp? 💡🦊`,
            `Ich kann dich aufmuntern! 🌈🦊`,
            `Sag 'Witz' für was Lustiges! 😄🦊`
        ];
        return fallbacks[Math.floor(Math.random() * fallbacks.length)];
    }
    
    addMessage(text, role) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `clippy-message ${role}`;
        messageDiv.textContent = text;
        
        this.messagesContainer.appendChild(messageDiv);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        
        this.chatHistory.push({ role, content: text });
    }
    
    showTyping() {
        this.isTyping = true;
        
        const typingDiv = document.createElement('div');
        typingDiv.className = 'clippy-typing';
        typingDiv.id = 'clippy-typing-indicator';
        typingDiv.innerHTML = `
            <div class="clippy-typing-avatar">
                ${this.getFoxyMini()}
            </div>
            <div class="clippy-typing-dots">
                <span></span><span></span><span></span>
            </div>
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
                text.textContent = 'Bereit zu helfen!';
            } else {
                dot.classList.add('offline');
                text.textContent = 'Schnellmodus';
            }
        }
    }
    
    setContext(options = {}) {
        if (options.module !== undefined) {
            this.module = options.module;
        }
        if (options.age !== undefined) this.age = options.age;
        if (options.currentQuestion !== undefined) this.currentQuestion = options.currentQuestion;
        if (options.userName !== undefined) this.userName = options.userName;
    }
    
    clearHistory() {
        this.chatHistory = [];
        if (this.messagesContainer) {
            this.messagesContainer.innerHTML = '';
        }
    }
}

// ============================================================================
// AUTO-INIT
// ============================================================================

window.Foxy = null;

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('clippy-container')) {
        const age = window.userAge || 10;
        const module = window.currentModule || null;
        const userName = window.userName || null;
        
        window.Foxy = new ClippyWidget({
            age: age,
            module: module,
            userName: userName
        });
    }
});

function updateFoxyModule(module) {
    if (window.Foxy) {
        window.Foxy.setContext({ module: module });
    }
}
