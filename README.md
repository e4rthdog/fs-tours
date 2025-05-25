# FS Tours

Personal Flight Simulator tour viewer with interactive world map.

## Features

- **Interactive Map**: Flight legs with directional arrows, zoom-responsive ICAO labels, and clickable sequence markers
- **Tour Management**: Password-protected CRUD operations for tours and legs
- **Tour Sharing**: Share tours via URLs (e.g., `/#/tour/TOUR123`)
- **SimBrief Integration**: Import flight plans with distance and altitude data
- **Auto-capitalization**: Consistent ICAO codes and tour IDs

## Tech Stack

- **Backend**: PHP (Slim + Medoo) with SQLite
- **Frontend**: Vue 3 + Quasar + Pinia
- **Data**: [AeroFetch](https://github.com/tiagohillebrandt/aerofetch) for airport/aircraft enrichment

## Quick Setup

### API

```bash
cd api && composer install
cp .env.example .env
# Set ADMIN_PASSWORD in .env
```

### Frontend

```bash
cd frontend && npm install
cp src/config/config.example.js src/config/config.js
# Configure API URL and SimBrief username
npm run dev
```

## Configuration

**Frontend** (`src/config/config.js`):

```javascript
export const config = {
	apiBaseUrl: 'https://your-api.com',
	simbriefUsername: 'your-username',
}
```

**API** (`.env`):

```env
ADMIN_PASSWORD=your_secure_password
API_BASE_PATH=/subdirectory  # Optional for subdirectory deployment
```

## Admin Access

Click the lock icon (🔐) in the header and enter your admin password to manage tours and legs.

## License

MIT
