# fs-tours

## Overview

fs-tours is a personal Flight Simulator tour viewer. The application allows users to view, add, edit, and delete tours and their associated flight legs. The main page displays a world map with tours and legs visualized, and each leg is clickable for detailed information.

---

## Project Structure

- **api/**: PHP backend using Slim and Medoo, with SQLite database. Integrates [AeroFetch](https://github.com/tiagohillebrandt/aerofetch) for aircraft and airport data.
- **frontend/**: Vue 3 (Composition API) + Quasar + Pinia + Vue Router. Modern UI for managing and visualizing tours and legs.

---

## API

- Built with PHP using the Slim framework and Medoo for database access.
- Uses a SQLite database for storing tours and legs.
- Integrates the [AeroFetch](https://github.com/tiagohillebrandt/aerofetch) library to fetch aircraft and airport data.
- Provides endpoints to:
  - Fetch all tours or a specific tour.
  - Fetch all legs or a specific leg, including enriched data such as airport coordinates and aircraft model.
  - Add, edit, or delete tours and legs (password protected for write operations).

---

## Frontend

- Built with Vue 3 (Composition API), Quasar Framework, Pinia for state management, and Vue Router.
- Main page displays a world map with colored tours and clickable legs.
- Features:
  - Select and view tours and their flight legs on a map.
  - Add, edit, and delete tours and legs (password protected for write operations).
  - View detailed information for each leg (origin, destination, aircraft, route, comments, links).
  - Responsive and modern UI.

---

## Setup

### API

1. `cd api`
2. Install dependencies: `composer install`
3. Ensure `db/fstours.db` exists (use `fstours.sample` as a template if needed)
4. Configure your web server to serve `public/index.php`

### Frontend

1. `cd frontend`
2. Install dependencies: `npm install`
3. **Configure the application**: Copy and edit the configuration file:

   ```bash
   cd src/config
   cp config.example.js config.js
   ```

   Edit `config.js` with your settings:

   ```javascript
   export const config = {
   	// Update this to your API server URL
   	apiBaseUrl: 'https://your-api-domain.com',

   	// Update this to your SimBrief username
   	simbriefUsername: 'your-simbrief-username',
   }
   ```

4. Start the dev server: `quasar dev`

---

## Configuration

### Configuration Options

#### `apiBaseUrl`

- The base URL for your FS Tours API server
- Examples:
  - Development: `http://fs-tours-api.ddev.site`
  - Production: `https://api.your-domain.com`

#### `simbriefUsername`

- Your SimBrief account username
- Used to fetch flight plans from SimBrief
- Must be a valid SimBrief username

### Security Notes

- The `config.js` file is ignored by git and will not be committed
- Never commit sensitive configuration data to the repository
- Each deployment environment should have its own `config.js` file
- The `config.example.js` file serves as a template and should be kept updated

### Deployment

For production deployment:

1. Copy `config.example.js` to `config.js`
2. Update with production settings
3. Ensure your web server serves the frontend application
4. The configuration will be bundled into the application at build time

---

## License

MIT License
