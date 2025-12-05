# Test-Sats Integration Konzept

**Erstellt:** 02. Dezember 2025  
**Version:** 1.0  
**Status:** Geplant

---

## 📋 ZUSAMMENFASSUNG

Integration eines Test-Sats Systems in die bestehende sgiT Education Platform. Die Test-Sats werden basierend auf dem aktuellen Bitcoin Live-Kurs berechnet, sind aber explizit als "nicht echte Sats" gekennzeichnet. Steuerung erfolgt über das Admin-Dashboard.

---

## 🎯 ZIELE

1. **Gamification:** Kinder sehen "Sats" als Belohnung (motivierender als abstrakte Punkte)
2. **Bitcoin-Education:** Kinder lernen den Zusammenhang Punkte ↔ Sats ↔ USD/EUR
3. **Live-Kurs:** Realitätsnähe durch echte Kursdaten
4. **Transparenz:** Klare Kennzeichnung als TEST-Sats
5. **Zukunftssicher:** Vorbereitung für BTCPay Server Integration

---

## 🏗️ ARCHITEKTUR

### Bestehendes System (bleibt unverändert)

```
adaptive_learning.php
├── Login: Name + Alter (5-15)
├── Level-System: Baby → Kind → Jugend → Erwachsen → Opa
├── Punkte: 3/5/7/10/15 pro richtige Antwort
├── Session: 10 Fragen, Score bleibt
└── Storage: PHP Session (Browser)
```

### Neue Komponenten

```
config.php (NEU)
├── TEST_SATS_ENABLED = true/false
├── TEST_SATS_MULTIPLIER = 0.01
└── TEST_SATS_SHOW_WARNING = true

adaptive_learning.php (ERWEITERT)
├── Test-Sats Anzeige im Header
├── Live-Kurs Abruf (Mempool API)
├── Berechnung: Punkte → Test-Sats
└── Warning-Banner

admin_v4.php (ERWEITERT)
├── Test-Sats Toggle (Ein/Aus)
├── Multiplikator Einstellung
├── Test-Sats Dashboard-Widget
└── "BTCPay Coming Soon" Banner
```

---

## 💰 BERECHNUNGSFORMEL

### Basis-Formel
```
Test-Sats = Punkte × Sats-per-USD × Multiplikator

Wobei:
- Sats-per-USD = 100.000.000 / BTC-USD-Preis
- Multiplikator = 0.01 (anpassbar im Admin)
```

### Beispielrechnung (BTC = $97.000)
```
Sats-per-USD = 100.000.000 / 97.000 = 1.031 Sats/USD

Baby (3 Punkte):
  3 × 1.031 × 0.01 = 0.031 ≈ 31 mSats (gerundet: 31 Test-Sats)

Kind (5 Punkte):
  5 × 1.031 × 0.01 = 0.052 ≈ 52 Test-Sats

Erwachsen (10 Punkte):
  10 × 1.031 × 0.01 = 0.103 ≈ 103 Test-Sats
```

### Anpassbarer Multiplikator
| Multiplikator | 3 Punkte → | 10 Punkte → | Beschreibung |
|---------------|------------|-------------|--------------|
| 0.001 | ~3 | ~10 | Sehr konservativ |
| 0.01 | ~31 | ~103 | **Standard** |
| 0.1 | ~309 | ~1.031 | Motivierend |
| 1.0 | ~3.093 | ~10.310 | Unrealistisch |

---

## 🖥️ UI DESIGN

### Header-Anzeige (adaptive_learning.php)

```
┌─────────────────────────────────────────────────────────────────┐
│ sgiT                    Hallo Colin (7 Jahre)     Abmelden     │
│ Adaptive Learning       👶 Baby (3 Punkte/Frage)               │
│ v4.3                    Gesamt-Score: 45                       │
│                         ₿ Test-Sats: ~1.485 ⚠️                 │
│                         └── BTC: $97.000                       │
└─────────────────────────────────────────────────────────────────┘
```

### Warning-Banner (unter Score)

```css
/* Styling */
.test-sats-warning {
    background: linear-gradient(135deg, #F7931A, #E88A00);
    color: white;
    padding: 5px 15px;
    border-radius: 8px;
    font-size: 11px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
```

```html
<div class="test-sats-warning">
    ⚠️ TEST-SATS - Keine echten Satoshis
</div>
```

### Admin Dashboard Widget

```
┌────────────────────────────────────────┐
│ ₿ Test-Sats System                     │
├────────────────────────────────────────┤
│ Status: [🟢 Aktiv] [Toggle]            │
│                                        │
│ Multiplikator: [0.01] [▼]              │
│                                        │
│ Aktuelle Werte:                        │
│ • BTC Preis: $97.000                   │
│ • Sats/USD: 1.031                      │
│ • 1 Punkt = ~10 Test-Sats              │
│                                        │
│ ┌──────────────────────────────────┐   │
│ │ 🔜 BTCPay Server Integration     │   │
│ │    Coming Soon!                  │   │
│ └──────────────────────────────────┘   │
└────────────────────────────────────────┘
```

