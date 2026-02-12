/**
 * sgiT Education - Playing Card SVGs v1.0
 * Professionelle SVG Spielkarten-Bibliothek
 * 52 Karten (4 Farben x 13 Werte) + Rueckseite + 2 Joker
 *
 * Zugriff: PLAYING_CARD_SVGS['herz_A'], PLAYING_CARD_SVGS['pik_10'], PLAYING_CARD_SVGS['back']
 * Namenskonvention: {farbe}_{wert} - herz, karo, pik, kreuz / 2-10, B, D, K, A
 * Auch mit Original-Werten: herz_bube, herz_dame, herz_koenig, herz_ass
 */

const PLAYING_CARD_SVGS = (() => {
    const RED = '#e74c3c';
    const BLACK = '#1a1a1a';
    const CARD_BG = '#fffef5';
    const CARD_BORDER = '#ccc';
    const BACK_PRIMARY = '#1a5276';
    const BACK_SECONDARY = '#2980b9';
    const BACK_ACCENT = '#f39c12';

    const SUIT_COLORS = { herz: RED, karo: RED, pik: BLACK, kreuz: BLACK };

    const SUIT_PATHS = {
        herz: '<path d="M35 20c0-8-6-14-13-14S9 12 9 20c0 16 26 28 26 28S61 36 61 20c0-8-6-14-13-14S35 12 35 20z" fill="currentColor"/>',
        karo: '<path d="M35 5L55 35L35 65L15 35Z" fill="currentColor"/>',
        pik: '<path d="M35 5C35 5 5 30 5 42c0 8 6 14 13 14c5 0 10-3 12-7v11h10v-11c2 4 7 7 12 7c7 0 13-6 13-14C65 30 35 5 35 5z" fill="currentColor"/>',
        kreuz: '<path d="M35 60v-12c-3 4-8 7-13 7c-7 0-13-6-13-13s6-13 13-13c3 0 6 1 8 3c-2-3-3-6-3-9c0-7 6-13 13-13s13 6 13 13c0 3-1 6-3 9c2-2 5-3 8-3c7 0 13 6 13 13s-6 13-13 13c-5 0-10-3-13-7v12H30z" fill="currentColor"/>'
    };

    const SUIT_SYMBOLS_SMALL = {
        herz: '<path d="M5 3c0-1.5-1-2.5-2.2-2.5S.5 2 .5 3c0 2.8 4.5 5 4.5 5S9.5 5.8 9.5 3c0-1.5-1-2.5-2.2-2.5S5 2 5 3z"/>',
        karo: '<path d="M5 0L9 5L5 10L1 5Z"/>',
        pik: '<path d="M5 0S0 4.5 0 6.5c0 1.5 1 2.5 2.2 2.5c.9 0 1.7-.5 2-1.2v2.2h1.6V7.8c.3.7 1.1 1.2 2 1.2C9 9 10 8 10 6.5 10 4.5 5 0 5 0z"/>',
        kreuz: '<path d="M5 10V8.2c-.5.6-1.3 1-2.2 1C1.3 9.2 0 7.9 0 6.4s1.3-2.2 2.8-2.2c.5 0 1 .1 1.3.4C3.8 4.2 3.5 3.6 3.5 2.8 3.5 1.3 4.2 0 5 0s1.5 1.3 1.5 2.8c0 .8-.3 1.4-.6 1.8.3-.3.8-.4 1.3-.4C8.7 4.2 10 5 10 6.4S8.7 9.2 7.2 9.2c-.9 0-1.7-.4-2.2-1V10z"/>'
    };

    const VALUE_DISPLAY = {
        '2': '2', '3': '3', '4': '4', '5': '5', '6': '6',
        '7': '7', '8': '8', '9': '9', '10': '10',
        'bube': 'B', 'dame': 'D', 'koenig': 'K', 'ass': 'A'
    };

    function makeSVG(content, w = 70, h = 100) {
        return 'data:image/svg+xml,' + encodeURIComponent(
            `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}">${content}</svg>`
        );
    }

    function createCardSVG(suit, value) {
        const color = SUIT_COLORS[suit];
        const display = VALUE_DISPLAY[value] || value;
        const suitSmall = SUIT_SYMBOLS_SMALL[suit];
        const fontSize = display.length > 1 ? '9' : '11';
        const textX = display.length > 1 ? '3' : '5';

        // Pip layout for number cards
        let centerContent = '';
        const numVal = parseInt(value);

        if (!isNaN(numVal) && numVal >= 2 && numVal <= 10) {
            centerContent = generatePips(suit, numVal, color);
        } else {
            // Face cards and Ace - large center symbol
            const faceSymbols = {
                'ass': generateAceCenter(suit, color),
                'bube': generateFaceCenter('B', color),
                'dame': generateFaceCenter('D', color),
                'koenig': generateFaceCenter('K', color)
            };
            centerContent = faceSymbols[value] || '';
        }

        return makeSVG(`
            <defs>
                <clipPath id="card-clip"><rect x="0" y="0" width="70" height="100" rx="6"/></clipPath>
            </defs>
            <rect x="0" y="0" width="70" height="100" rx="6" fill="${CARD_BG}" stroke="${CARD_BORDER}" stroke-width="1" clip-path="url(#card-clip)"/>
            <g fill="${color}" font-family="'Segoe UI',Arial,sans-serif" font-weight="bold">
                <text x="${textX}" y="14" font-size="${fontSize}">${display}</text>
                <g transform="translate(${textX}, 16) scale(0.7)">${suitSmall}</g>
            </g>
            <g fill="${color}" font-family="'Segoe UI',Arial,sans-serif" font-weight="bold" transform="rotate(180,35,50)">
                <text x="${textX}" y="14" font-size="${fontSize}">${display}</text>
                <g transform="translate(${textX}, 16) scale(0.7)">${suitSmall}</g>
            </g>
            ${centerContent}
        `);
    }

    function generatePips(suit, count, color) {
        const pipScale = 0.45;
        const pip = `<g fill="${color}" transform="scale(${pipScale})">${SUIT_SYMBOLS_SMALL[suit]}</g>`;

        // Pip positions for each card value (x, y, rotated)
        const layouts = {
            2: [[35,25],[35,75,true]],
            3: [[35,25],[35,50],[35,75,true]],
            4: [[22,25],[48,25],[22,75,true],[48,75,true]],
            5: [[22,25],[48,25],[35,50],[22,75,true],[48,75,true]],
            6: [[22,25],[48,25],[22,50],[48,50],[22,75,true],[48,75,true]],
            7: [[22,25],[48,25],[22,50],[48,50],[35,37],[22,75,true],[48,75,true]],
            8: [[22,25],[48,25],[22,50],[48,50],[35,37],[35,63,true],[22,75,true],[48,75,true]],
            9: [[22,22],[48,22],[22,40],[48,40],[35,50],[22,60,true],[48,60,true],[22,78,true],[48,78,true]],
            10: [[22,22],[48,22],[22,40],[48,40],[35,30],[35,50],[22,60,true],[48,60,true],[22,78,true],[48,78,true]]
        };

        const positions = layouts[count] || [];
        return positions.map(([x, y, rotated]) => {
            const ox = x - 2.2;
            const oy = y - 2.5;
            if (rotated) {
                return `<g transform="translate(${ox + 4.5}, ${oy + 5}) scale(${pipScale}) rotate(180, 5, 5)"  fill="${color}">${SUIT_SYMBOLS_SMALL[suit]}</g>`;
            }
            return `<g transform="translate(${ox}, ${oy}) scale(${pipScale})" fill="${color}">${SUIT_SYMBOLS_SMALL[suit]}</g>`;
        }).join('');
    }

    function generateAceCenter(suit, color) {
        return `<g transform="translate(22, 28) scale(0.37)" fill="${color}">${SUIT_PATHS[suit]}</g>`;
    }

    function generateFaceCenter(letter, color) {
        return `<text x="35" y="58" font-family="'Georgia','Times New Roman',serif" font-size="28" font-weight="bold" fill="${color}" text-anchor="middle" opacity="0.9">${letter}</text>`;
    }

    // Generate card back
    function createCardBack() {
        return makeSVG(`
            <rect x="0" y="0" width="70" height="100" rx="6" fill="${BACK_PRIMARY}" stroke="${BACK_SECONDARY}" stroke-width="1.5"/>
            <rect x="4" y="4" width="62" height="92" rx="4" fill="none" stroke="${BACK_ACCENT}" stroke-width="1" opacity="0.6"/>
            <rect x="7" y="7" width="56" height="86" rx="3" fill="${BACK_SECONDARY}" opacity="0.3"/>
            <g opacity="0.4" fill="${BACK_ACCENT}">
                ${Array.from({length: 7}, (_, i) =>
                    Array.from({length: 10}, (_, j) =>
                        `<rect x="${10 + i * 8}" y="${8 + j * 9}" width="4" height="4" rx="1" transform="rotate(45,${12 + i * 8},${10 + j * 9})"/>`
                    ).join('')
                ).join('')}
            </g>
            <text x="35" y="55" font-family="'Georgia',serif" font-size="16" font-weight="bold" fill="${BACK_ACCENT}" text-anchor="middle" opacity="0.7">sgiT</text>
        `);
    }

    // Generate joker
    function createJoker(color) {
        const fill = color === 'red' ? RED : BLACK;
        return makeSVG(`
            <rect x="0" y="0" width="70" height="100" rx="6" fill="${CARD_BG}" stroke="${CARD_BORDER}" stroke-width="1"/>
            <text x="5" y="14" font-family="'Segoe UI',Arial,sans-serif" font-size="9" font-weight="bold" fill="${fill}">JKR</text>
            <text x="35" y="42" font-size="22" text-anchor="middle">🃏</text>
            <text x="35" y="65" font-family="'Georgia',serif" font-size="14" font-weight="bold" fill="${fill}" text-anchor="middle">JOKER</text>
            <g fill="${fill}" font-family="'Segoe UI',Arial,sans-serif" font-weight="bold" transform="rotate(180,35,50)">
                <text x="5" y="14" font-size="9">JKR</text>
            </g>
        `);
    }

    // Build full card set
    const cards = {};
    const suits = ['herz', 'karo', 'pik', 'kreuz'];
    const values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'bube', 'dame', 'koenig', 'ass'];
    const shortValues = { 'bube': 'B', 'dame': 'D', 'koenig': 'K', 'ass': 'A' };

    for (const suit of suits) {
        for (const value of values) {
            const svg = createCardSVG(suit, value);
            cards[`${suit}_${value}`] = svg;
            // Short aliases: herz_A, pik_B, karo_D, kreuz_K
            if (shortValues[value]) {
                cards[`${suit}_${shortValues[value]}`] = svg;
            }
        }
    }

    cards.back = createCardBack();
    cards.joker_red = createJoker('red');
    cards.joker_black = createJoker('black');

    // Utility: Get card key from card object {color, value}
    cards.getKey = function(card) {
        return `${card.color}_${card.value}`;
    };

    // Utility: Create img element
    cards.createImg = function(key, className) {
        const img = document.createElement('img');
        img.src = this[key] || this.back;
        img.alt = key;
        img.draggable = false;
        if (className) img.className = className;
        return img;
    };

    return cards;
})();
