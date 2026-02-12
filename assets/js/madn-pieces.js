/**
 * sgiT Education - MADN (Mensch aergere dich nicht) Piece SVGs v1.0
 * Farbige Kegel/Pin-Spielfiguren
 * 4 Farben: rot, blau, gruen, gelb
 */

const MADN_PIECE_SVGS = (() => {
    const COLORS = {
        red:    { main: '#e74c3c', dark: '#c0392b', light: '#ff6b5b', stroke: '#a93226' },
        blue:   { main: '#3498db', dark: '#2980b9', light: '#5dade2', stroke: '#1f618d' },
        green:  { main: '#27ae60', dark: '#1e8449', light: '#52be80', stroke: '#196f3d' },
        yellow: { main: '#f1c40f', dark: '#d4ac0d', light: '#f4d03f', stroke: '#b7950b' }
    };

    function makeSVG(paths) {
        return 'data:image/svg+xml,' + encodeURIComponent(
            `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">${paths}</svg>`
        );
    }

    function createPiece(color) {
        const c = COLORS[color];
        return makeSVG(`
            <defs>
                <radialGradient id="g_${color}" cx="40%" cy="30%">
                    <stop offset="0%" stop-color="${c.light}"/>
                    <stop offset="100%" stop-color="${c.dark}"/>
                </radialGradient>
            </defs>
            <ellipse cx="20" cy="35" rx="10" ry="3" fill="rgba(0,0,0,0.25)"/>
            <path d="M12 34 Q12 20 15 12 Q17 6 20 4 Q23 6 25 12 Q28 20 28 34 Z"
                  fill="url(#g_${color})" stroke="${c.stroke}" stroke-width="1"/>
            <circle cx="20" cy="10" r="6" fill="url(#g_${color})" stroke="${c.stroke}" stroke-width="1"/>
            <ellipse cx="18" cy="8" rx="2" ry="1.5" fill="rgba(255,255,255,0.3)"/>
        `);
    }

    const pieces = {};
    for (const color of ['red', 'blue', 'green', 'yellow']) {
        pieces[color] = createPiece(color);
    }
    return pieces;
})();
