# 🐳 sgiT Education - Docker Setup

## Quick Start

```bash
# 1. In das Docker-Verzeichnis wechseln
cd C:\xampp\htdocs\Education\docker

# 2. Environment kopieren
cp .env.example .env

# 3. Container starten
docker-compose up -d

# 4. Öffnen im Browser
# http://localhost:8080
```

## Befehle

| Befehl | Beschreibung |
|--------|--------------|
| `docker-compose up -d` | Starten (Hintergrund) |
| `docker-compose down` | Stoppen |
| `docker-compose logs -f` | Logs anzeigen |
| `docker-compose logs -f php` | Nur PHP Logs |
| `docker-compose up -d --build` | Neu bauen |
| `docker-compose exec php bash` | Shell im PHP Container |

## Container

| Container | Port | Beschreibung |
|-----------|------|--------------|
| `sgit_nginx` | 8080 | Webserver |
| `sgit_php` | 9000 | PHP-FPM (intern) |
| `sgit_ollama` | 11434 | AI/LLM Server |

## Volumes

| Volume | Pfad | Beschreibung |
|--------|------|--------------|
| `sgit_education_sqlite` | `/var/www/html/AI/data` | Fragen-DB |
| `sgit_education_wallet` | `/var/www/html/wallet` | Wallet-DB |
| `sgit_education_backups` | `/var/www/html/backups` | Backups |
| `sgit_ollama_models` | `/root/.ollama` | AI Models |

## Ollama Model laden

```bash
# Im Ollama Container
docker-compose exec ollama ollama pull llama3.2:latest

# Oder von außen
curl http://localhost:11434/api/pull -d '{"name": "llama3.2:latest"}'
```

## Troubleshooting

### Container startet nicht
```bash
docker-compose logs nginx
docker-compose logs php
```

### Permission Denied
```bash
# Im Container Rechte setzen
docker-compose exec php chown -R www-data:www-data /var/www/html
```

### Ollama nicht erreichbar
```bash
# Prüfen ob Container läuft
docker-compose ps

# Ollama Test
curl http://localhost:11434/api/tags
```

## Struktur

```
docker/
├── docker-compose.yml    # Haupt-Orchestrierung
├── Dockerfile           # PHP-FPM Image
├── .env.example         # Environment Template
├── nginx/
│   ├── nginx.conf       # nginx Haupt-Config
│   └── sites/
│       └── education.conf  # Site Config
└── php/
    └── php.ini          # PHP Config
```

---

**Version:** 1.0  
**Datum:** 05.12.2025
