# 🐳 sgiT Docker Template

## Namensschema

Alle sgiT Docker-Projekte folgen dem Schema:

```
sgit-%projectname%
```

### Beispiele

| Projekt | Name | Container |
|---------|------|-----------|
| Education Platform | `sgit-education` | sgit-education-nginx, sgit-education-php, sgit-education-ollama |
| Wearpart | `sgit-wearpart` | sgit-wearpart-nginx, sgit-wearpart-php |
| API Service | `sgit-api` | sgit-api-nginx, sgit-api-php |

## Verwendung

### 1. Template kopieren

```bash
cp -r templates/docker/ mein-neues-projekt/docker/
```

### 2. Variablen ersetzen

Ersetze in allen Dateien:
- `${PROJECT_NAME}` → Dein Projektname (z.B. "My App")
- `${PROJECT_NAME_LOWER}` → Kleingeschrieben, keine Leerzeichen (z.B. "myapp")

```bash
# Beispiel mit sed (Linux/Mac)
cd mein-neues-projekt/docker
sed -i 's/${PROJECT_NAME}/My App/g' docker-compose.yml
sed -i 's/${PROJECT_NAME_LOWER}/myapp/g' docker-compose.yml
```

### 3. Ports anpassen (falls nötig)

Wenn bereits ein anderes sgiT-Projekt auf Port 8080 läuft:

```yaml
ports:
  - "8081:80"  # Ändere zu 8081, 8082, etc.
```

Für Ollama (falls mehrere laufen):

```yaml
ports:
  - "11435:11434"  # Ändere zu 11435, 11436, etc.
```

### 4. Starten

```bash
docker-compose up -d
```

## Enthaltene Services

| Service | Beschreibung | Standard-Port |
|---------|--------------|---------------|
| nginx | Webserver & Reverse Proxy | 8080 |
| php | PHP-FPM 8.3 Application Server | 9000 (intern) |
| ollama | AI/LLM Server (optional) | 11434 |

## Dateien

```
docker/
├── docker-compose.template.yml  # Haupt-Template
├── README.md                    # Diese Datei
├── Dockerfile                   # PHP-FPM Image (kopieren!)
├── nginx/
│   ├── nginx.conf              # nginx Haupt-Config
│   └── sites/
│       └── default.conf        # Site Config
└── php/
    └── php.ini                 # PHP Config
```

## Vorteile des Namensschemas

1. **Übersichtlichkeit** in Docker Desktop
2. **Eindeutige Identifikation** bei mehreren Projekten
3. **Konsistente Benennung** von Netzwerken und Volumes
4. **Einfache Verwaltung** mit `docker ps | grep sgit-`

---

**Erstellt:** 12.12.2025  
**Schema-Version:** 1.0
