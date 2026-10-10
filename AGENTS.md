# AGENTS.md

FS Tours is a flight simulator tour viewer: an interactive world map for managing and displaying tours and their flight legs.

Project-specific instructions live next to the code: [api/AGENTS.md](api/AGENTS.md) and [frontend/AGENTS.md](frontend/AGENTS.md). This file holds what applies to both.

## Repository layout

```
fs-tours/
├── api/        # PHP backend (Slim 4 + Medoo, SQLite)
└── frontend/   # Vue 3 + Quasar SPA
```

## Setup

```bash
# API
cd api && composer install
cp .env.example .env              # set ADMIN_PASSWORD

# Frontend
cd frontend && npm install
cp src/config/config.example.js src/config/config.js   # set apiBaseUrl and simbriefUsername
npm run dev
```

## Admin authentication

Admin API calls send `Authorization: Bearer {ADMIN_PASSWORD}`; the frontend verifies it via `POST /auth/verify` and keeps the password as the token, clearing it on logout or auth failure.
The plaintext password is the session token. Acceptable only in trusted environments; do not extend this scheme unless asked.

## Git

- Personal project: work directly on `develop`, then merge `develop` into `main`. No feature branches, no pull requests.
- Commit messages: [Conventional Commits](https://www.conventionalcommits.org/) (`feat`, `fix`, `docs`, `refactor`, `build`, ...), imperative mood, lowercase first letter, no trailing period, single concise line with no body.
- Do not commit or merge unless asked.

## Working style

- Give short, step-by-step instructions.
- Do not add extra information or next steps unless asked.
- For code examples, focus on architecture and patterns rather than full implementations, unless asked.
