# GeoAnalytics Project

## Project Overview

This is a web application for geoanalytics, built with a multi-container architecture using Docker Compose.

- **Frontend:** A Vue.js 3 Single-Page Application (SPA) using [Framework7](https://framework7.io/) for UI components, [Pinia](https://pinia.vuejs.org/) for state management, and [Vite](https://vitejs.dev/) for the development and build tooling.
- **Backend:** A PHP/Laravel API that serves data to the frontend.
- **Database:** PostgreSQL with the PostGIS extension for storing and querying geospatial data.
- **Web Server:** Nginx is used as a reverse proxy to route requests to the appropriate service.

## Building and Running

The entire application stack is managed by Docker Compose.

**To start all services:**

```sh
docker-compose up -d
```

- The main application will be accessible at [http://localhost:8000](http://localhost:8000).
- The frontend Vite development server runs on [http://localhost:5173](http://localhost:5173).
- The PostgreSQL database is exposed on port `5433` on the host machine.

**To stop all services:**

```sh
docker-compose down
```

## Development Environment

The `docker-compose.yml` file is configured to provide a seamless development experience with hot-reloading for both the frontend and backend.

- **`frontend` service:** This service builds the `frontend/Dockerfile` and runs the Vite development server using `npm run dev`.
    - It is accessible directly at **[http://localhost:5173](http://localhost:5173)**.
    - The `frontend/` directory is mounted as a volume into the container, so any changes you make to the Vue.js source code will trigger an instantaneous update in your browser (Hot Module Replacement).
    - For frontend development, this is the primary URL you will be working with.

- **`backend` service:** This service runs the PHP-FPM process for the Laravel application.
    - The `backend/` directory is also mounted as a volume, meaning any changes to your PHP code are immediately reflected without needing to rebuild the container.

- **`nginx` service:** This acts as the main entry point for the application, accessible at **[http://localhost:8000](http://localhost:8000)**. It routes requests:
    - API calls to `/api/...` are forwarded to the `backend` service.
    - Other requests are typically served by Nginx, which has access to the `backend/public` directory.

In summary, for development, run `docker-compose up -d` and edit the files in the `frontend` or `backend` directories. View frontend changes at `http://localhost:5173` and test API integrations through the main application URL `http://localhost:8000`.

## Development Conventions

### Frontend

The frontend code is located in the `frontend/` directory.

- **Code Style:** Code formatting is enforced by [Prettier](https://prettier.io/). To format the code, run:
  ```sh
  npm run format
  ```
- **Linting:** Code quality is checked with [ESLint](https://eslint.org/). To run the linter, run:
  ```sh
  npm run lint
  ```
- **Dependencies:** Frontend dependencies are managed with `npm`. Install them with `npm install` inside the `frontend` directory.

### Backend

The backend code is located in the `backend/` directory and is a standard Laravel application.

- Follow standard Laravel coding conventions and best practices.
- Backend dependencies are managed with Composer.

## Session Summary (2025-11-10)

### Analysis Overview

- **Backend (Laravel):**
    - Reviewed API routes in `routes/api.php`. Key endpoints identified:
        - `GET /api/usaha`: Fetches business data, handled by `UsahaController@index`. Supports bounding box and search queries.
        - `GET /api/usaha/suggestions`: Provides search suggestions, handled by `UsahaController@searchSuggestions`.
        - `GET /api/wilayah/kecamatan`: Fetches district data, handled by `WilayahController@kecamatan`.
        - `GET /api/geojson/subsls`: Serves GeoJSON data for sub-SLS areas, handled by `WilayahController@subslsGeojson`.
    - Inspected migrations, confirming the `usahas` table (`2025_11_06_180158_create_usahas_table.php`) is the primary data source, containing business details and geographic coordinates (latitude, longitude).

- **Frontend (Vue.js):**
    - Analyzed the main map component: `src/pages/MapPage.vue`.
    - It uses **Leaflet.js** for map rendering and interaction.
    - State management is handled by **Pinia**, with `useUsahaStore` and `useWilayahStore` fetching data from the backend API.
    - The component features dynamic marker loading/caching based on map viewport (`moveend`, `zoomend`), search functionality with suggestions, and a detail popup (`UsahaDetailPopup.vue`).
    - It attempts to use `leaflet.glify` for WebGL-based point rendering for performance, with a fallback to standard Leaflet canvas markers.