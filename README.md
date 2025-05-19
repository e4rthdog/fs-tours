# fs-tours

## Overview

fs-tours is a personal Flight Simulator tour viewer. The application allows users to view, add, edit, and delete tours and their associated flight legs. The main page displays a world map with tours and legs visualized, and each leg is clickable for detailed information.

## API

- Built with PHP using the Slim framework and Medoo for database access.
- Uses a SQLite database for storing tours and legs.
- Integrates the [AeroFetch](https://github.com/tiagohillebrandt/aerofetch) library to fetch aircraft and airport data.
- Provides endpoints to:
  - Fetch all tours or a specific tour.
  - Fetch all legs or a specific leg, including enriched data such as airport coordinates and aircraft model.
