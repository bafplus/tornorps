# TornOps

TornOps is a web portal for Torn City faction members to aggregate, analyze, and visualize data from Torn City APIs and third-party services.

## Features

- **Ranked War Tracking** - Monitor faction wars and attack statistics
- **Member Analytics** - Track faction member performance and stats
- **Multi-API Integration** - Torn API, FFScouter, TornStats
- **Auto-Sync** - Scheduled data synchronization
- **Member Portal** - Individual user dashboards and settings
- **Admin Panel** - Full control over faction settings and API keys

## Tech Stack

- **Framework:** Laravel 11
- **PHP:** 8.3
- **Database:** MariaDB
- **Web Server:** Apache (in Docker)
- **Container:** Docker (single container deployment)
- **Admin Panel:** Filament 3.x

## Requirements

- Docker & Docker Compose
- Torn API Key
- FFScouter API Key (optional)

## Quick Start

### 1. Clone & Setup

```bash
git clone https://github.com/bafplus/tornorps.git
cd tornorps
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your API keys:
```env
TORN_API_KEY=your-torn-api-key
```

> **Note:** `APP_KEY` wordt automatisch gegenereerd in stap 4.

### 3. Start Docker Container

```bash
docker compose up -d
```

### 4. Setup Database

```bash
docker exec tornops php artisan key:generate
docker exec tornops php artisan migrate
```

### 5. Access Application

- **Portal:** http://localhost:8080
- **Admin Panel:** http://localhost:8080/admin

## Project Structure

```
tornorps/
├── app/
│   ├── Console/Commands/    # Artisan commands for syncing
│   ├── Models/             # Eloquent models
│   ├── Services/           # API services (Torn, FFScouter)
│   └── Filament/           # Admin panel resources
├── database/migrations/    # Database schema
├── docker/                 # Docker configuration
├── config/                 # Laravel configuration
└── resources/views/       # Blade templates
```

## Artisan Commands

```bash
# Sync all faction data
php artisan torn:sync-faction {faction_id}

# Sync members only
php artisan torn:sync-members {faction_id}

# Sync wars only
php artisan torn:sync-wars {faction_id}
```

## Data Sync Schedule

| Data Type | Frequency |
|-----------|-----------|
| Faction Members | Every 15 minutes |
| Active Wars | Every 5 minutes |
| Ranked Wars | Every 5 minutes |
| Player Profiles | Every 30 minutes |

## API Keys Setup

### Torn API Key
1. Go to https://www.torn.com/preferences.php#tab=api
2. Create a custom key with selections:
   - Faction: members, rankedwarreport, warfare, wars, rankedwars
   - User: profile, attacks, battlestats, personalstats, basic

### FFScouter API Key
1. Go to https://ffscouter.com/
2. Register and get your API key

## Configuration

### Faction Settings (Admin Panel)
- Faction ID
- Torn API Key
- FFScouter API Key
- Auto-sync toggle
- Sync frequency settings

## Development

### Running Tests
```bash
docker exec tornops php artisan test
```

### Logs
```bash
docker logs tornops
```

## License

MIT License
