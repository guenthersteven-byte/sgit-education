/**
 * sgiT Education - Dame (Checkers) Piece SVGs v1.0
 * Professionelle runde Spielsteine mit Schatten
 * CI-Farben: Gruen + Schwarz (statt Rot + Schwarz)
 *
 * Zugriff: DAME_PIECE_SVGS.green, DAME_PIECE_SVGS.greenKing, etc.
 */

const DAME_PIECE_SVGS = (() => {
    const GREEN = '#2a5a0a';
    const GREEN_LIGHT = '#4a8a2a';
    const GREEN_DARK = '#1a3503';
    const BLACK = '#1a1a1a';
    const BLACK_LIGHT = '#3a3a3a';
    const BLACK_DARK = '#0a0a0a';
    const CROWN = '#f39c12';

    function makeSVG(paths) {
        return 'data:image/svg+xml,' + encodeURIComponent(
            `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50">${paths}</svg>`
        );
    }

    const green = makeSVG(`
        <defs><radialGradient id="gg" cx="40%" cy="35%"><stop offset="0%" stop-color="${GREEN_LIGHT}"/><stop offset="100%" stop-color="${GREEN_DARK}"/></radialGradient></defs>
        <circle cx="25" cy="28" r="18" fill="rgba(0,0,0,0.3)"/>
        <circle cx="25" cy="25" r="18" fill="url(#gg)" stroke="${GREEN}" stroke-width="2"/>
        <ellipse cx="25" cy="20" rx="10" ry="6" fill="rgba(255,255,255,0.15)"/>
    `);

    const greenKing = makeSVG(`
        <defs><radialGradient id="gkg" cx="40%" cy="35%"><stop offset="0%" stop-color="${GREEN_LIGHT}"/><stop offset="100%" stop-color="${GREEN_DARK}"/></radialGradient></defs>
        <circle cx="25" cy="28" r="18" fill="rgba(0,0,0,0.3)"/>
        <circle cx="25" cy="25" r="18" fill="url(#gkg)" stroke="${GREEN}" stroke-width="2"/>
        <ellipse cx="25" cy="20" rx="10" ry="6" fill="rgba(255,255,255,0.15)"/>
        <path d="M14 22L18 12L22 18L25 10L28 18L32 12L36 22Z" fill="${CROWN}" stroke="#c17d0e" stroke-width="0.8"/>
        <circle cx="18" cy="12" r="1.5" fill="${CROWN}"/><circle cx="25" cy="10" r="1.5" fill="${CROWN}"/><circle cx="32" cy="12" r="1.5" fill="${CROWN}"/>
    `);

    const black = makeSVG(`
        <defs><radialGradient id="bg" cx="40%" cy="35%"><stop offset="0%" stop-color="${BLACK_LIGHT}"/><stop offset="100%" stop-color="${BLACK_DARK}"/></radialGradient></defs>
        <circle cx="25" cy="28" r="18" fill="rgba(0,0,0,0.3)"/>
        <circle cx="25" cy="25" r="18" fill="url(#bg)" stroke="#444" stroke-width="2"/>
        <ellipse cx="25" cy="20" rx="10" ry="6" fill="rgba(255,255,255,0.1)"/>
    `);

    const blackKing = makeSVG(`
        <defs><radialGradient id="bkg" cx="40%" cy="35%"><stop offset="0%" stop-color="${BLACK_LIGHT}"/><stop offset="100%" stop-color="${BLACK_DARK}"/></radialGradient></defs>
        <circle cx="25" cy="28" r="18" fill="rgba(0,0,0,0.3)"/>
        <circle cx="25" cy="25" r="18" fill="url(#bkg)" stroke="#444" stroke-width="2"/>
        <ellipse cx="25" cy="20" rx="10" ry="6" fill="rgba(255,255,255,0.1)"/>
        <path d="M14 22L18 12L22 18L25 10L28 18L32 12L36 22Z" fill="${CROWN}" stroke="#c17d0e" stroke-width="0.8"/>
        <circle cx="18" cy="12" r="1.5" fill="${CROWN}"/><circle cx="25" cy="10" r="1.5" fill="${CROWN}"/><circle cx="32" cy="12" r="1.5" fill="${CROWN}"/>
    `);

    return { green, greenKing, black, blackKing };
})();
