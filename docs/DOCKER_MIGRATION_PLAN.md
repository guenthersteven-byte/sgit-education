# 🐳 Docker Migration Plan - XAMPP → Docker/nginx

**Erstellt:** 05. Dezember 2025  
**Status:** 📋 GEPLANT  
**Geschätzter Aufwand:** 6-8 Stunden  
**Priorität:** MITTEL (Nice-to-Have für Production)

---

## 🎯 Ziel

Migration der sgiT Education Platform von XAMPP (Apache/PHP) zu einer Docker-basierten Infrastruktur mit nginx, PHP-FPM und optionalem Ollama-Container.

---

## 📊 Aktueller Stack vs. Ziel-Stack

| Komponente | XAMPP (Aktuell) | Docker (Ziel) |
|------------|-----------------|---------------|
| **Webserver** | Apache 2.4 | nginx:alpine |
| **PHP** | PHP 8.x (mod_php) | PHP-FPM 8.3 |
| **Datenbank** | SQLite | SQLite (unverändert) |
| **AI** | Ollama (lokal) | Ollama Container oder Host |
| **OS** | Windows | Container (Linux) |
| **Pfade** | `C:\xampp\htdocs\Education` | `/var/www/html` |

---

## 🏗️ Geplante Architektur

```
┌─────────────────────────────────────────────────────────────┐
│                    Docker Compose Stack                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐  │
│  │    nginx     │    │   PHP-FPM    │    │   Ollama     │  │
│  │   :80/:443   │───▶│    :9000     │    │   :11434     │  │
│  │   (Proxy)    │    │  (App Code)  │    │  (AI Model)  │  │
│  └──────────────┘    └──────────────┘    └──────────────┘  │
│         │                   │                   │           │
│         └───────────────────┴───────────────────┘           │
│                             │                                │
│                      ┌──────┴──────┐                        │
│                      │   Volumes   │                        │
│                      │  - app_data │                        │
│                      │  - sqlite   │                        │
│                      │  - backups  │                        │
│                      └─────────────┘                        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 Geplante Dateistruktur

```
C:\xampp\htdocs\Education\
├── docker/
│   ├── docker-compose.yml      # Haupt-Orchestrierung
│   ├── docker-compose.dev.yml  # Development Overrides
│   ├── docker-compose.prod.yml # Production Overrides
│   ├── Dockerfile              # PHP-FPM Image
│   ├── nginx/
│   │   ├── nginx.conf          # Haupt-Konfiguration
│   │   └── sites/
│   │       └── education.conf  # Site-spezifisch
│   ├── php/
│   │   ├── php.ini             # PHP Konfiguration
│   │   └── www.conf            # PHP-FPM Pool
│   └── ollama/
│       └── Dockerfile          # Optional: Custom Ollama
├── ... (bestehende App-Dateien)
```

---

## 🔧 Migrations-Schritte

### Phase 1: Docker-Grundgerüst (2h)
- [ ] `docker-compose.yml` erstellen
- [ ] `Dockerfile` für PHP-FPM
- [ ] nginx Konfiguration
- [ ] Volumes für SQLite DBs

### Phase 2: App-Anpassungen (2h)
- [ ] Pfade von Windows auf Linux umstellen
- [ ] `db_config.php` Docker-kompatibel machen
- [ ] Ollama URL konfigurierbar machen (Host vs Container)
- [ ] Environment Variables einführen

### Phase 3: Testing (1-2h)
- [ ] Alle 16 Module testen
- [ ] Wallet-System testen
- [ ] AI Generator testen
- [ ] Leaderboard testen
- [ ] Foxy Chatbot testen

### Phase 4: Optimierung (1-2h)
- [ ] nginx Caching einrichten
- [ ] PHP OPcache konfigurieren
- [ ] Health Checks hinzufügen
- [ ] Logging zentralisieren

### Phase 5: Production Prep (Optional)
- [ ] SSL/TLS mit Let's Encrypt
- [ ] Production docker-compose
- [ ] CI/CD Pipeline (GitHub Actions)
- [ ] Deployment zu sgit.space

---

## ⚠️ Bekannte Herausforderungen

| Challenge | Lösung |
|-----------|--------|
| SQLite Dateipfade | Volume Mounts mit korrekten Permissions |
| Ollama Verbindung | `host.docker.internal` oder separater Container |
| Windows → Linux | Pfad-Separatoren, Case-Sensitivity |
| File Uploads | Shared Volume zwischen nginx und PHP |
| Session Handling | Redis oder File-based mit Volume |

---

## 🌐 Umgebungsvariablen (geplant)

```env
# .env (für Docker)
APP_ENV=development
APP_DEBUG=true

# Database
DB_PATH=/var/www/html/AI/data/questions.db
WALLET_DB_PATH=/var/www/html/wallet/wallet.db

# Ollama
OLLAMA_HOST=ollama
OLLAMA_PORT=11434
OLLAMA_MODEL=llama3.2:latest

# nginx
NGINX_HOST=localhost
NGINX_PORT=80
```

---

## 📋 Voraussetzungen

### Auf Entwicklungsrechner:
- [ ] Docker Desktop für Windows installiert
- [ ] WSL2 Backend aktiviert
- [ ] Mindestens 8GB RAM für Ollama

### Für Production (sgit.space):
- [ ] Docker + Docker Compose auf Server
- [ ] Domain DNS konfiguriert
- [ ] SSL Zertifikat (Let's Encrypt)

---

## 🚀 Quick Start (nach Migration)

```bash
# Development starten
cd C:\xampp\htdocs\Education
docker-compose up -d

# Logs anzeigen
docker-compose logs -f

# Stoppen
docker-compose down

# Mit Neuaufbau
docker-compose up -d --build
```

---

## 📊 Vorteile nach Migration

| Vorteil | Beschreibung |
|---------|--------------|
| **Portabilität** | Läuft überall gleich (Windows, Mac, Linux, Server) |
| **Isolation** | Keine Konflikte mit anderen Projekten |
| **Reproduzierbar** | Exakt gleiche Umgebung für alle |
| **Skalierbar** | Einfach mehr Container starten |
| **Production-Ready** | Direkter Pfad zu sgit.space |
| **Backup einfacher** | Volume-basierte Backups |

---

## 🔗 Referenzen

- [Docker PHP Best Practices](https://docs.docker.com/language/php/)
- [nginx + PHP-FPM](https://www.nginx.com/resources/wiki/start/topics/examples/phpfcgi/)
- [Ollama Docker](https://hub.docker.com/r/ollama/ollama)

---

**Status:** Bereit für Implementierung wenn gewünscht ✅
