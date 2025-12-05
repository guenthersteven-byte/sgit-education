# sgiT Education - Bitcoin Reward System Konzept

**Erstellt:** 02. Dezember 2025  
**Version:** 0.1 (Konzeptphase)  
**Autor:** sgiT Solution Engineering

---

## 🎯 VISION

Kinder verdienen **echte Sats** durch Lernerfolge - ein digitales Sparschwein, das Bildung mit realem Wert verbindet.

---

## 💡 KERNIDEE

| Rolle | Funktion |
|-------|----------|
| **Eltern** | Laden Family-Wallet auf (Sparschwein) |
| **Kinder** | Verdienen Sats durch Module/Achievements |
| **System** | Verwaltet Transfers, Tracking, Limits |

**Pädagogischer Mehrwert:**
- Kinder lernen den Wert von Geld/Bitcoin
- Direkte Verbindung: Lernen → Belohnung
- Eigenverantwortung durch eigene Wallet
- Praxisbezug zum Bitcoin-Modul

---

## 🏗️ ARCHITEKTUR-OPTIONEN

### Option A: Interne Sats (Einfach)
```
[Eltern-Wallet] ──deposit──> [Family Pool SQLite]
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
              [Kind 1 Sats]   [Kind 2 Sats]   [Withdraw Request]
```
- **Pro:** Kein externer Service, volle Kontrolle, offline-fähig
- **Con:** Kein echtes BTC bis Auszahlung

### Option B: BTCPay Server Integration (Empfohlen)
```
[BTCPay Server lokal]
        │
        ├── Lightning Wallet (Hot - für Rewards)
        │       │
        │       └── [Education Platform API]
        │               │
        │       ┌───────┴───────┐
        │       ▼               ▼
        │   [Kind 1]       [Kind 2]
        │   Lightning      Lightning
        │   Address        Address
        │
        └── Cold Storage (Savings)
```
- **Pro:** Echtes BTC, Lightning für Micro-Payments, Self-Custody
- **Con:** Komplexeres Setup, Node erforderlich

### Option C: Hybrid (Pragmatisch) ⭐ EMPFOHLEN
```
[Eltern laden auf] ──> [SQLite Ledger] ──> [Kinder verdienen]
                              │
                      [Manueller Withdraw]
                              │
                              ▼
                    [Echte BTC Wallet]
```
- **Pro:** Einfach zu implementieren, echtes BTC bei Auszahlung
- **Con:** Nicht vollautomatisch

---

## 📊 REWARD-STRUKTUR (Vorschlag)

### Modul-Belohnungen
| Aktion | Sats | Bemerkung |
|--------|------|-----------|
| Session abgeschlossen (10 Fragen) | 10-50 | Je nach Score |
| 100% Score | +25 Bonus | Perfekte Runde |
| Neues Modul gestartet | 5 | Motivation |
| Tägliches Login | 5 | Konsistenz |

### Achievement-Belohnungen
| Achievement | Sats | Bedingung |
|-------------|------|-----------|
| 🥉 Bronze Mathe | 100 | 10 Sessions |
| 🥈 Silber Mathe | 250 | 50 Sessions |
| 🥇 Gold Mathe | 500 | 100 Sessions |
| 🏆 Meister | 1000 | Alle Module Gold |
| 📚 Bücherwurm | 50 | 7 Tage Streak |
| 🔥 Feuerstreak | 200 | 30 Tage Streak |

### Eltern-Kontrolle
| Setting | Beschreibung |
|---------|--------------|
| `daily_limit` | Max. Sats pro Tag (z.B. 100) |
| `weekly_limit` | Max. Sats pro Woche (z.B. 500) |
| `min_score` | Mindest-Score für Reward (z.B. 60%) |
| `withdraw_approval` | Eltern müssen Auszahlung bestätigen |

---

## 🗄️ DATENBANK-SCHEMA

### Neue Tabellen

```sql
-- Family Wallet (Sparschwein der Eltern)
CREATE TABLE family_wallet (
    id INTEGER PRIMARY KEY,
    balance_sats INTEGER DEFAULT 0,
    total_deposited INTEGER DEFAULT 0,
    total_distributed INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Kind-Wallets
CREATE TABLE child_wallets (
    id INTEGER PRIMARY KEY,
    child_name TEXT NOT NULL,
    balance_sats INTEGER DEFAULT 0,
    total_earned INTEGER DEFAULT 0,
    total_withdrawn INTEGER DEFAULT 0,
    btc_address TEXT,  -- Für echte Auszahlungen
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Transaktions-Log
CREATE TABLE sat_transactions (
    id INTEGER PRIMARY KEY,
    child_id INTEGER,
    type TEXT CHECK(type IN ('earn', 'withdraw', 'bonus', 'penalty')),
    amount_sats INTEGER NOT NULL,
    reason TEXT,
    module TEXT,
    session_id TEXT,
    score INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES child_wallets(id)
);

-- Achievements
CREATE TABLE achievements (
    id INTEGER PRIMARY KEY,
    child_id INTEGER,
    achievement_key TEXT NOT NULL,
    achievement_name TEXT NOT NULL,
    reward_sats INTEGER,
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES child_wallets(id)
);

-- Reward-Konfiguration
CREATE TABLE reward_config (
    id INTEGER PRIMARY KEY,
    config_key TEXT UNIQUE NOT NULL,
    config_value TEXT NOT NULL,
    description TEXT
);
```

