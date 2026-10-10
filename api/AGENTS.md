# AGENTS.md (api)

PHP 8+ backend using the Slim Framework 4 and the Medoo ORM on SQLite. See the root [AGENTS.md](../AGENTS.md) for shared rules (git, admin auth flow).

## Stack rules

- Use Slim for routing/middleware and Medoo for all database access.
- Use [AeroFetch](https://github.com/tiagohillebrandt/aerofetch) for airport and aircraft data. Do not add another data source.

## Layout

- `public/index.php` - entry point: dependencies, Medoo, middleware and all routes
- `db/` - SQLite database (not committed)
- `.ddev/` - local environment (`ddev start`)
- `.env` - `ADMIN_PASSWORD`, optional `API_BASE_PATH` for subdirectory deployment

## Routes

- CRUD for `/tours` and `/legs`, plus `GET /tours/{id}/legs` (enriched) and `POST /auth/verify`.
- Reads are public. Writes and `/auth/verify` require admin auth (Bearer token compared with `ADMIN_PASSWORD`). CORS headers go on every response.
- Deleting a tour also deletes its legs.
- Keep the frontend store (`frontend/src/stores/fstours.js`) in sync when a route or payload changes.

## Database schema

- **tours**: `tour_id` (unique, required), `tour_description` (required)
- **tour_legs**: `id` (PK), `tour_id` (references `tours.tour_id`), `origin`, `destination`, `aircraft`, optional `route`, `comments`, `flight_date`, `link1`-`link3`

## Leg enrichment and validation

- Before returning legs, use AeroFetch to add `*_coords` and `*_name` for origin and destination, and `aircraft_model`. These are computed on read, never stored.
- Validate leg payloads on create and update. Store `tour_id`, `origin`, `destination` and `aircraft` uppercase.
