# fs-tours

Personal Flight Simulator tour viewer with interactive world map visualization.

## Features

- **World Map**: Visual tour display with colored routes and clickable flight legs
- **Tour Management**: Add, edit, delete tours and flight legs (password protected)
- **SimBrief Integration**: Import flight plans directly from SimBrief
- **Aircraft Data**: Enriched with airport coordinates and aircraft information

## Tech Stack

- **Backend**: PHP (Slim framework + Medoo) with SQLite database
- **Frontend**: Vue 3 + Quasar + Pinia
- **Data**: [AeroFetch](https://github.com/tiagohillebrandt/aerofetch) for aircraft/airport data

## Setup

### API

```bash
cd api
composer install
# Ensure db/fstours.db exists (copy from fstours.sample if needed)
# Configure web server to serve public/index.php
```

### Frontend

```bash
cd frontend
npm install

# Copy and configure settings
cd src/config
cp config.example.js config.js
# Edit config.js with your API URL and SimBrief username

# Start development server
quasar dev
```

## Configuration

Edit `frontend/src/config/config.js`:

```javascript
export const config = {
	apiBaseUrl: 'https://your-api-domain.com',
	simbriefUsername: 'your-simbrief-username',
}
```

**Note**: `config.js` is git-ignored. Use `config.example.js` as template.

## License

MIT
