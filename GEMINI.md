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

---

## Database Restoration from Scratch

This guide explains how to set up and populate the entire database from a clean state. This is useful after running `migrate:fresh` or for setting up a new development environment.

**Run these commands sequentially from the project root directory:**

### 1. Reset and Create Tables
This command drops all tables and re-runs all migrations.
```sh
docker-compose exec backend php artisan migrate:fresh
```

### 2. Seed Base Data
This command runs the main seeder, which populates essential tables like `usahas` and `muatan_subsls`.
```sh
docker-compose exec backend php artisan db:seed
```

### 3. Seed `regsosek` Data
This command populates the large `regsosek` table from its CSV file. This may take some time.
```sh
docker-compose exec backend php artisan db:seed --class=RegsosekSeeder
```

### 4. Populate `demografi_sls`
This command aggregates data from `regsosek` and `muatan_subsls` to create the demographic data needed for analysis.
```sh
docker-compose exec backend php artisan populate:demografi-sls
```

### 5. Import `peta_sls` Polygons
This command imports the large GeoJSON file containing SLS polygons into the `peta_sls` table.
```sh
docker-compose exec backend php artisan import:peta-sls
```

### 6. Restore Geocode Data and Finalize
This is the final, integrated step. The `--import` flag restores your backed-up geocoding results. The command then runs data cleaning tasks. The `--export` flag saves the final, cleaned state back to your backup file.
```sh
docker-compose exec backend php artisan data:fix-usaha --import --export
```

After these steps, your database will be fully restored and ready for development.

---

## Session Summary (2025-11-12)

### Progress Overview

- **Data Ingestion:** Successfully created and executed seeders/commands to populate all primary tables required for Phase 2 analysis:
    - `regsosek`: Populated with ~205k records.
    - `demografi_sls`: Populated by aggregating `regsosek` data.
    - `peta_sls`: Populated with ~1.3k SLS polygons from the `Final_SLS_202416104.geojson` file.
    - `referensi_kbli`: Populated.
- **Model Creation:** Created all missing Eloquent models (`DemografiSls`, `PetaSls`, `Regsosek`, `ReferensiKbli`, `AnalisisZonaCache`) to align with the database schema, improving code structure and maintainability.
- **Geocode Backup/Restore Mechanism:**
    - Created `usaha:export-geocode` command to back up `latitude`, `longitude`, and `geom` data to a CSV file (`database/backups/geocode_backup.csv`).
    - Created `usaha:import-geocode` command to rapidly restore geocoding results from the backup file after a database migration.
    - Integrated both commands into `data:fix-usaha` via `--import` and `--export` flags for a streamlined workflow.
- **Data Quality Dashboard:**
    - Created a diagnostic view at `/data-quality/usaha` to provide a comprehensive overview of all key tables.
    - Implemented performance optimizations (caching, index additions, query refactoring) to ensure the dashboard loads quickly despite large datasets.

### Current Status
All foundational data and tooling required for `spec/phase2.md` are now in place. The database is fully populated, and a robust workflow for data management has been established. The project is ready to proceed with the development of the core analysis logic (`analisis:run-zona` command).
