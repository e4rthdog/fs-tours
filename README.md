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
3. Start the dev server: `quasar dev`

---

## License

MIT License
