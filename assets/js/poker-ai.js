/**
 * sgiT Education - Poker AI Engine v1.0
 * Texas Hold'em Hand-Evaluation & KI-Entscheidungslogik
 *
 * Handranking (hoch -> niedrig):
 *   9 = Royal Flush, 8 = Straight Flush, 7 = Four of a Kind,
 *   6 = Full House, 5 = Flush, 4 = Straight, 3 = Three of a Kind,
 *   2 = Two Pair, 1 = One Pair, 0 = High Card
 *
 * Kartenobjekte: { color: 'herz'|'karo'|'pik'|'kreuz', value: '2'-'10'|'bube'|'dame'|'koenig'|'ass' }
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 */

const POKER_AI = (() => {
    'use strict';

    // --- Value mapping ---
    const VALUE_ORDER = {
        '2': 2, '3': 3, '4': 4, '5': 5, '6': 6, '7': 7, '8': 8,
        '9': 9, '10': 10, 'bube': 11, 'dame': 12, 'koenig': 13, 'ass': 14
    };

    const VALUE_NAMES = {
        2: '2', 3: '3', 4: '4', 5: '5', 6: '6', 7: '7', 8: '8',
        9: '9', 10: '10', 11: 'Bube', 12: 'Dame', 13: 'Koenig', 14: 'Ass'
    };

    const HAND_NAMES = [
        'High Card', 'Ein Paar', 'Zwei Paare', 'Drilling',
        'Strasse', 'Flush', 'Full House', 'Vierling',
        'Straight Flush', 'Royal Flush'
    ];

    // --- Utilities ---

    function numericValue(card) {
        return VALUE_ORDER[card.value] || 0;
    }

    /**
     * Generate all C(n,k) combinations from an array.
     */
    function combinations(arr, k) {
        const result = [];
        function helper(start, combo) {
            if (combo.length === k) { result.push([...combo]); return; }
            for (let i = start; i < arr.length; i++) {
                combo.push(arr[i]);
                helper(i + 1, combo);
                combo.pop();
            }
        }
        helper(0, []);
        return result;
    }

    // --- 5-card hand evaluation ---

    /**
     * Evaluate exactly 5 cards. Returns { rank: 0-9, kickers: [...], name: string }
     * kickers is an array of numeric values used for tie-breaking (highest first).
     */
    function evaluate5(cards) {
        const vals = cards.map(c => numericValue(c)).sort((a, b) => b - a);
        const suits = cards.map(c => c.color);

        const isFlush = suits.every(s => s === suits[0]);

        // Check straight (including A-2-3-4-5 wheel)
        let isStraight = false;
        let straightHigh = 0;
        if (vals[0] - vals[4] === 4 && new Set(vals).size === 5) {
            isStraight = true;
            straightHigh = vals[0];
        }
        // Wheel: A-2-3-4-5
        if (!isStraight && vals[0] === 14 && vals[1] === 5 && vals[2] === 4 && vals[3] === 3 && vals[4] === 2) {
            isStraight = true;
            straightHigh = 5; // 5-high straight
        }

        // Count value occurrences
        const counts = {};
        for (const v of vals) counts[v] = (counts[v] || 0) + 1;
        const groups = Object.entries(counts)
            .map(([v, c]) => ({ val: parseInt(v), count: c }))
            .sort((a, b) => b.count - a.count || b.val - a.val);

        // Royal Flush
        if (isFlush && isStraight && straightHigh === 14) {
            return { rank: 9, kickers: [14], name: HAND_NAMES[9] };
        }
        // Straight Flush
        if (isFlush && isStraight) {
            return { rank: 8, kickers: [straightHigh], name: HAND_NAMES[8] };
        }
        // Four of a Kind
        if (groups[0].count === 4) {
            const quad = groups[0].val;
            const kick = groups[1].val;
            return { rank: 7, kickers: [quad, kick], name: HAND_NAMES[7] };
        }
        // Full House
        if (groups[0].count === 3 && groups[1].count === 2) {
            return { rank: 6, kickers: [groups[0].val, groups[1].val], name: HAND_NAMES[6] };
        }
        // Flush
        if (isFlush) {
            return { rank: 5, kickers: vals, name: HAND_NAMES[5] };
        }
        // Straight
        if (isStraight) {
            return { rank: 4, kickers: [straightHigh], name: HAND_NAMES[4] };
        }
        // Three of a Kind
        if (groups[0].count === 3) {
            const trip = groups[0].val;
            const kicks = groups.filter(g => g.count === 1).map(g => g.val).sort((a, b) => b - a);
            return { rank: 3, kickers: [trip, ...kicks], name: HAND_NAMES[3] };
        }
        // Two Pair
        if (groups[0].count === 2 && groups[1].count === 2) {
            const high = Math.max(groups[0].val, groups[1].val);
            const low = Math.min(groups[0].val, groups[1].val);
            const kick = groups[2].val;
            return { rank: 2, kickers: [high, low, kick], name: HAND_NAMES[2] };
        }
        // One Pair
        if (groups[0].count === 2) {
            const pair = groups[0].val;
            const kicks = groups.filter(g => g.count === 1).map(g => g.val).sort((a, b) => b - a);
            return { rank: 1, kickers: [pair, ...kicks], name: HAND_NAMES[1] };
        }
        // High Card
        return { rank: 0, kickers: vals, name: HAND_NAMES[0] };
    }

    /**
     * Compare two evaluated hands. Returns >0 if a wins, <0 if b wins, 0 if tie.
     */
    function compareHands(a, b) {
        if (a.rank !== b.rank) return a.rank - b.rank;
        for (let i = 0; i < Math.min(a.kickers.length, b.kickers.length); i++) {
            if (a.kickers[i] !== b.kickers[i]) return a.kickers[i] - b.kickers[i];
        }
        return 0;
    }

    // --- Public API ---

    /**
     * Evaluate the best 5-card hand from hole cards + community cards.
     * @param {Array} holeCards - 2 cards
     * @param {Array} communityCards - 0..5 cards
     * @returns {{ rank: number, name: string, cards: Array, kickers: Array }}
     */
    function evaluateHand(holeCards, communityCards) {
        const all = [...holeCards, ...communityCards];

        // Pre-flop: only 2 cards, return simple evaluation
        if (all.length < 5) {
            const vals = all.map(c => numericValue(c)).sort((a, b) => b - a);
            const suited = all.length >= 2 && all[0].color === all[1].color;
            const paired = all.length >= 2 && numericValue(all[0]) === numericValue(all[1]);
            if (paired) {
                return { rank: 1, name: HAND_NAMES[1], cards: all, kickers: vals };
            }
            return { rank: 0, name: HAND_NAMES[0], cards: all, kickers: vals, suited };
        }

        // Find the best 5-card combination
        const combos = combinations(all, 5);
        let best = null;
        let bestCards = null;

        for (const combo of combos) {
            const result = evaluate5(combo);
            if (!best || compareHands(result, best) > 0) {
                best = result;
                bestCards = combo;
            }
        }

        return {
            rank: best.rank,
            name: best.name,
            cards: bestCards,
            kickers: best.kickers
        };
    }

    /**
     * Calculate normalized hand strength (0..1).
     * Uses the hand rank plus kicker-based sub-ranking.
     * @param {Array} holeCards - 2 cards
     * @param {Array} communityCards - 0..5 cards
     * @returns {number} 0 (worst) to 1 (best)
     */
    function calculateHandStrength(holeCards, communityCards) {
        const hand = evaluateHand(holeCards, communityCards);

        // Base strength from rank (0-9 mapped to ranges)
        const rankBase = hand.rank / 9;

        // Sub-ranking within rank from kickers (normalized 0..1 within the rank tier)
        let kickerScore = 0;
        if (hand.kickers && hand.kickers.length > 0) {
            // Weighted kicker sum: first kicker most important
            let weight = 1;
            let total = 0;
            let maxTotal = 0;
            for (let i = 0; i < hand.kickers.length; i++) {
                total += hand.kickers[i] * weight;
                maxTotal += 14 * weight;
                weight *= 0.1;
            }
            kickerScore = total / maxTotal;
        }

        // Combine: each rank tier spans ~0.1, kickers fill the range within
        const strength = rankBase * 0.85 + kickerScore * 0.15;
        return Math.min(1, Math.max(0, strength));
    }

    /**
     * Pre-flop hole card strength heuristic (Chen formula simplified).
     * @param {Array} holeCards - exactly 2 cards
     * @returns {number} 0..1 normalized
     */
    function preFlopStrength(holeCards) {
        if (holeCards.length < 2) return 0.3;

        const v1 = numericValue(holeCards[0]);
        const v2 = numericValue(holeCards[1]);
        const high = Math.max(v1, v2);
        const low = Math.min(v1, v2);
        const gap = high - low;
        const suited = holeCards[0].color === holeCards[1].color;
        const paired = v1 === v2;

        let score = high; // Start with high card value (2-14)

        if (paired) {
            score = Math.max(score * 2, 5);
        }

        if (suited) score += 2;

        // Connectedness bonus
        if (gap === 1) score += 1;
        else if (gap === 2) score += 0;
        else if (gap === 3) score -= 1;
        else if (gap >= 4) score -= (gap - 3);

        // Both high cards bonus
        if (high >= 12 && low >= 10) score += 2;

        // Normalize to 0..1 (score range roughly -2 to 30)
        return Math.min(1, Math.max(0, (score + 2) / 32));
    }

    /**
     * AI decision engine. Returns the action for an AI player.
     * @param {number} strength - Hand strength 0..1
     * @param {number} potOdds - Cost to call / (pot + cost to call), 0..1
     * @param {number} difficulty - 1 (easy), 2 (medium), 3 (hard)
     * @param {string} stage - 'preflop'|'flop'|'turn'|'river'
     * @param {object} context - { callAmount, pot, chips, minRaise, bigBlind }
     * @returns {{ action: 'fold'|'call'|'raise', amount: number }}
     */
    function decideBetAction(strength, potOdds, difficulty, stage, context) {
        const { callAmount = 0, pot = 0, chips = 1000, minRaise = 20, bigBlind = 20 } = context || {};

        // Random factor for unpredictability
        const rand = Math.random();

        // --- Difficulty 1: Loose-Passive (Anfaenger) ---
        if (difficulty === 1) {
            // Rarely folds, calls a lot, almost never raises
            if (callAmount === 0) {
                // Check or small raise with very strong hand
                if (strength > 0.8 && rand < 0.2) {
                    return { action: 'raise', amount: Math.min(minRaise, chips) };
                }
                return { action: 'call', amount: 0 }; // check
            }
            if (strength < 0.15 && rand < 0.4) {
                return { action: 'fold', amount: 0 };
            }
            if (strength > 0.75 && rand < 0.15) {
                return { action: 'raise', amount: Math.min(minRaise, chips) };
            }
            return { action: 'call', amount: callAmount };
        }

        // --- Difficulty 2: Tight-Aggressive ---
        if (difficulty === 2) {
            const threshold = stage === 'preflop' ? 0.35 : 0.30;

            if (callAmount === 0) {
                if (strength > 0.65) {
                    const raiseAmt = Math.min(Math.round(pot * 0.5 + bigBlind), chips);
                    return { action: 'raise', amount: Math.max(minRaise, raiseAmt) };
                }
                if (strength > 0.3) {
                    return { action: 'call', amount: 0 }; // check
                }
                // Occasional bluff raise
                if (rand < 0.1) {
                    return { action: 'raise', amount: Math.min(minRaise, chips) };
                }
                return { action: 'call', amount: 0 }; // check
            }

            if (strength < threshold) {
                // Weak hand: mostly fold
                if (rand < 0.15) return { action: 'call', amount: callAmount }; // stubborn call
                return { action: 'fold', amount: 0 };
            }

            if (strength > 0.7) {
                const raiseAmt = Math.min(Math.round(pot * 0.6 + callAmount), chips);
                if (rand < 0.6) return { action: 'raise', amount: Math.max(minRaise, raiseAmt) };
                return { action: 'call', amount: callAmount };
            }

            return { action: 'call', amount: callAmount };
        }

        // --- Difficulty 3: Advanced (pot odds, bluffs) ---
        {
            // Effective pot odds comparison
            const effectivePotOdds = callAmount > 0 ? callAmount / (pot + callAmount) : 0;
            const equity = strength;

            if (callAmount === 0) {
                // No bet to call
                if (strength > 0.7) {
                    // Value bet: ~60-80% pot
                    const betSize = Math.round(pot * (0.5 + rand * 0.3));
                    return { action: 'raise', amount: Math.max(minRaise, Math.min(betSize, chips)) };
                }
                if (strength > 0.4) {
                    // Medium hand: sometimes bet for protection
                    if (rand < 0.35) {
                        const betSize = Math.round(pot * 0.4);
                        return { action: 'raise', amount: Math.max(minRaise, Math.min(betSize, chips)) };
                    }
                    return { action: 'call', amount: 0 };
                }
                // Weak hand: occasional bluff
                if (rand < 0.12 && stage !== 'river') {
                    const bluffSize = Math.round(pot * (0.5 + rand * 0.3));
                    return { action: 'raise', amount: Math.max(minRaise, Math.min(bluffSize, chips)) };
                }
                return { action: 'call', amount: 0 };
            }

            // Facing a bet
            if (equity > effectivePotOdds + 0.15) {
                // Good enough to raise
                if (strength > 0.75 && rand < 0.55) {
                    const raiseAmt = Math.round(pot * 0.7 + callAmount);
                    return { action: 'raise', amount: Math.max(minRaise, Math.min(raiseAmt, chips)) };
                }
                return { action: 'call', amount: callAmount };
            }

            if (equity > effectivePotOdds - 0.05) {
                // Marginal: call
                return { action: 'call', amount: callAmount };
            }

            // Below pot odds: usually fold, occasional bluff-raise
            if (rand < 0.08 && stage !== 'river') {
                const bluffRaise = Math.round(pot * 0.8);
                return { action: 'raise', amount: Math.max(minRaise, Math.min(bluffRaise, chips)) };
            }

            return { action: 'fold', amount: 0 };
        }
    }

    // --- Expose public API ---

    return {
        evaluateHand,
        calculateHandStrength,
        preFlopStrength,
        decideBetAction,
        compareHands,
        evaluate5,
        VALUE_ORDER,
        HAND_NAMES
    };
})();
