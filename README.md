# sgiT Education Platform

**AI-Powered Learning Platform with Gamification & Multiplayer Games**

![Status](https://img.shields.io/badge/Status-Production-brightgreen)
![Version](https://img.shields.io/badge/Version-v3.47.0-blue)
![Platform](https://img.shields.io/badge/Platform-Proxmox_LXC-orange)
![AI](https://img.shields.io/badge/AI-Gemma2%3A2b-purple)

---

## 📋 Overview

The sgiT Education Platform is a comprehensive learning management system with integrated AI assistance, gamification features, and multiplayer educational games. Built on modern web technologies and powered by local AI models for question generation and adaptive learning.

**Live URL:** https://edu.sgit.space  
**Production Server:** Proxmox VE LXC Container (CT 105)  
**IP Address:** 192.168.200.145

---

## 🚀 Current Status (21.12.2025)

### ✅ Production Deployment
- **Migration Complete:** Successfully migrated from Windows Notebook to Proxmox Server
- **Migration Date:** 21.12.2025 15:20 CET
- **Duration:** ~2.5 hours (including troubleshooting)
- **Status:** PRODUCTION READY ✅

### 📊 Platform Statistics
- **Questions:** 4,904 total
  - 1,178 AI-generated (via Gemma2:2b)
  - 3,720 CSV-imported
  - 3,710 with explanations
- **Modules:** 21 active learning modules
- **Games:** 7 multiplayer games
- **Wallet System:** 12,082 Sats distributed
- **Users:** Multi-user support with role-based access

---

## 🛠️ Technical Stack

### Infrastructure
- **Platform:** Proxmox VE 9.1.2 (LXC Container CT 105)
- **OS:** Debian 12
- **Container:** sgit-edu
- **Resources:**
  - RAM: 8GB
  - CPU: 4 Cores
  - Disk: 50GB

### Software Stack
- **Docker:** v29.1.3
- **Docker Compose:** v3.0.0
- **Web Server:** nginx:alpine
- **PHP:** 8.3-FPM
- **AI Engine:** Ollama (Gemma2:2b model, 1.6GB)
- **Database:** SQLite with WAL mode
- **Reverse Proxy:** Nginx Proxy Manager
- **SSL:** Let's Encrypt

### Container Architecture
```
├── sgit-education-nginx     (Port 8080→80)
├── sgit-education-php       (Port 9000)
└── sgit-education-ollama    (Port 11434, Model: Gemma2:2b)
```

---

## 📦 Features

### Learning Modules (21 Active)
- Mathematik
- Englisch
- Biologie
- Physik
- Chemie
- Geschichte
- Erdkunde
- Logik & Denken
- Wissensvermittlung
- Legal Understanding
- Code Challenges
- HTML & CSS
- Schach
- Dame
- Business
- Unterhaltung
- Poker
- Romme
- Maumau
- Elternfragen
- Skat

### AI-Powered Features
- **AI Question Generator:** Automatic question generation using Gemma2:2b
- **Adaptive Learning:** Personalized learning paths
- **Bot System:** Automated testing and quality assurance
- **Performance Metrics:** TTFB, Response-Zeit tracking

### Multiplayer Games
- Schach (Chess)
- Dame (Checkers)
- Poker
- Romme (Rummy)
- Maumau
- Skat
- Business Strategy Games

### Wallet System
- Virtual currency (Sats)
- Reward mechanism
- User transactions
- Balance tracking

---

## 🔧 Installation & Deployment

### Prerequisites
- Proxmox VE Server
- Docker + Docker Compose
- 8GB RAM minimum
- 50GB disk space

### Quick Start
```bash
# Clone repository (on Proxmox CT)
cd /opt
scp -r sgit-admin@192.168.200.128:/share/backups/sg-dev-113/daily education

# Navigate to docker directory
cd /opt/education/docker

# Start containers
docker compose up -d

# Pull AI model
docker exec sgit-education-ollama ollama pull gemma2:2b

# Fix permissions (CRITICAL!)
docker exec -it sgit-education-php bash
cd /var/www/html
chown -R www-data:www-data .
find . -type d -exec chmod 775 {} \;
find . -name "*.db*" -exec chmod 666 {} \;
exit

# Restart containers
docker compose restart
```

### Access
- **Direct:** http://192.168.200.145:8080
- **HTTPS:** https://edu.sgit.space
- **Admin Panel:** /admin_v4.php
- **Login:** admin / sgit2025

---

## 🔐 Security

### Authentication
- Admin access protected
- Session management
- CSRF protection

### Network Security
- Nginx Proxy Manager with SSL
- VPN access available (10.8.0.0/24)
- Firewall rules active

### Data Protection
- SQLite database with WAL mode
- Regular backups to QNAP NAS
- Version control via Git

---

## 📝 Migration Notes (21.12.2025)

### Source System (DEPRECATED)
- **Platform:** Windows 11 Notebook (sg-dev-113)
- **IP:** 192.168.200.113
- **Environment:** Docker Desktop
- **Backup Location:** QNAP /share/backups/sg-dev-113/daily

### Target System (PRODUCTION)
- **Platform:** Proxmox VE LXC Container
- **Container ID:** CT 105
- **Hostname:** sgit-edu
- **IP:** 192.168.200.145

### Migration Process
1. ✅ Container creation (Debian 12, 8GB RAM, 4 Cores, 50GB)
2. ✅ Docker installation (v29.1.3 + Compose v3.0.0)
3. ✅ File transfer via SCP from QNAP backup
4. ✅ docker-compose.yml configuration (removed OneDrive mount)
5. ✅ Container deployment (nginx, php, ollama)
6. ✅ AI model installation (Gemma2:2b, 1.6GB)
7. ✅ Permission fixes (www-data:www-data, INSIDE container)
8. ✅ Testing & verification
9. ✅ NPM proxy update (edu.sgit.space → .145:8080)

### Key Lessons Learned
- **SQLite Permissions:** MUST be set INSIDE the container, not on host
  ```bash
  docker exec -it sgit-education-php bash
  chown -R www-data:www-data /var/www/html
  ```
- **Gemma2:2b vs llama3.2:** Smaller model saved 4GB RAM + 30GB disk
- **Container Rebuild:** Use `docker compose up -d --build` for clean rebuild
- **Permission Strategy:** 
  - Directories: 775
  - SQLite DBs: 666
  - Owner: www-data:www-data

### Performance
- **Migration Duration:** ~2.5 hours (including troubleshooting)
- **Files Transferred:** 1,004 files
- **Data Size:** 4,904 questions, 21 modules, multiple SQLite databases
- **Zero Downtime:** VPN users maintained access to old system during migration

---

## 🔄 Maintenance

### Backup Strategy
- **Source:** /opt/education
- **Destination:** QNAP NAS /share/backups/sgit-edu
- **Method:** rsync over SSH
- **Schedule:** Daily (planned)
- **Retention:** 30 days

### Monitoring
- **Platform:** Uptime Kuma
- **Monitors:** Planned (External + Internal)
- **Alerts:** Email to admin@sgit.space
- **Metrics:** Response time, uptime, SSL certificate expiry

### Updates
```bash
# Update containers
cd /opt/education/docker
docker compose pull
docker compose up -d

# Update AI model
docker exec sgit-education-ollama ollama pull gemma2:2b
```

---

## 🐛 Troubleshooting

### Common Issues

**1. SQLite Readonly Database Errors**
```bash
# Fix permissions INSIDE container
docker exec -it sgit-education-php bash
cd /var/www/html
chown -R www-data:www-data .
find . -type d -exec chmod 775 {} \;
find . -name "*.db*" -exec chmod 666 {} \;
exit
docker compose restart
```

**2. Container Won't Start**
```bash
cd /opt/education/docker
docker compose down
docker compose up -d --build
```

**3. AI Model Not Responding**
```bash
# Check Ollama container
docker logs sgit-education-ollama

# Reload model
docker exec sgit-education-ollama ollama pull gemma2:2b
```

**4. Permission Issues After Update**
```bash
# Always fix permissions after file changes
docker exec -it sgit-education-php bash
cd /var/www/html && chown -R www-data:www-data .
```

### Logs
```bash
# View all container logs
cd /opt/education/docker
docker compose logs -f

# View specific container
docker compose logs -f sgit-education-php
```

---

## 📚 Documentation

### File Structure
```
/opt/education/
├── docker/
│   ├── docker-compose.yml
│   ├── Dockerfile
│   ├── nginx/
│   └── php/
├── admin/
├── api/
├── bots/
├── Database/
├── wallet/
├── logik/
├── schach/
└── *.db (SQLite databases)
```

### Configuration Files
- `docker-compose.yml` - Container orchestration
- `Dockerfile` - PHP container build
- `nginx/default.conf` - Web server config
- `php/php.ini` - PHP configuration

---

## 🤝 Contributing

This is a private educational platform for sgiT. For questions or suggestions:
- **Contact:** admin@sgit.space
- **Issues:** Create via GitHub Issues
- **Documentation:** Keep updated in this README

---

## 📄 License

Private/Proprietary - sgiT © 2025

---

## 🎯 Roadmap

### Immediate (Next 7 Days)
- [ ] Uptime Kuma monitoring setup
- [ ] QNAP backup automation (rsync + cronjob)
- [ ] Performance benchmarking
- [ ] Notebook .113 shutdown

### Short-term (Next Month)
- [ ] SSL certificate automation renewal
- [ ] Enhanced logging system
- [ ] User management improvements
- [ ] Additional AI model evaluation

### Long-term
- [ ] Multi-language support
- [ ] Advanced analytics dashboard
- [ ] Mobile app integration
- [ ] API for third-party integrations

---

## 📞 Support

**Platform Issues:**
- Check logs: `docker compose logs -f`
- Restart containers: `docker compose restart`
- Contact: admin@sgit.space

**Infrastructure Issues:**
- Proxmox: https://pve.sgit.space
- Monitoring: https://monitoring.sgit.space
- VPN: Contact admin for access

---

**Last Updated:** 21.12.2025  
**Version:** v3.47.0  
**Maintained by:** deStevie (Steven Günther)  
**Production Server:** Proxmox CT 105 (192.168.200.145)