---

## 📝 IMPLEMENTIERUNG

### Phase 1: Config (15 min)

**Datei:** `config.php`

```php
<?php
// Test-Sats System Konfiguration
define('TEST_SATS_ENABLED', true);
define('TEST_SATS_MULTIPLIER', 0.01);
define('TEST_SATS_SHOW_WARNING', true);
define('TEST_SATS_SHOW_BTC_PRICE', true);

// Mempool API (bereits im Admin verwendet)
define('MEMPOOL_API_URL', 'https://mempool.space/api/v1/prices');
```

### Phase 2: Berechnung (30 min)

**Datei:** `adaptive_learning.php` - Neue Funktionen

```php
<?php
/**
 * Hole aktuellen BTC Preis via Mempool API
 */
function getBTCPrice() {
    static $cache = null;
    static $cacheTime = 0;
    
    // Cache für 60 Sekunden
    if ($cache && (time() - $cacheTime) < 60) {
        return $cache;
    }
    
    try {
        $json = @file_get_contents('https://mempool.space/api/v1/prices');
        if ($json) {
            $data = json_decode($json, true);
            $cache = [
                'usd' => $data['USD'] ?? 0,
                'eur' => $data['EUR'] ?? 0,
                'sats_per_usd' => $data['USD'] > 0 ? round(100000000 / $data['USD']) : 0
            ];
            $cacheTime = time();
            return $cache;
        }
    } catch (Exception $e) {
        error_log("BTC API Error: " . $e->getMessage());
    }
    
    return ['usd' => 0, 'eur' => 0, 'sats_per_usd' => 0];
}

/**
 * Berechne Test-Sats aus Punkten
 */
function calculateTestSats($points) {
    if (!defined('TEST_SATS_ENABLED') || !TEST_SATS_ENABLED) {
        return 0;
    }
    
    $btc = getBTCPrice();
    $multiplier = defined('TEST_SATS_MULTIPLIER') ? TEST_SATS_MULTIPLIER : 0.01;
    
    if ($btc['sats_per_usd'] > 0) {
        return round($points * $btc['sats_per_usd'] * $multiplier);
    }
    
    return 0;
}
```

### Phase 3: UI Integration (30 min)

**Datei:** `adaptive_learning.php` - Header erweitern

```php
<!-- Test-Sats Anzeige (nur wenn aktiviert) -->
<?php if (defined('TEST_SATS_ENABLED') && TEST_SATS_ENABLED): ?>
    <?php $btc = getBTCPrice(); ?>
    <?php $testSats = calculateTestSats($_SESSION['total_score']); ?>
    
    <div class="test-sats-display">
        <div class="sats-value">₿ <?php echo number_format($testSats); ?> Test-Sats</div>
        <?php if (TEST_SATS_SHOW_BTC_PRICE && $btc['usd'] > 0): ?>
            <div class="btc-price">BTC: $<?php echo number_format($btc['usd']); ?></div>
        <?php endif; ?>
        <?php if (TEST_SATS_SHOW_WARNING): ?>
            <div class="test-sats-warning">⚠️ Keine echten Sats</div>
        <?php endif; ?>
    </div>
<?php endif; ?>
```

### Phase 4: Admin Toggle (30 min)

**Datei:** `admin_v4.php` - Neues Widget

```javascript
// Test-Sats System Toggle
async function toggleTestSats(enabled) {
    const formData = new FormData();
    formData.append('ajax_action', 'toggle_test_sats');
    formData.append('enabled', enabled ? '1' : '0');
    
    const res = await fetch(location.href, { method: 'POST', body: formData });
    const data = await res.json();
    
    if (data.success) {
        showToast('Test-Sats System ' + (enabled ? 'aktiviert' : 'deaktiviert'), 'success');
    }
}
```

---

## 🔜 NÄCHSTE SCHRITTE

Nach Abschluss der Test-Sats Integration:

### 1.7 Achievement-System
- Meilenstein-Badges (100 Sats, 1.000 Sats, etc.)
- Streak-Bonus (tägliches Lernen)
- Modul-Meister (alle Fragen in einem Modul)

### 1.8 Modul-Integration
- Reward-Hooks in alle 15 Module
- Session-Ende Zusammenfassung mit Sats

### 1.9 Kind-Dashboard
- Eigene Wallet-Ansicht für Kinder
- Achievements-Galerie
- Fortschritts-Anzeige

### 1.10 BTCPay Server (Später)
- Echte Bitcoin-Auszahlung
- Lightning Network Integration
- Eltern-Freigabe System

---

## ⚠️ WICHTIGE HINWEISE

1. **Keine echten Sats:** System ist rein motivational
2. **Live-Kurs:** Kann schwanken - UI sollte das zeigen
3. **Cache:** API-Calls sollten gecacht werden (60s)
4. **Fallback:** Bei API-Fehler → 0 Test-Sats anzeigen
5. **Transparenz:** Immer Warning-Banner zeigen

---

**Nächster Schritt:** Mit Achievement-System (1.7) weitermachen!
