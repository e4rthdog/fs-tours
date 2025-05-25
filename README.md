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

# Copy and configure environment
cp .env.example .env
# Edit .env and set ADMIN_PASSWORD=your_secure_password

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

### Admin Authentication

The application has password-protected admin functionality for CRUD operations:

1. **API Configuration**:
   - Copy `api/.env.example` to `api/.env`
   - Set a secure `ADMIN_PASSWORD` (required - no default fallback)
2. **Frontend**: Click the admin toggle button (🔐) in the header
3. **Login**: Enter the password to enable tour/leg management

**Security Note**: The API requires `ADMIN_PASSWORD` to be explicitly set in the environment. If not configured, admin operations will fail with a 500 error.

**Note**: Both `config.js` and `.env` are git-ignored. Use their `.example` files as templates.

## License

MIT
