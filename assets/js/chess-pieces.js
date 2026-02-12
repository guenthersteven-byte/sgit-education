/**
 * sgiT Education - Chess Piece SVGs v1.0
 * Professionelle Staunton-Style SVG Schachfiguren
 * Weiss: Heller Fill mit dunkler Outline
 * Schwarz: Dunkler Fill mit Outline
 */

const CHESS_PIECE_SVGS = (() => {
    const W = '#fff';     // White fill
    const WS = '#222';    // White stroke
    const B = '#1a1a1a';  // Black fill
    const BS = '#1a1a1a'; // Black stroke
    const BH = '#666';    // Black highlight

    function makeSVG(paths, w = 45, h = 45) {
        return 'data:image/svg+xml,' + encodeURIComponent(
            `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}">${paths}</svg>`
        );
    }

    // ===== WHITE PIECES =====

    const wK = makeSVG(`
        <g fill="${W}" stroke="${WS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 22.5 11.63 L 22.5 6" stroke-linejoin="miter"/>
            <path d="M 20 8 L 25 8" stroke-linejoin="miter"/>
            <path d="M 22.5 25 C 22.5 25 27 17.5 25.5 14.5 C 25.5 14.5 24.5 12 22.5 12 C 20.5 12 19.5 14.5 19.5 14.5 C 18 17.5 22.5 25 22.5 25" stroke-linecap="butt" stroke-linejoin="miter"/>
            <path d="M 12.5 37 C 18 40.5 27 40.5 32.5 37 L 32.5 30 C 32.5 30 41.5 25.5 38.5 19.5 C 34.5 13 25 16 22.5 23.5 L 22.5 27 L 22.5 23.5 C 20 16 10.5 13 6.5 19.5 C 3.5 25.5 12.5 30 12.5 30 L 12.5 37"/>
            <path d="M 12.5 30 C 18 27 27 27 32.5 30" fill="none"/>
            <path d="M 12.5 33.5 C 18 30.5 27 30.5 32.5 33.5" fill="none"/>
            <path d="M 12.5 37 C 18 34 27 34 32.5 37" fill="none"/>
        </g>
    `);

    const wQ = makeSVG(`
        <g fill="${W}" stroke="${WS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 9 26 C 17.5 24.5 30 24.5 36 26 L 38.5 13.5 L 31 25 L 30.7 10.9 L 25.5 24.5 L 22.5 10 L 19.5 24.5 L 14.3 10.9 L 14 25 L 6.5 13.5 L 9 26 Z"/>
            <path d="M 9 26 C 9 28 10.5 30 10.5 30 C 18 29.5 27 29.5 34.5 30 C 34.5 30 36 28 36 26 C 27.5 24.5 17.5 24.5 9 26 Z"/>
            <path d="M 11.5 30 C 15 29 30 29 33.5 30" fill="none"/>
            <path d="M 12 33.5 C 18 31 27 31 33 33.5" fill="none"/>
            <path d="M 10.5 36 C 18 34 27 34 34.5 36" fill="none"/>
            <path d="M 11 38.5 C 18 36 27 36 34 38.5 L 34.5 36 C 27 34 18 34 10.5 36 L 11 38.5 Z"/>
            <circle cx="6" cy="12" r="2.5"/>
            <circle cx="14" cy="9" r="2.5"/>
            <circle cx="22.5" cy="8" r="2.5"/>
            <circle cx="31" cy="9" r="2.5"/>
            <circle cx="39" cy="12" r="2.5"/>
        </g>
    `);

    const wR = makeSVG(`
        <g fill="${W}" stroke="${WS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 9 39 L 36 39 L 36 36 L 9 36 L 9 39 Z"/>
            <path d="M 12.5 32 L 14 29.5 L 31 29.5 L 32.5 32 L 12.5 32 Z"/>
            <path d="M 12 36 L 12 32 L 33 32 L 33 36 L 12 36 Z"/>
            <path d="M 14 29.5 L 14 16.5 L 31 16.5 L 31 29.5 L 14 29.5 Z"/>
            <path d="M 14 16.5 L 11 14 L 11 9 L 15 9 L 15 11 L 20 11 L 20 9 L 25 9 L 25 11 L 30 11 L 30 9 L 34 9 L 34 14 L 31 16.5 L 14 16.5 Z"/>
            <path d="M 11 14 L 34 14" fill="none"/>
        </g>
    `);

    const wB = makeSVG(`
        <g fill="${W}" stroke="${WS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 9 36 C 12.39 35.03 19.11 36.43 22.5 34 C 25.89 36.43 32.61 35.03 36 36 C 36 36 37.65 36.54 39 38 C 38.32 38.97 37.35 38.99 36 38.5 C 32.61 37.53 25.89 38.96 22.5 37.5 C 19.11 38.96 12.39 37.53 9 38.5 C 7.65 38.99 6.68 38.97 6 38 C 7.35 36.54 9 36 9 36 Z"/>
            <path d="M 15 32 C 17.5 34.5 27.5 34.5 30 32 C 30.5 30.5 30 30 30 30 C 30 27.5 27.5 26 27.5 26 C 33 24.5 33.5 14.5 22.5 10.5 C 11.5 14.5 12 24.5 17.5 26 C 17.5 26 15 27.5 15 30 C 15 30 14.5 30.5 15 32 Z"/>
            <path d="M 25 8 A 2.5 2.5 0 1 1 20 8 A 2.5 2.5 0 1 1 25 8 Z"/>
            <path d="M 17.5 26 L 27.5 26 M 15 30 L 30 30 M 22.5 15.5 L 22.5 20.5 M 20 18 L 25 18" fill="none"/>
        </g>
    `);

    const wN = makeSVG(`
        <g fill="${W}" stroke="${WS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 22 10 C 32.5 11 38.5 18 38 39 L 15 39 C 15 30 25 32.5 23 18"/>
            <path d="M 24 18 C 24.38 20.91 18.45 25.37 16 27 C 13 29 13.18 31.34 11 31 C 9.96 30.66 12.3 27.21 11 28 C 10 28.5 11.19 29.23 10 30 C 9 30 5.97 31 6 26 C 6 24 12 14 12 14 C 12 14 13.89 12.1 14 10.5 C 13.27 9.51 13.5 8.5 13.5 7.5 C 14.5 6.5 16.5 10 16.5 10 L 18.5 10 C 18.5 10 19.28 8.01 21 7 C 22 7 22 10 22 10"/>
            <circle cx="12" cy="25.5" r="0.5" fill="${WS}" stroke="none"/>
            <path d="M 13 15.5 C 13 15.5 14.5 15.5 15 16.5 C 15.5 17.5 14.2 19.3 13.5 19 C 12.8 18.7 13 15.5 13 15.5" fill="${WS}" stroke="none"/>
        </g>
    `);

    const wP = makeSVG(`
        <g fill="${W}" stroke="${WS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 22.5 9 C 19.79 9 17.61 11.18 17.61 13.89 C 17.61 15.16 18.12 16.31 18.94 17.17 C 16.08 18.67 14.06 21.63 14.06 25.06 C 14.06 27.1 14.79 28.95 16 30.39 L 16 33 L 29 33 L 29 30.39 C 30.21 28.95 30.94 27.1 30.94 25.06 C 30.94 21.63 28.92 18.67 26.06 17.17 C 26.88 16.31 27.39 15.16 27.39 13.89 C 27.39 11.18 25.21 9 22.5 9 Z"/>
            <path d="M 10.5 36 L 34.5 36" fill="none"/>
            <path d="M 12 33 L 33 33 L 34.5 36 L 10.5 36 L 12 33 Z"/>
            <path d="M 9 39 L 36 39 L 36 36 L 9 36 L 9 39 Z"/>
        </g>
    `);

    // ===== BLACK PIECES =====

    const bK = makeSVG(`
        <g fill="${B}" stroke="${BS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 22.5 11.63 L 22.5 6" stroke-linejoin="miter"/>
            <path d="M 20 8 L 25 8" stroke-linejoin="miter"/>
            <path d="M 22.5 25 C 22.5 25 27 17.5 25.5 14.5 C 25.5 14.5 24.5 12 22.5 12 C 20.5 12 19.5 14.5 19.5 14.5 C 18 17.5 22.5 25 22.5 25" fill="${B}" stroke-linecap="butt" stroke-linejoin="miter"/>
            <path d="M 12.5 37 C 18 40.5 27 40.5 32.5 37 L 32.5 30 C 32.5 30 41.5 25.5 38.5 19.5 C 34.5 13 25 16 22.5 23.5 L 22.5 27 L 22.5 23.5 C 20 16 10.5 13 6.5 19.5 C 3.5 25.5 12.5 30 12.5 30 L 12.5 37"/>
            <path d="M 12.5 30 C 18 27 27 27 32.5 30" fill="none" stroke="#fff" stroke-width="1"/>
            <path d="M 12.5 33.5 C 18 30.5 27 30.5 32.5 33.5" fill="none" stroke="#fff" stroke-width="1"/>
            <path d="M 12.5 37 C 18 34 27 34 32.5 37" fill="none" stroke="#fff" stroke-width="1"/>
        </g>
    `);

    const bQ = makeSVG(`
        <g fill="${B}" stroke="${BS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="6" cy="12" r="2.5" stroke="none"/>
            <circle cx="14" cy="9" r="2.5" stroke="none"/>
            <circle cx="22.5" cy="8" r="2.5" stroke="none"/>
            <circle cx="31" cy="9" r="2.5" stroke="none"/>
            <circle cx="39" cy="12" r="2.5" stroke="none"/>
            <circle cx="6" cy="12" r="2.5" fill="none" stroke="${BS}"/>
            <circle cx="14" cy="9" r="2.5" fill="none" stroke="${BS}"/>
            <circle cx="22.5" cy="8" r="2.5" fill="none" stroke="${BS}"/>
            <circle cx="31" cy="9" r="2.5" fill="none" stroke="${BS}"/>
            <circle cx="39" cy="12" r="2.5" fill="none" stroke="${BS}"/>
            <path d="M 9 26 C 17.5 24.5 30 24.5 36 26 L 38.5 13.5 L 31 25 L 30.7 10.9 L 25.5 24.5 L 22.5 10 L 19.5 24.5 L 14.3 10.9 L 14 25 L 6.5 13.5 L 9 26 Z" stroke-linecap="butt"/>
            <path d="M 9 26 C 9 28 10.5 30 10.5 30 C 18 29.5 27 29.5 34.5 30 C 34.5 30 36 28 36 26 C 27.5 24.5 17.5 24.5 9 26 Z" stroke-linecap="butt"/>
            <path d="M 11.5 30 C 15 29 30 29 33.5 30" fill="none" stroke="#fff" stroke-width="1"/>
            <path d="M 12 33.5 C 18 31 27 31 33 33.5" fill="none" stroke="#fff" stroke-width="1"/>
            <path d="M 10.5 36 C 18 34 27 34 34.5 36" fill="none" stroke="#fff" stroke-width="1"/>
            <path d="M 11 38.5 C 18 36 27 36 34 38.5 L 34.5 36 C 27 34 18 34 10.5 36 L 11 38.5 Z"/>
        </g>
    `);

    const bR = makeSVG(`
        <g fill="${B}" stroke="${BS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 9 39 L 36 39 L 36 36 L 9 36 L 9 39 Z"/>
            <path d="M 12.5 32 L 14 29.5 L 31 29.5 L 32.5 32 L 12.5 32 Z"/>
            <path d="M 12 36 L 12 32 L 33 32 L 33 36 L 12 36 Z"/>
            <path d="M 14 29.5 L 14 16.5 L 31 16.5 L 31 29.5 L 14 29.5 Z"/>
            <path d="M 14 16.5 L 11 14 L 11 9 L 15 9 L 15 11 L 20 11 L 20 9 L 25 9 L 25 11 L 30 11 L 30 9 L 34 9 L 34 14 L 31 16.5 L 14 16.5 Z"/>
            <path d="M 11 14 L 34 14" fill="none" stroke="#fff" stroke-width="1"/>
            <path d="M 12 31.5 L 33 31.5" fill="none" stroke="#fff" stroke-width="1"/>
            <path d="M 14 16.5 L 31 16.5" fill="none" stroke="#fff" stroke-width="1"/>
        </g>
    `);

    const bB = makeSVG(`
        <g fill="${B}" stroke="${BS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 9 36 C 12.39 35.03 19.11 36.43 22.5 34 C 25.89 36.43 32.61 35.03 36 36 C 36 36 37.65 36.54 39 38 C 38.32 38.97 37.35 38.99 36 38.5 C 32.61 37.53 25.89 38.96 22.5 37.5 C 19.11 38.96 12.39 37.53 9 38.5 C 7.65 38.99 6.68 38.97 6 38 C 7.35 36.54 9 36 9 36 Z"/>
            <path d="M 15 32 C 17.5 34.5 27.5 34.5 30 32 C 30.5 30.5 30 30 30 30 C 30 27.5 27.5 26 27.5 26 C 33 24.5 33.5 14.5 22.5 10.5 C 11.5 14.5 12 24.5 17.5 26 C 17.5 26 15 27.5 15 30 C 15 30 14.5 30.5 15 32 Z"/>
            <circle cx="22.5" cy="8" r="2.5"/>
            <path d="M 17.5 26 L 27.5 26 M 15 30 L 30 30" fill="none" stroke="#fff" stroke-width="1"/>
            <path d="M 22.5 15.5 L 22.5 20.5 M 20 18 L 25 18" fill="none" stroke="#fff" stroke-width="1"/>
        </g>
    `);

    const bN = makeSVG(`
        <g fill="${B}" stroke="${BS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 22 10 C 32.5 11 38.5 18 38 39 L 15 39 C 15 30 25 32.5 23 18"/>
            <path d="M 24 18 C 24.38 20.91 18.45 25.37 16 27 C 13 29 13.18 31.34 11 31 C 9.96 30.66 12.3 27.21 11 28 C 10 28.5 11.19 29.23 10 30 C 9 30 5.97 31 6 26 C 6 24 12 14 12 14 C 12 14 13.89 12.1 14 10.5 C 13.27 9.51 13.5 8.5 13.5 7.5 C 14.5 6.5 16.5 10 16.5 10 L 18.5 10 C 18.5 10 19.28 8.01 21 7 C 22 7 22 10 22 10"/>
            <circle cx="12" cy="25.5" r="0.5" fill="#fff" stroke="none"/>
            <path d="M 13 15.5 C 13 15.5 14.5 15.5 15 16.5 C 15.5 17.5 14.2 19.3 13.5 19 C 12.8 18.7 13 15.5 13 15.5" fill="#fff" stroke="none"/>
        </g>
    `);

    const bP = makeSVG(`
        <g fill="${B}" stroke="${BS}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M 22.5 9 C 19.79 9 17.61 11.18 17.61 13.89 C 17.61 15.16 18.12 16.31 18.94 17.17 C 16.08 18.67 14.06 21.63 14.06 25.06 C 14.06 27.1 14.79 28.95 16 30.39 L 16 33 L 29 33 L 29 30.39 C 30.21 28.95 30.94 27.1 30.94 25.06 C 30.94 21.63 28.92 18.67 26.06 17.17 C 26.88 16.31 27.39 15.16 27.39 13.89 C 27.39 11.18 25.21 9 22.5 9 Z"/>
            <path d="M 10.5 36 L 34.5 36" fill="none"/>
            <path d="M 12 33 L 33 33 L 34.5 36 L 10.5 36 L 12 33 Z"/>
            <path d="M 9 39 L 36 39 L 36 36 L 9 36 L 9 39 Z"/>
        </g>
    `);

    return { wK, wQ, wR, wB, wN, wP, bK, bQ, bR, bB, bN, bP };
})();