---

## 🔧 IMPLEMENTATION ROADMAP

### Phase 1: Internes Ledger (1-2 Tage)
- [ ] Datenbank-Schema erstellen
- [ ] SQLite Tabellen anlegen
- [ ] Basis-API für Sat-Transaktionen
- [ ] Integration in bestehende Module

### Phase 2: UI/Dashboard (2-3 Tage)
- [ ] Kind-Dashboard mit Wallet-Anzeige
- [ ] Eltern-Dashboard mit Übersicht
- [ ] Transaktions-Historie
- [ ] Achievement-Galerie

### Phase 3: Achievement-System (1-2 Tage)
- [ ] Achievement-Definitionen
- [ ] Trigger-Logik
- [ ] Benachrichtigungen
- [ ] Badge-Anzeige

### Phase 4: BTCPay Integration (Optional, 3-5 Tage)
- [ ] BTCPay Server Setup (Docker)
- [ ] API-Integration
- [ ] Lightning Wallet
- [ ] Echte Auszahlungen

---

## 🔒 SICHERHEIT

### Lokal = Sicher
| Aspekt | Maßnahme |
|--------|----------|
| Zugriff | Nur lokales Netzwerk |
| Daten | SQLite verschlüsselt (optional) |
| Auszahlung | Eltern-PIN erforderlich |
| Limits | Tägliche/Wöchentliche Caps |

### Bei BTCPay Integration
| Aspekt | Maßnahme |
|--------|----------|
| Hot Wallet | Nur kleine Beträge |
| Cold Storage | Hauptspareinlagen |
| 2FA | Für Eltern-Zugang |
| Backup | Seed Phrase sicher verwahrt |

---

## 💰 KOSTEN-BEISPIEL

### Szenario: 2 Kinder, 1 Jahr
```
Tägliches Lernen: ~50 Sats/Kind/Tag
Monatlich: ~1.500 Sats/Kind = 3.000 Sats Familie
Jährlich: ~36.000 Sats = ca. 36€ (bei 100k Sats/€)

Achievements Bonus: ~10.000 Sats/Jahr
────────────────────────────────────
Gesamt: ~46.000 Sats ≈ 46€/Jahr
```

**Fazit:** Sehr günstiges Belohnungssystem mit echtem Lerneffekt!

---

## 🎮 UX KONZEPT

### Kind sieht nach Session:
```
┌─────────────────────────────────────┐
│  🎉 Super gemacht!                  │
│                                     │
│  Score: 8/10 (80%)                  │
│                                     │
│  ⚡ +40 Sats verdient!              │
│                                     │
│  Wallet: 1.234 Sats                 │
│  ████████████░░░ 82% zum nächsten   │
│                   Achievement       │
│                                     │
│  [Weiter lernen]  [Wallet ansehen]  │
└─────────────────────────────────────┘
```

### Eltern-Dashboard:
```
┌─────────────────────────────────────┐
│  💰 Family Wallet                   │
│  ═══════════════                    │
│  Balance: 50.000 Sats               │
│  Diesen Monat verteilt: 2.340 Sats  │
│                                     │
│  👧 Emma:    1.234 Sats  [Details]  │
│  👦 Max:       890 Sats  [Details]  │
│                                     │
│  [+ Aufladen]  [Einstellungen]      │
└─────────────────────────────────────┘
```

---

## ✅ MACHBARKEIT: JA!

| Kriterium | Bewertung |
|-----------|-----------|
| Technisch | ✅ SQLite + PHP reicht völlig |
| Sicherheit | ✅ Lokal = minimales Risiko |
| Pädagogisch | ✅ Perfekt für Bitcoin-Education |
| Kosten | ✅ Minimal (~50€/Jahr) |
| Aufwand | ✅ 1-2 Wochen Entwicklung |

---

## 📋 NÄCHSTE SCHRITTE

1. **Entscheidung:** Option A, B oder C?
2. **Phase 1 starten:** Datenbank-Schema
3. **Modul-Integration:** Reward-Hooks einbauen
4. **UI entwickeln:** Dashboards

---

**Empfehlung:** Starte mit **Option C (Hybrid)** - einfach zu implementieren, später erweiterbar auf BTCPay.

---

*Dokument erstellt für sgiT Education Platform*  
*sgiT Solution Engineering & IT Services*
