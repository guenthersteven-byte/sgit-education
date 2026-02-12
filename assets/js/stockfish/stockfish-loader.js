/**
 * sgiT Education - Stockfish.js Loader v1.1
 *
 * Laedt die Stockfish Chess Engine als Web Worker.
 * Verwendet stockfish.js v10 (pure JS, kein WASM/SharedArrayBuffer noetig).
 * Funktioniert auf allen Browsern ohne spezielle Server-Headers.
 *
 * Nutzung:
 *   const sf = new StockfishLoader();
 *   sf.init().then(() => {
 *       sf.setDifficulty(3);
 *       sf.getBestMove(fen, (move) => console.log(move));
 *   });
 */
class StockfishLoader {
    constructor() {
        this.worker = null;
        this.ready = false;
        this.thinking = false;
        this.onReady = null;
        this.onBestMove = null;
        this.onError = null;
        this.difficulty = 3;

        // Schwierigkeitsstufen
        this.levels = [
            { name: 'Anfaenger', skill: 0,  depth: 1,  moveTime: 200,  elo: '~400'  },
            { name: 'Leicht',    skill: 5,  depth: 3,  moveTime: 400,  elo: '~800'  },
            { name: 'Mittel',    skill: 10, depth: 6,  moveTime: 800,  elo: '~1200' },
            { name: 'Schwer',    skill: 15, depth: 10, moveTime: 1500, elo: '~1600' },
            { name: 'Meister',   skill: 20, depth: 16, moveTime: 3000, elo: '~2000' }
        ];

    }

    init() {
        return new Promise((resolve, reject) => {
            try {
                // Stockfish lokal laden (gehostet auf dem Server, kein CDN/CORS noetig)
                this.worker = new Worker('/assets/js/stockfish/stockfish.js');

                this.worker.onmessage = (e) => this._handleMessage(e.data);
                this.worker.onerror = (e) => {
                    console.error('Stockfish Worker Error:', e);
                    if (!this.ready) reject(new Error('Stockfish Worker konnte nicht geladen werden'));
                };

                // UCI initialisieren
                this.worker.postMessage('uci');

                // Timeout
                const timeout = setTimeout(() => {
                    if (!this.ready) {
                        reject(new Error('Stockfish init timeout (20s)'));
                    }
                }, 20000);

                this.onReady = () => {
                    clearTimeout(timeout);
                    console.log('Stockfish bereit!');
                    resolve();
                };
            } catch (e) {
                reject(e);
            }
        });
    }

    _handleMessage(line) {
        if (typeof line !== 'string') return;

        if (line === 'uciok') {
            this.worker.postMessage('isready');
        }

        if (line === 'readyok' && !this.ready) {
            this.ready = true;
            this.setDifficulty(this.difficulty);
            if (this.onReady) this.onReady();
        }

        if (line.startsWith('bestmove')) {
            this.thinking = false;
            const parts = line.split(' ');
            const move = parts[1];
            if (this.onBestMove && move && move !== '(none)') {
                this.onBestMove(move);
            }
        }
    }

    setDifficulty(level) {
        this.difficulty = Math.max(1, Math.min(5, level));
        if (!this.worker || !this.ready) return;

        const settings = this.levels[this.difficulty - 1];
        this.worker.postMessage('setoption name Skill Level value ' + settings.skill);
    }

    getBestMove(fen, callback) {
        if (!this.worker || !this.ready || this.thinking) return;

        this.thinking = true;
        this.onBestMove = callback;

        const settings = this.levels[this.difficulty - 1];
        this.worker.postMessage('position fen ' + fen);
        this.worker.postMessage('go depth ' + settings.depth + ' movetime ' + settings.moveTime);
    }

    stop() {
        if (this.worker && this.thinking) {
            this.worker.postMessage('stop');
            this.thinking = false;
        }
    }

    newGame() {
        if (this.worker) {
            this.worker.postMessage('ucinewgame');
            this.worker.postMessage('isready');
        }
    }

    destroy() {
        if (this.worker) {
            this.worker.terminate();
            this.worker = null;
            this.ready = false;
        }
    }
}
